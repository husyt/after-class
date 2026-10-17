
extends CharacterBody3D

@export_group("Walking")
@export_range(0.1, 8.0, 0.1) var walk_speed: float = 1.2
@export_range(0.0, 2.0, 0.05) var random_speed_variation: float = 0.15
@export var original_art_faces_right: bool = true
@export var random_start_direction: bool = true
@export_enum("Right", "Left") var starting_direction: int = 0

@export_group("Random Turns")
@export var random_turns_enabled: bool = true
@export_range(0.0, 1.0, 0.05) var turn_chance: float = 0.55
@export_range(0.5, 30.0, 0.5) var turn_interval_min: float = 3.0
@export_range(0.5, 30.0, 0.5) var turn_interval_max: float = 7.0

@export_group("Time Between Appearances")
@export var wait_before_first_appearance: bool = true
@export_range(0.0, 30.0, 0.5) var first_delay_min: float = 0.0
@export_range(0.0, 30.0, 0.5) var first_delay_max: float = 5.0
@export_range(0.0, 60.0, 0.5) var repeat_delay_min: float = 3.0
@export_range(0.0, 60.0, 0.5) var repeat_delay_max: float = 8.0
@export_range(0.5, 15.0, 0.5) var offscreen_margin: float = 3.0

@export_group("Scene Boundaries")
@export var use_camera_stop_markers: bool = true
@export var left_stop_name: StringName = &"CameraLeftStop"
@export var right_stop_name: StringName = &"CameraRightStop"
@export var fallback_left_x: float = -15.0
@export var fallback_right_x: float = 15.0

@export_group("Debug")
@export var debug_npc: bool = false

@onready var sprite: AnimatedSprite3D = get_node_or_null("AnimatedSprite3D") as AnimatedSprite3D

var _rng: RandomNumberGenerator = RandomNumberGenerator.new()
var _left_x: float = 0.0
var _right_x: float = 0.0
var _fixed_y: float = 0.0
var _fixed_z: float = 0.0
var _direction: float = 1.0
var _current_speed: float = 1.2
var _turn_time_left: float = 0.0
var _appearance_time_left: float = 0.0
var _walk_animation: StringName = &""
var _waiting: bool = false
var _initialized: bool = false


func _enter_tree() -> void:
	collision_layer = 0
	collision_mask = 0
	remove_from_group(&"player")
	for child: Node in get_children():
		if child is CollisionShape3D:
			(child as CollisionShape3D).set_deferred("disabled", true)


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_PAUSABLE
	collision_layer = 0
	collision_mask = 0
	remove_from_group(&"player")
	velocity = Vector3.ZERO
	_rng.randomize()
	_fixed_y = global_position.y
	_fixed_z = global_position.z

	if sprite == null or sprite.sprite_frames == null:
		push_error("Walking NPC needs an AnimatedSprite3D with SpriteFrames: " + name)
		set_physics_process(false)
		return

	_walk_animation = _find_walk_animation()
	if _walk_animation == &"":
		push_warning("Walking NPC needs a Walk animation: " + name)

	if wait_before_first_appearance:
		hide()

	call_deferred("_initialize_walking")


func _initialize_walking() -> void:
	if not is_inside_tree():
		return

	_left_x = minf(fallback_left_x, fallback_right_x)
	_right_x = maxf(fallback_left_x, fallback_right_x)

	if use_camera_stop_markers:
		var scene: Node = get_tree().current_scene
		if scene != null:
			var left_marker: Marker3D = scene.find_child(String(left_stop_name), true, false) as Marker3D
			var right_marker: Marker3D = scene.find_child(String(right_stop_name), true, false) as Marker3D
			if left_marker != null and right_marker != null:
				_left_x = minf(left_marker.global_position.x, right_marker.global_position.x)
				_right_x = maxf(left_marker.global_position.x, right_marker.global_position.x)
			elif debug_npc:
				print(name, ": using fallback NPC boundaries")

	if _right_x - _left_x < 0.5:
		push_error("Walking NPC: left/right boundaries are too close: " + name)
		set_physics_process(false)
		return

	_direction = _choose_direction()
	_current_speed = _choose_speed()
	_schedule_next_turn()
	_update_facing()
	_initialized = true

	if wait_before_first_appearance:
		_waiting = true
		_appearance_time_left = _random_seconds(first_delay_min, first_delay_max)
	else:
		_waiting = false
		show()
		_play_walk()


func _physics_process(delta: float) -> void:
	if not _initialized:
		return

	if _waiting:
		_appearance_time_left -= delta
		if _appearance_time_left <= 0.0:
			_spawn_from_edge()
		return

	var pos: Vector3 = global_position
	pos.x += _direction * _current_speed * delta
	pos.y = _fixed_y
	pos.z = _fixed_z
	global_position = pos

	if (_direction > 0.0 and pos.x > _right_x + offscreen_margin) or (_direction < 0.0 and pos.x < _left_x - offscreen_margin):
		_begin_appearance_gap()
		return

	if random_turns_enabled:
		_turn_time_left -= delta
		if _turn_time_left <= 0.0:
			if _rng.randf() < turn_chance:
				_direction *= -1.0
				_update_facing()
				if debug_npc:
					print(name, ": changed direction")
			_schedule_next_turn()

	if _walk_animation != &"" and not sprite.is_playing():
		_play_walk()


func _begin_appearance_gap() -> void:
	_waiting = true
	_appearance_time_left = _random_seconds(repeat_delay_min, repeat_delay_max)
	hide()
	if debug_npc:
		print(name, ": offscreen; next appearance in ", snappedf(_appearance_time_left, 0.1), " seconds")


func _spawn_from_edge() -> void:
	_direction = _choose_direction()
	_current_speed = _choose_speed()
	_schedule_next_turn()

	var pos: Vector3 = global_position
	pos.x = _left_x - offscreen_margin if _direction > 0.0 else _right_x + offscreen_margin
	pos.y = _fixed_y
	pos.z = _fixed_z
	global_position = pos

	_update_facing()
	_waiting = false
	show()
	_play_walk()

	if debug_npc:
		print(name, ": appeared at X=", pos.x, " direction=", _direction)


func _choose_direction() -> float:
	if random_start_direction:
		return 1.0 if _rng.randi_range(0, 1) == 0 else -1.0
	return 1.0 if starting_direction == 0 else -1.0


func _choose_speed() -> float:
	return maxf(0.1, walk_speed + _rng.randf_range(-random_speed_variation, random_speed_variation))


func _random_seconds(min_seconds: float, max_seconds: float) -> float:
	return _rng.randf_range(minf(min_seconds, max_seconds), maxf(min_seconds, max_seconds))


func _schedule_next_turn() -> void:
	_turn_time_left = _random_seconds(turn_interval_min, turn_interval_max)


func _update_facing() -> void:
	if sprite != null:
		sprite.flip_h = (_direction < 0.0) if original_art_faces_right else (_direction > 0.0)


func _find_walk_animation() -> StringName:
	for candidate: String in ["walk", "walking", "run", "running"]:
		for animation_name: StringName in sprite.sprite_frames.get_animation_names():
			if String(animation_name).to_lower() == candidate:
				return animation_name
	return &""


func _play_walk() -> void:
	if sprite == null or _walk_animation == &"":
		return
	if sprite.animation != _walk_animation or not sprite.is_playing():
		sprite.play(_walk_animation)
