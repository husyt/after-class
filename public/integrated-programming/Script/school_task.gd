extends Area3D

const CLASS_NOTEBOOK_SCENE: String = "res://Scene/class_activity.tscn"
const COMPUTER_ACTIVITY_SCENE: String = "res://Scene/computer_activity.tscn"
const LIBRARY_BORROWING_SCENE: String = "res://Scene/library_borrowing.tscn"
const CLINIC_HEALTH_SCENE: String = "res://Scene/clinic_health_checkup.tscn"
const CLINIC_SLEEP_SCENE: String = "res://Scene/clinic_sleep.tscn"

@export var task_id: String = ""
@export_file("*.tscn") var target_scene_path: String = ""
@export_range(2.0, 4.0, 0.1) var transition_duration: float = 3.0
@export_range(0.1, 1.0, 0.05) var fade_duration: float = 0.45
@export var mouse_padding: float = 15.0
@export var click_padding: float = 0.05
@export var hover_scale: float = 1.15
@export var hover_color: Color = Color(1.0, 0.85, 0.2, 1.0)
@export_group("Cafeteria Audio")
@export var buy_sound: AudioStream
@export var eat_sound: AudioStream
@export_range(-30.0, 6.0, 0.5) var cafeteria_volume_db: float = 0.0
@export var repeat_eating_sound: bool = true
@export var show_eating_progress: bool = true
@export_group("Prompt Visibility")
@export var proximity_fallback: bool = true
@export_range(0.5, 5.0, 0.1) var proximity_horizontal: float = 1.7
@export_range(0.5, 5.0, 0.1) var proximity_vertical: float = 2.0
@export_range(0.5, 10.0, 0.5) var proximity_depth: float = 5.0
@export var show_prompt_through_background: bool = true

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
var _loading_tween: Tween = null
var _cafeteria_action: String = ""
var _eat_audio_active: bool = false
var cafeteria_sound_player: AudioStreamPlayer = null
var eating_progress_container: CenterContainer = null
var eating_progress_label: Label = null
var eating_progress_bar: ProgressBar = null


func _ready() -> void:
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

	var old_click_area: CollisionObject3D = prompt.get_node_or_null("ClickArea") as CollisionObject3D

	if old_click_area != null:
		old_click_area.input_ray_pickable = false
		old_click_area.collision_layer = 0
		old_click_area.collision_mask = 0

	_configure_interaction()
	_ensure_prompt_text()
	if show_prompt_through_background:
		prompt.no_depth_test = true
	task_id = String(game_state.call("normalize_school_task_id", task_id))
	_cafeteria_action = _find_cafeteria_action()
	_configure_key()
	_create_black_screen()
	_create_cafeteria_sound_player()

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
	var text: String = (String(name) + " " + prompt.text).to_lower().replace("_", " ")
	var scene_name: String = ""

	if owner != null:
		scene_name = owner.scene_file_path.get_file().to_lower()

	var classroom_activity: bool = "start activity" in text and (
		scene_name == "classroom.tscn"
		or String(name).to_lower() == "activity"
	)

	if classroom_activity:
		task_id = ""
		target_scene_path = CLASS_NOTEBOOK_SCENE
		action_only = false
		return

	var computer_activity: bool = (
		"use computer" in text
		or ("computer activity" in text and "exit" not in text)
	)

	if computer_activity:
		task_id = ""
		target_scene_path = COMPUTER_ACTIVITY_SCENE
		action_only = false
		return

	if "borrow" in text and "exit" not in text:
		task_id = ""
		target_scene_path = LIBRARY_BORROWING_SCENE
		action_only = false
		return

	if "get form" in text or "health form" in text:
		task_id = ""
		target_scene_path = CLINIC_HEALTH_SCENE
		action_only = false
		return

	if "sleep" in text:
		task_id = ""
		target_scene_path = CLINIC_SLEEP_SCENE
		action_only = false
		return

	if not task_id.is_empty() or not target_scene_path.is_empty():
		return

	var normalized_name: String = String(name).to_lower().replace("_", " ")
	if "apologize" in text or "apolagize" in text or "apologise" in text:
		action_only = true
	elif "enter grounds" in text or "enter school grounds" in text or normalized_name == "enter grounds":
		target_scene_path = "res://Scene/school_grounds.tscn"
		if not ResourceLoader.exists(target_scene_path, "PackedScene"):
			var alternate: String = "res://Scene/school_grounds.tscn.tscn"
			if ResourceLoader.exists(alternate, "PackedScene"):
				target_scene_path = alternate
	elif "exit grounds" in text or "exit school grounds" in text:
		target_scene_path = "res://Scene/school_hallway2.tscn"
	elif normalized_name == "play" or "[f] play" in text or " play " in (" " + text + " "):
		action_only = true
	elif "attend class" in text:
		task_id = "attend_class"
	elif "eat" in text:
		task_id = "eat_lunch"
	elif "buy" in text:
		action_only = true


