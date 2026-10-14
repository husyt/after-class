extends Camera3D
## Attach to the Camera3D directly below the CharacterBody3D.
## Default troubleshooting mode: follows the character without marker limits.

@export var follow_speed: float = 8.0
@export var horizontal_offset: float = 0.0
@export var smooth_camera: bool = true
@export var use_camera_limits: bool = false
@export var preserve_initial_horizontal_offset: bool = false
@export var correct_player_behind_camera: bool = true
@export var debug_camera: bool = true

var player: CharacterBody3D = null
var left_marker: Marker3D = null
var right_marker: Marker3D = null
var fixed_y: float = 0.0
var fixed_z: float = 0.0
var starting_offset_x: float = 0.0
var camera_ready: bool = false


func _ready() -> void:
	player = get_parent() as CharacterBody3D
	if player == null:
		push_error("Camera3D must be directly inside CharacterBody3D.")
		return

	var initial_transform: Transform3D = global_transform
	var original_offset_x: float = global_position.x - player.global_position.x

	top_level = true
	global_transform = initial_transform
	fixed_y = global_position.y
	fixed_z = global_position.z
	starting_offset_x = original_offset_x if preserve_initial_horizontal_offset else 0.0
	process_priority = 100
	make_current()
	call_deferred("_initialize_camera")


func _initialize_camera() -> void:
	await get_tree().process_frame
	if not is_inside_tree() or not is_instance_valid(player):
		return

	var scene: Node = get_tree().current_scene
	if scene != null:
		left_marker = scene.get_node_or_null("CameraLeftStop") as Marker3D
		right_marker = scene.get_node_or_null("CameraRightStop") as Marker3D

	# If a previous player script moved the sprite behind the camera,
	# move the camera backwards along its viewing axis to see the player.
	if correct_player_behind_camera:
		var sprite: AnimatedSprite3D = player.get_node_or_null("AnimatedSprite3D") as AnimatedSprite3D
		if sprite != null:
			var forward: Vector3 = -global_basis.z.normalized()
			var depth: float = (sprite.global_position - global_position).dot(forward)
			if depth < 1.5:
				global_position -= forward * (1.5 - depth)
				fixed_y = global_position.y
				fixed_z = global_position.z
				if debug_camera:
					print("CAMERA DEPTH CORRECTED: sprite was too close or behind camera")

	camera_ready = true
	_update_camera(0.0, true)

	if debug_camera:
		print("CAMERA READY at ", global_position,
			" | player at ", player.global_position,
			" | limits enabled=", use_camera_limits)


func _process(delta: float) -> void:
	if camera_ready and is_instance_valid(player):
		_update_camera(delta, false)


func _update_camera(delta: float, instant: bool) -> void:
	var target_x: float = player.global_position.x + starting_offset_x + horizontal_offset
	var limits_available: bool = (
		use_camera_limits
		and is_instance_valid(left_marker)
		and is_instance_valid(right_marker)
	)

	var left_limit: float = 0.0
	var right_limit: float = 0.0
	if limits_available:
		left_limit = minf(left_marker.global_position.x, right_marker.global_position.x)
		right_limit = maxf(left_marker.global_position.x, right_marker.global_position.x)
		target_x = clampf(target_x, left_limit, right_limit)

	var target_position: Vector3 = global_position
	if instant or not smooth_camera:
		target_position.x = target_x
	else:
		var blend: float = 1.0 - exp(-maxf(0.0, follow_speed) * delta)
		target_position.x = lerpf(global_position.x, target_x, blend)

	if limits_available:
		target_position.x = clampf(target_position.x, left_limit, right_limit)

	target_position.y = fixed_y
	target_position.z = fixed_z
	global_position = target_position


func _exit_tree() -> void:
	camera_ready = false
