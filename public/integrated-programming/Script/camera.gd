
extends Camera3D

# ============================================
# EQUALPATH - SIDE SCROLLING CAMERA
# ============================================

@export var follow_speed: float = 8.0
@export var smooth_camera: bool = true
@export var horizontal_offset: float = 0.0

@export var use_camera_limits: bool = true
@export var edge_padding: float = 0.05

@export var preserve_initial_horizontal_offset: bool = false
@export var correct_player_behind_camera: bool = true

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
		push_error(
            "Camera3D must be a child of CharacterBody3D."
		)
		return

	var initial_transform: Transform3D = global_transform

	var original_offset_x: float = (
		global_position.x - player.global_position.x
	)

	top_level = true
	global_transform = initial_transform

	fixed_y = global_position.y
	fixed_z = global_position.z

	if preserve_initial_horizontal_offset:
		starting_offset_x = original_offset_x
	else:
		starting_offset_x = 0.0

	process_priority = 100

	make_current()

	call_deferred("_initialize_camera")


func _initialize_camera() -> void:

	await get_tree().process_frame

	if not is_inside_tree():
		return

	if not is_instance_valid(player):
		return

	var scene: Node = get_tree().current_scene

	if scene != null:

		left_marker = (
			scene.get_node_or_null("CameraLeftStop")
			as Marker3D
		)

		right_marker = (
			scene.get_node_or_null("CameraRightStop")
			as Marker3D
		)

	if use_camera_limits:

		if left_marker == null or right_marker == null:
			push_warning(
                "Add CameraLeftStop and CameraRightStop "
				+ "Marker3D nodes to the scene root."
			)

	if projection != PROJECTION_ORTHOGONAL:
		push_warning(
            "Use Orthogonal projection for accurate "
			+ "background edge limits."
		)

	# Keep player visible if camera was
	# accidentally positioned too close.

	if correct_player_behind_camera:

		var sprite: AnimatedSprite3D = (
			player.get_node_or_null("AnimatedSprite3D")
			as AnimatedSprite3D
		)

		if sprite != null:

			var forward: Vector3 = -global_basis.z.normalized()

			var depth: float = (
				sprite.global_position - global_position
			).dot(forward)

			if depth < 1.5:

				global_position -= forward * (1.5 - depth)

				fixed_y = global_position.y
				fixed_z = global_position.z

	camera_ready = true

	_update_camera(0.0, true)


func _process(delta: float) -> void:

	if not camera_ready:
		return

	if not is_instance_valid(player):
		return

	_update_camera(delta, false)


# ============================================
# CAMERA VISIBLE WIDTH
# ============================================

func _get_half_camera_width() -> float:

	var viewport_size: Vector2 = (
		get_viewport().get_visible_rect().size
	)

	if viewport_size.y <= 0.0:
		return size * 0.5

	var visible_width: float = size

	if keep_aspect == Camera3D.KEEP_HEIGHT:

		var aspect_ratio: float = (
			viewport_size.x / viewport_size.y
		)

		visible_width = size * aspect_ratio

	return visible_width * 0.5


# ============================================
# CAMERA MOVEMENT AND LIMITS
# ============================================

func _update_camera(
	delta: float,
	instant: bool
) -> void:

	var target_x: float = (
		player.global_position.x
		+ starting_offset_x
		+ horizontal_offset
	)

	var limits_available: bool = (
		use_camera_limits
		and is_instance_valid(left_marker)
		and is_instance_valid(right_marker)
	)

	var min_camera_x: float = 0.0
	var max_camera_x: float = 0.0

	if limits_available:

		var image_left: float = minf(
			left_marker.global_position.x,
			right_marker.global_position.x
		)

		var image_right: float = maxf(
			left_marker.global_position.x,
			right_marker.global_position.x
		)

		var half_width: float = (
			_get_half_camera_width()
		)

		# Keep the ENTIRE camera view
		# inside the background edges.

		min_camera_x = (
			image_left
			+ half_width
			+ edge_padding
		)

		max_camera_x = (
			image_right
			- half_width
			- edge_padding
		)

		if min_camera_x > max_camera_x:

			# Background is narrower than
			# the camera's visible area.
			target_x = (
				image_left + image_right
			) * 0.5

			min_camera_x = target_x
			max_camera_x = target_x

		else:

			target_x = clampf(
				target_x,
				min_camera_x,
				max_camera_x
			)

	var target_position: Vector3 = global_position

	if instant or not smooth_camera:

		target_position.x = target_x

	else:

		var blend: float = (
			1.0 - exp(
				-maxf(0.0, follow_speed) * delta
			)
		)

		target_position.x = lerpf(
			global_position.x,
			target_x,
			blend
		)

	if limits_available:

		target_position.x = clampf(
			target_position.x,
			min_camera_x,
			max_camera_x
		)

	# Lock vertical and depth positions.
	target_position.y = fixed_y
	target_position.z = fixed_z

	global_position = target_position


func _exit_tree() -> void:

	camera_ready = false