func _ensure_prompt_text() -> void:
	var current_text: String = prompt.text.strip_edges()
	var normalized_name: String = String(name).to_lower().replace("_", " ")
	if "apolagize" in current_text.to_lower() or "apologize" in current_text.to_lower() or "apologise" in current_text.to_lower():
		current_text = "Apologize"
	elif current_text.is_empty():
		if "apolagize" in normalized_name or "apologize" in normalized_name:
			current_text = "Apologize"
		elif "enter grounds" in normalized_name:
			current_text = "Enter Grounds"
		elif "exit grounds" in normalized_name:
			current_text = "Exit Grounds"
		elif normalized_name == "play":
			current_text = "Play"
		else:
			current_text = String(name).replace("_", " ").capitalize()
	if not current_text.begins_with("["):
		current_text = "[F] " + current_text
	prompt.text = current_text


func _find_cafeteria_action() -> String:
	if not target_scene_path.is_empty():
		return ""

	var trigger_text: String = (" " + String(name) + " " + prompt.text + " ").to_lower().replace("_", " ")

	if task_id == "eat_lunch" or " eat " in trigger_text:
		return "eat"

	if " buy " in trigger_text:
		return "buy"

	return ""


func _create_cafeteria_sound_player() -> void:
	cafeteria_sound_player = AudioStreamPlayer.new()
	cafeteria_sound_player.name = "CafeteriaSoundPlayer"
	cafeteria_sound_player.volume_db = cafeteria_volume_db
	cafeteria_sound_player.process_mode = Node.PROCESS_MODE_ALWAYS
	add_child(cafeteria_sound_player)
	cafeteria_sound_player.finished.connect(_on_cafeteria_sound_finished)


func _play_cafeteria_sound() -> void:
	if cafeteria_sound_player == null:
		return

	var chosen_sound: AudioStream = null
	if _cafeteria_action == "buy":
		chosen_sound = buy_sound
	elif _cafeteria_action == "eat":
		chosen_sound = eat_sound

	if chosen_sound == null:
		var default_path: String = "res://Sounds/buy.wav" if _cafeteria_action == "buy" else "res://Sounds/eat.wav"
		if ResourceLoader.exists(default_path):
			chosen_sound = load(default_path) as AudioStream

	if chosen_sound == null:
		return

	cafeteria_sound_player.volume_db = cafeteria_volume_db
	cafeteria_sound_player.stream = chosen_sound
	_eat_audio_active = _cafeteria_action == "eat"
	cafeteria_sound_player.play()


func _on_cafeteria_sound_finished() -> void:
	if _eat_audio_active and repeat_eating_sound and not _cafeteria_action.is_empty():
		if cafeteria_sound_player != null and cafeteria_sound_player.stream != null:
			cafeteria_sound_player.play()


func _stop_cafeteria_sound() -> void:
	_eat_audio_active = false
	if cafeteria_sound_player != null and cafeteria_sound_player.playing:
		cafeteria_sound_player.stop()


func _configure_key() -> void:
	var opening: int = prompt.text.find("[")

	if opening < 0:
		return

	var closing: int = prompt.text.find("]", opening + 1)

	if closing <= opening + 1:
		return

	var parsed: Key = OS.find_keycode_from_string(
		prompt.text.substr(
			opening + 1,
			closing - opening - 1
		).strip_edges()
	)

	if parsed != KEY_NONE:
		interaction_key = parsed


func _is_player(body: Node3D) -> bool:
	return body is CharacterBody3D and (
		body.is_in_group(&"player")
		or String(body.name).to_lower().begins_with("characterbod")
	)


func _physics_process(_delta: float) -> void:
	if busy or prompt == null:
		return

	current_player = null

	for body: Node3D in get_overlapping_bodies():
		if _is_player(body):
			current_player = body as CharacterBody3D
			break

	if current_player == null and proximity_fallback:
		var player: CharacterBody3D = get_tree().get_first_node_in_group(&"player") as CharacterBody3D
		if is_instance_valid(player) and _is_close_to_player(player):
			current_player = player

	player_near = is_instance_valid(current_player)
	_update_visibility()


