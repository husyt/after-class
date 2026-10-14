extends CharacterBody3D
## EqualPath - Reusable school NPC controller for Godot 4.
## Attach to CharacterBod (CharacterBody3D), whose child is AnimatedSprite3D.
## Supports Idle/breathing and optional horizontal patrol.

enum Behavior { IDLE_ONLY, WALK_PATROL }

@export var behavior: Behavior = Behavior.IDLE_ONLY

@export_group("Walking")
@export_range(0.1, 5.0, 0.1) var walk_speed: float = 0.8
@export_range(0.1, 10.0, 0.1) var patrol_half_distance: float = 1.5
@export_range(0.0, 5.0, 0.1) var wait_at_ends: float = 0.8
@export var start_facing_right: bool = true
@export var frames_face_right: bool = true
@export var use_physics_collision: bool = false

@export_group("Animation")
@export_range(0.1, 3.0, 0.1) var idle_animation_speed: float = 1.0
@export_range(0.1, 3.0, 0.1) var walk_animation_speed: float = 1.0

@onready var sprite: AnimatedSprite3D = get_node_or_null("AnimatedSprite3D") as AnimatedSprite3D

var _origin: Vector3 = Vector3.ZERO
var _direction: float = 1.0
var _wait_remaining: float = 0.0
var _idle_name: StringName = &""
var _walk_name: StringName = &""
var _has_valid_collision: bool = false


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_PAUSABLE
	_origin = global_position
	_direction = 1.0 if start_facing_right else -1.0
	
	if sprite == null:
		push_error("NPC %s needs a direct child named AnimatedSprite3D." % name)
		set_physics_process(false)
		return
	if sprite.sprite_frames == null:
		push_error("NPC %s needs SpriteFrames assigned to AnimatedSprite3D." % name)
		set_physics_process(false)
		return
	
	_idle_name = _find_animation(["Idle", "Breathing", "Breathe"])
	_walk_name = _find_animation(["Walk", "Walking"])
	
	if _idle_name == &"":
		push_warning("NPC %s: Add an Idle animation (or Breathing) to SpriteFrames." % name)
	if behavior == Behavior.WALK_PATROL and _walk_name == &"":
		push_warning("NPC %s: WALK_PATROL needs a Walk animation. Remaining idle." % name)
	
	_has_valid_collision = _has_enabled_collision_shape()
	if use_physics_collision and not _has_valid_collision:
		push_warning("NPC %s: Add CollisionShape3D to enable physics collisions. Using visual movement." % name)
	
	_set_facing()
	_play_idle()


func _physics_process(delta: float) -> void:
	if sprite == null or sprite.sprite_frames == null:
		return
	
	if behavior != Behavior.WALK_PATROL or _walk_name == &"":
		velocity = Vector3.ZERO
		_play_idle()
		return
	
	if _wait_remaining > 0.0:
		_wait_remaining = maxf(0.0, _wait_remaining - delta)
		velocity = Vector3.ZERO
		_play_idle()
		return
	
	var left_limit: float = _origin.x - patrol_half_distance
	var right_limit: float = _origin.x + patrol_half_distance
	var next_x: float = clampf(
		global_position.x + _direction * walk_speed * delta,
		left_limit,
		right_limit
	)
	
	if use_physics_collision and _has_valid_collision:
		velocity = Vector3(_direction * walk_speed, 0.0, 0.0)
		move_and_slide()
		global_position.x = clampf(global_position.x, left_limit, right_limit)
	else:
		velocity = Vector3.ZERO
		var new_position: Vector3 = global_position
		new_position.x = next_x
		global_position = new_position
	
	# Keep the NPC on the same horizontal 2.5D plane it was placed on.
	var final_position: Vector3 = global_position
	final_position.y = _origin.y
	final_position.z = _origin.z
	global_position = final_position
	
	_set_facing()
	_play_walk()
	
	var at_right: bool = _direction > 0.0 and global_position.x >= right_limit - 0.001
	var at_left: bool = _direction < 0.0 and global_position.x <= left_limit + 0.001
	if at_right or at_left:
		_direction *= -1.0
		_wait_remaining = wait_at_ends
		_set_facing()
		_play_idle()


func _find_animation(candidates: Array[String]) -> StringName:
	for candidate: String in candidates:
		for available: StringName in sprite.sprite_frames.get_animation_names():
			if String(available).to_lower() == candidate.to_lower():
				return available
	return &""


func _set_facing() -> void:
	if sprite != null:
		# Assumes source frames face right unless frames_face_right is disabled.
		sprite.flip_h = (_direction < 0.0) if frames_face_right else (_direction > 0.0)


func _play_idle() -> void:
	_play_animation(_idle_name, idle_animation_speed)


func _play_walk() -> void:
	_play_animation(_walk_name, walk_animation_speed)


func _play_animation(animation_name: StringName, speed: float) -> void:
	if sprite == null or animation_name == &"":
		return
	sprite.speed_scale = speed
	if sprite.animation != animation_name or not sprite.is_playing():
		sprite.play(animation_name)


func _has_enabled_collision_shape() -> bool:
	for child: Node in get_children():
		if child is CollisionShape3D and not (child as CollisionShape3D).disabled:
			if (child as CollisionShape3D).shape != null:
				return true
	return false
