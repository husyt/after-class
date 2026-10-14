extends Area3D
## Full replacement for the activity / black-transition Area3D script.
## Keep FPrompt (Label3D) and the Area3D's CollisionShape3D child.
## SceneInteraction.gd inherits this implementation for doors and room tasks.

@export var task_id: String = ""
@export_file("*.tscn") var target_scene_path: String = ""
## Seconds held completely black, in addition to the fades.
@export_range(2.0, 4.0, 0.1) var transition_duration: float = 3.0
@export_range(0.1, 1.0, 0.05) var fade_duration: float = 0.45
@export var mouse_padding: float = 15.0
@export var click_padding: float = 0.05
@export var hover_scale: float = 1.15
@export var hover_color: Color = Color(1.0, 0.85, 0.2, 1.0)

@onready var prompt: Label3D = get_node_or_null("FPrompt") as Label3D

var game_state: Node = null
var current_player: CharacterBody3D = null
var player_near: bool = false
var busy: bool = false
var completed: bool = false
var action_only: bool = false
var action_used: bool = false
var hovering: bool = false
var interaction_key: Key = KEY_F
var original_color: Color = Color.WHITE
var original_scale: Vector3 = Vector3.ONE
var hover_tween: Tween = null
var black_layer: CanvasLayer = null
var black_screen: ColorRect = null
var previous_pause: bool = false
var _owns_transition_pause: bool = false
var _transition_generation: int = 0
var _fade_tween: Tween = null


func _ready() -> void:
	# Polling/transition cleanup must also work while this script pauses the game.
	process_mode = Node.PROCESS_MODE_ALWAYS
	monitoring = true
	game_state = get_node_or_null("/root/GameState")
	if prompt == null or game_state == null:
		push_error("%s needs FPrompt (Label3D) and the GameState Autoload." % name)
		set_process(false)
		set_physics_process(false)
		set_process_unhandled_input(false)
		return
	original_color = prompt.modulate
	original_scale = prompt.scale
	prompt.hide()
	# Replace the previous generated 3D click collider with screen hit testing.
	var old_click_area: CollisionObject3D = prompt.get_node_or_null("ClickArea") as CollisionObject3D
	if old_click_area != null:
		old_click_area.input_ray_pickable = false
		old_click_area.collision_layer = 0
		old_click_area.collision_mask = 0
	_configure_interaction()
	task_id = String(game_state.call("normalize_school_task_id", task_id))
	_configure_key()
	_create_black_screen()
	if not body_entered.is_connected(_on_body_entered):
		body_entered.connect(_on_body_entered)
	if not body_exited.is_connected(_on_body_exited):
		body_exited.connect(_on_body_exited)
	if not game_state.is_connected("school_task_updated", _on_task_updated):
		game_state.connect("school_task_updated", _on_task_updated)
	if not game_state.is_connected("school_tasks_reset", _on_school_tasks_reset):
		game_state.connect("school_tasks_reset", _on_school_tasks_reset)
	_check_completion()
	_update_visibility()
	if task_id.is_empty() and target_scene_path.is_empty() and not action_only:
		push_warning("%s: choose Task ID or Target Scene Path in the Inspector." % name)


func _configure_interaction() -> void:
	if not task_id.is_empty() or not target_scene_path.is_empty():
		return
	var text: String = (String(name) + " " + prompt.text).to_lower().replace("_", " ")
	if "get form" in text or "health form" in text:
		task_id = "health_form"
	elif "start activity" in text or "attend class" in text:
		task_id = "attend_class"
	elif "borrow" in text:
		task_id = "borrow_book"
	elif "use computer" in text or "computer activity" in text:
		task_id = "computer_activity"
	elif "eat" in text:
		task_id = "eat_lunch"
	elif "buy" in text or "sleep" in text:
		action_only = true


func _configure_key() -> void:
	var opening: int = prompt.text.find("[")
	if opening < 0:
		return
	var closing: int = prompt.text.find("]", opening + 1)
	if closing <= opening + 1:
		return
	var parsed: Key = OS.find_keycode_from_string(prompt.text.substr(opening + 1, closing - opening - 1).strip_edges())
	if parsed != KEY_NONE:
		interaction_key = parsed


func _is_player(body: Node3D) -> bool:
	return body is CharacterBody3D and (body.is_in_group(&"player") or String(body.name).to_lower().begins_with("characterbody"))


func _physics_process(_delta: float) -> void:
	if busy or prompt == null:
		return
	current_player = null
	for body: Node3D in get_overlapping_bodies():
		if _is_player(body):
			current_player = body as CharacterBody3D
			break
	player_near = is_instance_valid(current_player)
	_update_visibility()


func _process(_delta: float) -> void:
	if prompt == null:
		return
	if not _can_interact() or not prompt.is_visible_in_tree():
		_reset_hover()
		return
	_set_hover(_mouse_over_text())