func _is_close_to_player(player: CharacterBody3D) -> bool:
	var distance: Vector3 = player.global_position - global_position
	return absf(distance.x) <= proximity_horizontal and absf(distance.y) <= proximity_vertical and absf(distance.z) <= proximity_depth


func _process(_delta: float) -> void:
	if prompt == null:
		return

	if not _can_interact() or not prompt.is_visible_in_tree():
		_reset_hover()
		return

	_set_hover(_mouse_over_text())


func _unhandled_input(event: InputEvent) -> void:
	if not _can_interact() or not prompt.is_visible_in_tree():
		return

	if event is InputEventKey:
		var key: InputEventKey = event as InputEventKey

		if key.pressed and not key.echo and (
			key.keycode == interaction_key
			or key.physical_keycode == interaction_key
		):
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

	if target_scene_path == CLASS_NOTEBOOK_SCENE:
		return not bool(
			game_state.call("is_school_task_complete", "attend_class")
		)

	if target_scene_path == COMPUTER_ACTIVITY_SCENE:
		return not bool(
			game_state.call("is_school_task_complete", "computer_activity")
		)

	if target_scene_path == LIBRARY_BORROWING_SCENE:
		return not bool(
			game_state.call("is_school_task_complete", "borrow_book")
		)

	if target_scene_path == CLINIC_HEALTH_SCENE:
		return not bool(
			game_state.call("is_school_task_complete", "health_form")
		)

	if not target_scene_path.is_empty():
		return true

	if action_only:
		return not action_used

	return not task_id.is_empty() and bool(
		game_state.call("can_complete_school_task", task_id)
	)


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
		var width: float = maxf(
			prompt.text.length() * prompt.font_size * prompt.pixel_size * 0.55,
			0.1
		)
		var height: float = maxf(
			prompt.font_size * prompt.pixel_size,
			0.1
		)

		bounds = AABB(
			Vector3(-width * 0.5, -height * 0.5, -0.01),
			Vector3(width, height, 0.02)
		)

	bounds = bounds.grow(maxf(0.0, click_padding))

	var transform_for_bounds: Transform3D = prompt.global_transform

	if prompt.billboard == BaseMaterial3D.BILLBOARD_ENABLED:
		transform_for_bounds.basis = (
			camera.global_basis.orthonormalized().scaled(
				prompt.global_basis.get_scale()
			)
		)

	elif prompt.billboard == BaseMaterial3D.BILLBOARD_FIXED_Y:
		var facing: Vector3 = camera.global_position - prompt.global_position
		facing.y = 0.0

		if facing.length_squared() > 0.0001:
			transform_for_bounds.basis = Basis.looking_at(
				facing.normalized(),
				Vector3.UP
			).scaled(prompt.global_basis.get_scale())

	var minimum: Vector2 = Vector2(INF, INF)
	var maximum: Vector2 = Vector2(-INF, -INF)

	for i: int in range(8):
		var world_point: Vector3 = transform_for_bounds * bounds.get_endpoint(i)

		if camera.is_position_behind(world_point):
			return Rect2()

		var screen_point: Vector2 = camera.unproject_position(world_point)
		minimum = minimum.min(screen_point)
		maximum = maximum.max(screen_point)

	return Rect2(
		minimum,
		maximum - minimum
	).grow(maxf(0.0, mouse_padding))


func _set_hover(value: bool) -> void:
	if hovering == value:
		return

	hovering = value

	if hover_tween != null and hover_tween.is_valid():
		hover_tween.kill()

	Input.set_default_cursor_shape(
		Input.CURSOR_POINTING_HAND if value else Input.CURSOR_ARROW
	)

	hover_tween = create_tween().set_parallel(true)
	hover_tween.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)

	hover_tween.tween_property(
		prompt,
		"modulate",
		hover_color if value else original_color,
		0.15
	)

	hover_tween.tween_property(
		prompt,
		"scale",
		original_scale * hover_scale if value else original_scale,
		0.15
	)


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
	black_screen.set_anchors_and_offsets_preset(
		Control.PRESET_FULL_RECT
	)
	black_screen.hide()

	eating_progress_container = CenterContainer.new()
	eating_progress_container.name = "EatingProgress"
	eating_progress_container.mouse_filter = Control.MOUSE_FILTER_IGNORE
	eating_progress_container.process_mode = Node.PROCESS_MODE_ALWAYS
	black_layer.add_child(eating_progress_container)
	eating_progress_container.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var content: VBoxContainer = VBoxContainer.new()
	content.custom_minimum_size.x = 370.0
	content.add_theme_constant_override("separation", 18)
	eating_progress_container.add_child(content)

	eating_progress_label = Label.new()
	eating_progress_label.text = "Eating lunch..."
	eating_progress_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	eating_progress_label.add_theme_font_size_override("font_size", 28)
	eating_progress_label.add_theme_color_override("font_color", Color.WHITE)
	content.add_child(eating_progress_label)

	eating_progress_bar = ProgressBar.new()
	eating_progress_bar.custom_minimum_size = Vector2(370.0, 24.0)
	eating_progress_bar.min_value = 0.0
	eating_progress_bar.max_value = 100.0
	eating_progress_bar.value = 0.0
	eating_progress_bar.show_percentage = true
	content.add_child(eating_progress_bar)

	eating_progress_container.hide()


