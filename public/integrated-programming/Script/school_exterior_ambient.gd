
extends Node

@export_group("Scene Boundaries")
@export var left_edge_marker: NodePath = ^"../CameraLeftStop"
@export var right_edge_marker: NodePath = ^"../CameraRightStop"
@export_range(0.0, 20.0, 0.1) var student_offscreen_margin: float = 2.0
@export_range(0.0, 30.0, 0.1) var tricycle_offscreen_margin: float = 4.0

@export_group("Student Instances")
@export var student_paths: Array[NodePath] = [
	^"../Character2",
	^"../Character3",
	^"../Character4",
	^"../Character5"
]
@export var student_speeds: Array[float] = [1.1, 1.3, 1.0, 1.2]
@export var student_directions: Array[int] = [1, 1, 1, 1]
@export var student_art_faces_right: bool = true

@export_group("Tricycle")
@export var tricycle_path: NodePath = ^"../Tricycle"
@export_range(0.1, 20.0, 0.1) var tricycle_speed: float = 3.0
@export var tricycle_direction: int = 1
@export var tricycle_art_faces_right: bool = true

@export_group("Options")
@export var disable_ambient_collisions: bool = true
@export var disable_old_npc_and_vehicle_controllers: bool = true
@export var debug_ambient: bool = false

var _left_x: float = 0.0
var _right_x: float = 0.0
var _actors: Array[Dictionary] = []
var _initialized: bool = false


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_PAUSABLE
	call_deferred("_initialize")


func _initialize() -> void:
	var left: Marker3D = get_node_or_null(left_edge_marker) as Marker3D
	var right: Marker3D = get_node_or_null(right_edge_marker) as Marker3D

	if left == null or right == null:
		push_error("AmbientController: assign Left Edge Marker and Right Edge Marker. Use the scene's CameraLeftStop and CameraRightStop, or separate Marker3D nodes.")
		set_physics_process(false)
		return

	_left_x = minf(left.global_position.x, right.global_position.x)
	_right_x = maxf(left.global_position.x, right.global_position.x)

	if _right_x - _left_x < 0.1:
		push_error("AmbientController: boundary markers need different X positions.")
		set_physics_process(false)
		return

	var playable_character: Node = get_parent().get_node_or_null("Character")

	for i in range(student_paths.size()):
		var node: Node3D = get_node_or_null(student_paths[i]) as Node3D
		if node == null:
			push_warning("AmbientController: student node missing at " + str(student_paths[i]))
			continue
		if node == playable_character:
			push_warning("AmbientController: refusing to control main playable Character.")
			continue

		var speed: float = 1.1
		if i < student_speeds.size():
			speed = maxf(0.1, student_speeds[i])

		var direction: int = 1
		if i < student_directions.size() and student_directions[i] < 0:
			direction = -1

		_register_actor(
			node, speed, direction, student_offscreen_margin,
			false, student_art_faces_right
		)

	var tricycle: Node3D = get_node_or_null(tricycle_path) as Node3D
	if tricycle != null and tricycle != playable_character:
		_register_actor(
			tricycle, tricycle_speed,
			-1 if tricycle_direction < 0 else 1,
			tricycle_offscreen_margin, true, tricycle_art_faces_right
		)
	else:
		push_warning("AmbientController: Tricycle not found at " + str(tricycle_path))

	_initialized = true
	if debug_ambient:
		print("SCHOOL EXTERIOR: ", _actors.size(), " ambient actors looping; player and camera untouched.")


func _register_actor(
	actor: Node3D,
	speed: float,
	direction: int,
	margin: float,
	is_vehicle: bool,
	art_faces_right: bool
) -> void:
	if disable_old_npc_and_vehicle_controllers or disable_ambient_collisions:
		_prepare_ambient_subtree(actor)

	actor.show()
	var animated_sprite: AnimatedSprite3D = _find_animated_sprite(actor)
	if animated_sprite != null:
		animated_sprite.show()
		animated_sprite.flip_h = (direction < 0) if art_faces_right else (direction > 0)
		var animation: StringName = _find_animation(
			animated_sprite,
			["drive", "driving", "move", "moving", "walk"]
			if is_vehicle else
			["walk", "walking", "run"]
		)
		if animation != &"":
			animated_sprite.play(animation)
		elif animated_sprite.sprite_frames != null and not animated_sprite.is_playing():
			animated_sprite.play()

	_actors.append({
		"node": actor,
		"speed": maxf(0.1, speed),
		"direction": direction,
		"margin": maxf(0.0, margin),
		"fixed_y": actor.global_position.y,
		"fixed_z": actor.global_position.z
	})

	if debug_ambient:
		print("AMBIENT LOOP: ", actor.name, " speed=", speed, " direction=", direction)


func _prepare_ambient_subtree(node: Node) -> void:
	if disable_old_npc_and_vehicle_controllers:
		if node is Camera3D:
			var camera: Camera3D = node as Camera3D
			camera.current = false
			camera.process_mode = Node.PROCESS_MODE_DISABLED
		elif node.get_script() != null and not node is AnimatedSprite3D:
			node.set_process(false)
			node.set_physics_process(false)
			node.set_process_input(false)
			node.set_process_unhandled_input(false)

		if node.is_in_group("player"):
			node.remove_from_group("player")

	if node is CharacterBody3D:
		(node as CharacterBody3D).velocity = Vector3.ZERO
		(node as CharacterBody3D).show()

	if disable_ambient_collisions and node is CollisionObject3D:
		var collision_object: CollisionObject3D = node as CollisionObject3D
		collision_object.collision_layer = 0
		collision_object.collision_mask = 0

	for child: Node in node.get_children():
		_prepare_ambient_subtree(child)


func _find_animated_sprite(root: Node) -> AnimatedSprite3D:
	if root is AnimatedSprite3D:
		return root as AnimatedSprite3D
	for child: Node in root.get_children():
		var found: AnimatedSprite3D = _find_animated_sprite(child)
		if found != null:
			return found
	return null


func _find_animation(sprite: AnimatedSprite3D, candidates: Array[String]) -> StringName:
	if sprite.sprite_frames == null:
		return &""
	for candidate: String in candidates:
		for available in sprite.sprite_frames.get_animation_names():
			if String(available).to_lower() == candidate:
				return StringName(available)
	return &""


func _physics_process(delta: float) -> void:
	if not _initialized:
		return

	for actor_info: Dictionary in _actors:
		var actor: Node3D = actor_info["node"] as Node3D
		if not is_instance_valid(actor):
			continue

		var direction: int = int(actor_info["direction"])
		var margin: float = float(actor_info["margin"])
		var speed: float = float(actor_info["speed"])
		var next_position: Vector3 = actor.global_position
		next_position.x += float(direction) * speed * delta

		if direction > 0 and next_position.x >= _right_x + margin:
			next_position.x = _left_x - margin
		elif direction < 0 and next_position.x <= _left_x - margin:
			next_position.x = _right_x + margin

		next_position.y = float(actor_info["fixed_y"])
		next_position.z = float(actor_info["fixed_z"])
		actor.global_position = next_position
