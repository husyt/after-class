
extends CharacterBody3D

@export var move_speed: float = 1.5
@export var preserve_scene_depth: bool = true
@export var fixed_z_if_needed: float = 1.0
@export var restore_saved_position: bool = true
@export var debug_player: bool = false
@export var original_art_faces_right: bool = true

@onready var sprite: AnimatedSprite3D = get_node_or_null("AnimatedSprite3D") as AnimatedSprite3D
@onready var footsteps_2d: AudioStreamPlayer = get_node_or_null("AudioStreamPlayer") as AudioStreamPlayer
@onready var footsteps_3d: AudioStreamPlayer3D = get_node_or_null("AudioStreamPlayer3D") as AudioStreamPlayer3D

var _game_state: Node = null
var _idle_animation: StringName = &""
var _walk_animation: StringName = &""
var _restoring_position: bool = true
var _scene_depth: float = 0.0
var _reported_busy: bool = false


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_PAUSABLE
	add_to_group(&"player")
	_game_state = get_node_or_null("/root/GameState")

	_scene_depth = global_position.z

	if not preserve_scene_depth:
		_scene_depth = fixed_z_if_needed
		global_position.z = _scene_depth

	visible = true

	if sprite == null:
		push_error("Player: AnimatedSprite3D child not found.")
	elif sprite.sprite_frames == null:
		push_error("Player: AnimatedSprite3D has no SpriteFrames.")
	else:
		sprite.visible = true
		_idle_animation = _find_animation("idle")
		_walk_animation = _find_animation("walk")
		_play_animation(_idle_animation)

	_stop_footsteps()
	call_deferred("_restore_previous_position")


func _physics_process(_delta: float) -> void:
	if _restoring_position or get_tree().paused:
		velocity = Vector3.ZERO
		_stop_footsteps()
		return

	if _interaction_is_busy():
		velocity = Vector3.ZERO
		_play_animation(_idle_animation)
		_stop_footsteps()

		if debug_player and not _reported_busy:
			print("PLAYER MOVEMENT LOCKED")
			_reported_busy = true

		return

	_reported_busy = false

	var direction: float = _get_horizontal_direction()

	velocity = Vector3(
		direction * move_speed,
		0.0,
		0.0
	)

	move_and_slide()
	global_position.z = _scene_depth

	if not is_zero_approx(direction):
		if sprite != null:
			sprite.flip_h = (
				direction < 0.0
				if original_art_faces_right
				else direction > 0.0
			)

		_play_animation(_walk_animation)
		_play_footsteps()
	else:
		_play_animation(_idle_animation)
		_stop_footsteps()


func _get_horizontal_direction() -> float:
	var direction: float = 0.0

	if (
		InputMap.has_action("move_left")
		and InputMap.has_action("move_right")
	):
		direction = Input.get_axis(
			"move_left",
			"move_right"
		)

	if is_zero_approx(direction):
		if (
			Input.is_key_pressed(KEY_A)
			or Input.is_key_pressed(KEY_LEFT)
		):
			direction -= 1.0

		if (
			Input.is_key_pressed(KEY_D)
			or Input.is_key_pressed(KEY_RIGHT)
		):
			direction += 1.0

	return clampf(direction, -1.0, 1.0)


func _find_animation(wanted: String) -> StringName:
	if sprite == null or sprite.sprite_frames == null:
		return &""

	for animation_name: StringName in sprite.sprite_frames.get_animation_names():
		if String(animation_name).to_lower() == wanted.to_lower():
			return animation_name

	return &""


func _play_animation(animation_name: StringName) -> void:
	if sprite == null or animation_name == &"":
		return

	if sprite.animation != animation_name or not sprite.is_playing():
		sprite.play(animation_name)


func _play_footsteps() -> void:
	if footsteps_2d != null and footsteps_2d.stream != null:
		if not footsteps_2d.playing:
			footsteps_2d.play()
	elif footsteps_3d != null and footsteps_3d.stream != null:
		if not footsteps_3d.playing:
			footsteps_3d.play()


func _stop_footsteps() -> void:
	if footsteps_2d != null and footsteps_2d.playing:
		footsteps_2d.stop()

	if footsteps_3d != null and footsteps_3d.playing:
		footsteps_3d.stop()


func _interaction_is_busy() -> bool:
	if _game_state == null:
		return false

	if not _game_state.has_method("is_interaction_busy"):
		return false

	return bool(_game_state.call("is_interaction_busy"))


func _restore_previous_position() -> void:
	await get_tree().process_frame

	if not is_inside_tree():
		return

	var restored: bool = false
	var scene: Node = get_tree().current_scene

	if (
		scene != null
		and _game_state != null
		and not scene.scene_file_path.is_empty()
	):
		var scene_path: String = scene.scene_file_path

		if _game_state.has_method("take_spawn_marker"):
			var marker_name: String = String(
				_game_state.call(
					"take_spawn_marker",
					scene_path
				)
			)

			if not marker_name.is_empty():
				var marker: Marker3D = scene.find_child(
					marker_name,
					true,
					false
				) as Marker3D

				if marker != null:
					global_position = Vector3(
						marker.global_position.x,
						marker.global_position.y,
						_scene_depth
					)

					restored = true

					if debug_player:
						print(
							"PLAYER ARRIVED AT MARKER: ",
							marker_name
						)

		if not restored and restore_saved_position:
			if (
				_game_state.has_method("has_position")
				and _game_state.has_method("get_position")
			):
				if bool(
					_game_state.call(
						"has_position",
						scene_path
					)
				):
					var saved: Vector3 = _game_state.call(
						"get_position",
						scene_path
					)

					global_position = Vector3(
						saved.x,
						saved.y,
						_scene_depth
					)

					restored = true

	global_position.z = _scene_depth
	velocity = Vector3.ZERO
	_restoring_position = false

	if debug_player:
		print(
			"PLAYER POSITION READY: ",
			global_position,
			" | restored=",
			restored
		)