func _unhandled_input(event: InputEvent) -> void:
	# Any future GUI controls get first choice of mouse clicks.
	if not _can_interact() or not prompt.is_visible_in_tree():
		return
	if event is InputEventKey:
		var key: InputEventKey = event as InputEventKey
		if key.pressed and not key.echo and (key.keycode == interaction_key or key.physical_keycode == interaction_key):
			get_viewport().set_input_as_handled()
			_activate_interaction()
	elif event is InputEventMouseButton:
		var button: InputEventMouseButton = event as InputEventMouseButton
		if button.button_index == MOUSE_BUTTON_LEFT and button.pressed and _mouse_over_text(button.position):
			get_viewport().set_input_as_handled()
			_activate_interaction()


func _can_interact() -> bool:
	if busy or not player_near or game_state == null or get_tree().paused:
		return false
	if bool(game_state.call("is_interaction_busy")):
		return false
	# A door remains usable after an optional attached task has completed.
	if not target_scene_path.is_empty():
		return true
	if action_only:
		return not action_used
	return not task_id.is_empty() and bool(game_state.call("can_complete_school_task", task_id))


func _mouse_over_text(point: Vector2 = Vector2(-1, -1)) -> bool:
	var hovered: Control = get_viewport().gui_get_hovered_control()
	if hovered != null and hovered.mouse_filter != Control.MOUSE_FILTER_IGNORE:
		return false
	if point.x < 0.0:
		point = get_viewport().get_mouse_position()
	return _projected_prompt_rect().has_point(point)


func _projected_prompt_rect() -> Rect2:
	var camera: Camera3D = get_viewport().get_camera_3d()
	if camera == null or camera.is_position_behind(prompt.global_position):
		return Rect2()
	var bounds: AABB = prompt.get_aabb()
	if bounds.size.x <= 0.0 or bounds.size.y <= 0.0:
		var width: float = maxf(prompt.text.length() * prompt.font_size * prompt.pixel_size * 0.55, 0.1)
		var height: float = maxf(prompt.font_size * prompt.pixel_size, 0.1)
		bounds = AABB(Vector3(-width * 0.5, -height * 0.5, -0.01), Vector3(width, height, 0.02))
	bounds = bounds.grow(maxf(0.0, click_padding))
	var transform_for_bounds: Transform3D = prompt.global_transform
	if prompt.billboard == BaseMaterial3D.BILLBOARD_ENABLED:
		transform_for_bounds.basis = camera.global_basis.orthonormalized().scaled(prompt.global_basis.get_scale())
	elif prompt.billboard == BaseMaterial3D.BILLBOARD_FIXED_Y:
		var facing: Vector3 = camera.global_position - prompt.global_position
		facing.y = 0.0
		if facing.length_squared() > 0.0001:
			transform_for_bounds.basis = Basis.looking_at(facing.normalized(), Vector3.UP).scaled(prompt.global_basis.get_scale())
	var minimum: Vector2 = Vector2(INF, INF)
	var maximum: Vector2 = Vector2(-INF, -INF)
	for i: int in range(8):
		var world_point: Vector3 = transform_for_bounds * bounds.get_endpoint(i)
		if camera.is_position_behind(world_point):
			return Rect2()
		var screen_point: Vector2 = camera.unproject_position(world_point)
		minimum = minimum.min(screen_point)
		maximum = maximum.max(screen_point)
	return Rect2(minimum, maximum - minimum).grow(maxf(0.0, mouse_padding))


func _set_hover(value: bool) -> void:
	if hovering == value:
		return
	hovering = value
	if hover_tween != null and hover_tween.is_valid():
		hover_tween.kill()
	Input.set_default_cursor_shape(Input.CURSOR_POINTING_HAND if value else Input.CURSOR_ARROW)
	hover_tween = create_tween().set_parallel(true)
	hover_tween.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
	hover_tween.tween_property(prompt, "modulate", hover_color if value else original_color, 0.15)
	hover_tween.tween_property(prompt, "scale", original_scale * hover_scale if value else original_scale, 0.15)


func _reset_hover() -> void:
	if hover_tween != null and hover_tween.is_valid():
		hover_tween.kill()
	if is_instance_valid(prompt):
		prompt.modulate = original_color
		prompt.scale = original_scale
	if hovering:
		Input.set_default_cursor_shape(Input.CURSOR_ARROW)
	hovering = false


func _create_black_screen() -> void:
	black_layer = CanvasLayer.new()
	black_layer.name = "TaskBlackTransition"
	black_layer.layer = 120
	black_layer.process_mode = Node.PROCESS_MODE_ALWAYS
	add_child(black_layer)
	black_screen = ColorRect.new()
	black_screen.name = "BlackScreen"
	black_screen.color = Color.BLACK
	black_screen.modulate.a = 0.0
	black_screen.mouse_filter = Control.MOUSE_FILTER_STOP
	black_layer.add_child(black_screen)
	black_screen.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	black_screen.hide()