func _activate_interaction() -> void:
	if not _can_interact():
		return

	if not target_scene_path.is_empty() and not ResourceLoader.exists(
		target_scene_path,
		"PackedScene"
	):
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
		if unfinished_task and not bool(
			game_state.call("can_complete_school_task", task_id)
		):
			_release_interaction()
			_update_visibility()
			return

		_run_transition(_transition_generation)

	else:
		_save_current_position()
		_change_scene.call_deferred()


func _start_task() -> void:
	_activate_interaction()


func _run_transition(generation: int) -> void:
	previous_pause = get_tree().paused
	_owns_transition_pause = true
	get_tree().paused = true

	black_screen.modulate.a = 0.0
	black_screen.show()
	eating_progress_container.hide()

	if _cafeteria_action == "buy":
		_play_cafeteria_sound()

	await _fade_to(1.0)

	if generation != _transition_generation or not is_inside_tree():
		return

	var wait_time: float = clampf(transition_duration, 2.0, 4.0)

	if _cafeteria_action == "eat":
		_play_cafeteria_sound()

		if show_eating_progress:
			eating_progress_bar.value = 0.0
			eating_progress_container.show()
			_loading_tween = create_tween()
			_loading_tween.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
			_loading_tween.set_ignore_time_scale(true)
			_loading_tween.tween_property(
				eating_progress_bar,
				"value",
				100.0,
				wait_time
			).set_trans(Tween.TRANS_LINEAR)
			await _loading_tween.finished
		else:
			await get_tree().create_timer(wait_time, true, false, true).timeout
	else:
		await get_tree().create_timer(wait_time, true, false, true).timeout

	if generation != _transition_generation or not is_inside_tree():
		return

	_stop_cafeteria_sound()
	eating_progress_container.hide()

	await _fade_to(0.0)

	if generation != _transition_generation or not is_inside_tree():
		return

	black_screen.hide()
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

	_fade_tween.tween_property(
		black_screen,
		"modulate:a",
		alpha,
		clampf(fade_duration, 0.1, 1.0)
	)

	await _fade_tween.finished


func _finish_task() -> bool:
	if action_only:
		action_used = true
		return true

	var accepted: bool = bool(
		game_state.call("complete_school_task", task_id)
	)

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
	completed = (
		game_state != null
		and not task_id.is_empty()
		and bool(game_state.call("is_school_task_complete", task_id))
	)


func _on_task_updated() -> void:
	_check_completion()
	_update_visibility()


func _on_school_tasks_reset() -> void:
	_transition_generation += 1

	if _fade_tween != null and _fade_tween.is_valid():
		_fade_tween.kill()

	if _loading_tween != null and _loading_tween.is_valid():
		_loading_tween.kill()

	_stop_cafeteria_sound()

	if eating_progress_container != null:
		eating_progress_container.hide()

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
		current_player = (
			get_tree().get_first_node_in_group(&"player") as CharacterBody3D
		)

	var current_scene: Node = get_tree().current_scene

	if (
		game_state != null
		and current_scene != null
		and is_instance_valid(current_player)
	):
		game_state.call(
			"save_position",
			current_scene.scene_file_path,
			current_player.global_position
		)


func _change_scene() -> void:
	if target_scene_path.is_empty():
		_release_interaction()
		_update_visibility()
		return

	var transition: Node = get_node_or_null(
		"/root/SceneTransition"
	)

	if transition != null and transition.has_method("transition_to"):
		if not bool(
			transition.call(
				"transition_to",
				target_scene_path
			)
		):
			_release_interaction()
			_update_visibility()

		return

	var error: Error = get_tree().change_scene_to_file(
		target_scene_path
	)

	if error != OK:
		push_error(
			"Could not change scene to %s (error %d)." % [
				target_scene_path,
				error
			]
		)

		_release_interaction()
		_update_visibility()


func _exit_tree() -> void:
	_transition_generation += 1
	_stop_cafeteria_sound()
	_release_interaction()
	_reset_hover()
