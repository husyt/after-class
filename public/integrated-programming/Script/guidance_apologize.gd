extends Area3D

const APOLOGY_COMPLETE_META_KEY: String = "equalpath_guidance_apology_completed"

@export_file("*.tscn") var warning_scene_path: String = "res://Scene/guidance_warning.tscn"

@onready var prompt: Label3D = get_node_or_null("FPrompt") as Label3D

var _player_inside: CharacterBody3D = null
var _game_state: Node = null
var _busy: bool = false


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_ALWAYS
	monitoring = true
	_game_state = get_node_or_null("/root/GameState")

	if prompt == null:
		push_error("Apolagize needs a direct child called FPrompt (Label3D).")
		set_physics_process(false)
		set_process_unhandled_input(false)
		return

	prompt.text = "[F] Apologize"
	prompt.modulate = Color.WHITE
	prompt.no_depth_test = true
	prompt.hide()

	if not body_entered.is_connected(_on_body_entered):
		body_entered.connect(_on_body_entered)
	if not body_exited.is_connected(_on_body_exited):
		body_exited.connect(_on_body_exited)

	_ensure_player_collision_mask()
	_update_prompt()


func _physics_process(_delta: float) -> void:
	if prompt == null:
		return

	_ensure_player_collision_mask()
	_player_inside = null

	for body: Node3D in get_overlapping_bodies():
		if _is_player(body):
			_player_inside = body as CharacterBody3D
			break

	_update_prompt()


func _on_body_entered(body: Node3D) -> void:
	if _is_player(body):
		_player_inside = body as CharacterBody3D
		_update_prompt()


func _on_body_exited(body: Node3D) -> void:
	if body == _player_inside:
		_player_inside = null
		_update_prompt()


func _is_player(body: Node3D) -> bool:
	return body is CharacterBody3D and (
		body.is_in_group(&"player")
		or String(body.name).to_lower().begins_with("characterbod")
	)


func _ensure_player_collision_mask() -> void:
	var player: CharacterBody3D = (
		get_tree().get_first_node_in_group(&"player") as CharacterBody3D
	)

	if not is_instance_valid(player):
		var current_scene: Node = get_tree().current_scene
		if current_scene != null:
			player = current_scene.find_child("CharacterBod", true, false) as CharacterBody3D

	if is_instance_valid(player) and player.collision_layer != 0:
		collision_mask |= player.collision_layer


func _update_prompt() -> void:
	if prompt == null:
		return

	prompt.visible = (
		is_instance_valid(_player_inside)
		and not _busy
		and not get_tree().paused
		and not _apology_is_complete()
	)


func _can_interact() -> bool:
	return (
		is_instance_valid(_player_inside)
		and not _busy
		and not get_tree().paused
		and prompt != null
		and prompt.is_visible_in_tree()
		and not _apology_is_complete()
	)


func _apology_is_complete() -> bool:
	return (
		_game_state != null
		and bool(_game_state.get_meta(APOLOGY_COMPLETE_META_KEY, false))
	)


func _unhandled_input(event: InputEvent) -> void:
	if not _can_interact():
		return

	if event is InputEventKey:
		var key_event: InputEventKey = event as InputEventKey
		if key_event.pressed and not key_event.echo:
			if key_event.keycode == KEY_F or key_event.physical_keycode == KEY_F:
				get_viewport().set_input_as_handled()
				_begin_apology()

	elif event is InputEventMouseButton:
		var mouse_event: InputEventMouseButton = event as InputEventMouseButton
		if mouse_event.pressed and mouse_event.button_index == MOUSE_BUTTON_LEFT:
			if _is_click_on_prompt(mouse_event.position):
				get_viewport().set_input_as_handled()
				_begin_apology()


func _is_click_on_prompt(mouse_position: Vector2) -> bool:
	if prompt == null or not prompt.is_visible_in_tree():
		return false

	var camera: Camera3D = get_viewport().get_camera_3d()
	if camera == null or camera.is_position_behind(prompt.global_position):
		return false

	var bounds: AABB = prompt.get_aabb()
	var minimum: Vector2 = Vector2(INF, INF)
	var maximum: Vector2 = Vector2(-INF, -INF)

	for corner: int in range(8):
		var world_point: Vector3 = prompt.global_transform * bounds.get_endpoint(corner)
		if camera.is_position_behind(world_point):
			continue
		var screen_point: Vector2 = camera.unproject_position(world_point)
		minimum = minimum.min(screen_point)
		maximum = maximum.max(screen_point)

	if minimum.x != INF and maximum.x > minimum.x:
		if Rect2(minimum, maximum - minimum).grow(18.0).has_point(mouse_position):
			return true

	var screen_center: Vector2 = camera.unproject_position(prompt.global_position)
	return Rect2(screen_center - Vector2(105.0, 32.0), Vector2(210.0, 64.0)).has_point(mouse_position)


func _begin_apology() -> void:
	if not _can_interact():
		return

	if not ResourceLoader.exists(warning_scene_path, "PackedScene"):
		push_error("Missing Guidance Warning scene: " + warning_scene_path)
		return

	if _game_state != null and _game_state.has_method("try_begin_interaction"):
		if not bool(_game_state.call("try_begin_interaction", self)):
			return

	_busy = true
	_update_prompt()

	var current_scene: Node = get_tree().current_scene
	if is_instance_valid(_player_inside) and current_scene != null and _game_state != null:
		if _game_state.has_method("save_position"):
			_game_state.call("save_position", current_scene.scene_file_path, _player_inside.global_position)

	_open_warning_scene.call_deferred()


func _open_warning_scene() -> void:
	var transition: Node = get_node_or_null("/root/SceneTransition")

	if transition != null and transition.has_method("transition_to"):
		if bool(transition.call("transition_to", warning_scene_path)):
			return

	var result: Error = get_tree().change_scene_to_file(warning_scene_path)
	if result != OK:
		push_error("Could not open Guidance Warning scene. Error: %d" % result)
		_busy = false
		if _game_state != null and _game_state.has_method("end_interaction"):
			_game_state.call("end_interaction", self)
		_update_prompt()


func _exit_tree() -> void:
	if is_instance_valid(_game_state) and _game_state.has_method("end_interaction"):
		_game_state.call("end_interaction", self)