func _activate_interaction() -> void:
	if not _can_interact():
		return
	if not target_scene_path.is_empty() and not ResourceLoader.exists(target_scene_path, "PackedScene"):
		push_error("Scene not found: " + target_scene_path)
		return
	if not bool(game_state.call("try_begin_interaction", self)):
		return
	busy = true
	_transition_generation += 1
	_reset_hover()
	prompt.hide()
	if is_instance_valid(current_player):
		current_player.velocity = Vector3.ZERO
	var unfinished_task: bool = not task_id.is_empty() and not completed
	if unfinished_task or action_only:
		if unfinished_task and not bool(game_state.call("can_complete_school_task", task_id)):
			_release_interaction()
			_update_visibility()
			return
		_run_transition(_transition_generation)
	else:
		_save_current_position()
		_change_scene.call_deferred()


func _start_task() -> void:
	# Compatibility with the name used by the previous task script.
	_activate_interaction()


func _run_transition(generation: int) -> void:
	previous_pause = get_tree().paused
	_owns_transition_pause = true
	get_tree().paused = true
	black_screen.modulate.a = 0.0
	black_screen.show()
	await _fade_to(1.0)
	if generation != _transition_generation:
		return
	await get_tree().create_timer(clampf(transition_duration, 2.0, 4.0), true, false, true).timeout
	if generation != _transition_generation:
		return
	await _fade_to(0.0)
	if generation != _transition_generation:
		return
	black_screen.hide()
	# Release our pause before notifying listeners that the task is complete.
	_release_interaction()
	var accepted: bool = _finish_task()
	_update_visibility()
	if accepted and not target_scene_path.is_empty():
		_save_current_position()
		if bool(game_state.call("try_begin_interaction", self)):
			busy = true
			prompt.hide()
			_change_scene.call_deferred()


func _fade_to(alpha: float) -> void:
	_fade_tween = create_tween()
	_fade_tween.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
	_fade_tween.set_ignore_time_scale(true)
	_fade_tween.tween_property(black_screen, "modulate:a", alpha, clampf(fade_duration, 0.1, 1.0))
	await _fade_tween.finished


func _finish_task() -> bool:
	if action_only:
		action_used = true
		return true
	var accepted: bool = bool(game_state.call("complete_school_task", task_id))
	_check_completion()
	return accepted


func _release_interaction() -> void:
	if _owns_transition_pause:
		get_tree().paused = previous_pause
		_owns_transition_pause = false
	busy = false
	if is_instance_valid(game_state):
		game_state.call("end_interaction", self)


func _check_completion() -> void:
	completed = game_state != null and not task_id.is_empty() and bool(game_state.call("is_school_task_complete", task_id))


func _on_task_updated() -> void:
	_check_completion()
	_update_visibility()


func _on_school_tasks_reset() -> void:
	_transition_generation += 1
	if _fade_tween != null and _fade_tween.is_valid():
		_fade_tween.kill()
	if black_screen != null:
		black_screen.hide()
	_release_interaction()
	completed = false
	action_used = false
	_update_visibility()


func _on_body_entered(body: Node3D) -> void:
	if _is_player(body):
		current_player = body as CharacterBody3D
		player_near = true
		_update_visibility()


func _on_body_exited(body: Node3D) -> void:
	if body == current_player:
		current_player = null
		player_near = false
		_update_visibility()


func _update_visibility() -> void:
	if prompt == null:
		return
	prompt.visible = _can_interact()
	if not prompt.visible:
		_reset_hover()


func _save_current_position() -> void:
	if not is_instance_valid(current_player):
		current_player = get_tree().get_first_node_in_group(&"player") as CharacterBody3D
	var current_scene: Node = get_tree().current_scene
	if game_state != null and current_scene != null and is_instance_valid(current_player):
		game_state.call("save_position", current_scene.scene_file_path, current_player.global_position)


func _change_scene() -> void:
	if target_scene_path.is_empty():
		_release_interaction()
		_update_visibility()
		return
	var transition: Node = get_node_or_null("/root/SceneTransition")
	if transition != null and transition.has_method("transition_to"):
		if not bool(transition.call("transition_to", target_scene_path)):
			_release_interaction()
			_update_visibility()
		return
	# Direct scene changes still work if the optional fade Autoload is absent.
	var error: Error = get_tree().change_scene_to_file(target_scene_path)
	if error != OK:
		push_error("Could not change scene to %s (error %d)." % [target_scene_path, error])
		_release_interaction()
		_update_visibility()


func _exit_tree() -> void:
	_transition_generation += 1
	_release_interaction()
	_reset_hover()
