extends CharacterBody3D
## Shared 2.5D traffic controller for Jeep and Tricycle.
## Attach to CharacterBod (CharacterBody3D) in BOTH vehicle scenes.
## Expected child: AnimatedSprite3D with Idle, Drive and Boarding animations.
## Vehicles never join the "player" group or change the game camera.

enum MovementMode {
	IDLE_ONLY,
	DRIVE_ONCE,
	DRIVE_LOOP,
	PATROL,
	BOARD_THEN_DRIVE
}

enum MotionState { IDLE, WAITING, BOARDING, DRIVING }
enum NextAction { BEGIN, LOOP_RESTART, PATROL_REVERSE }

signal boarding_finished
signal reached_destination

@export var movement_mode: MovementMode = MovementMode.DRIVE_LOOP
@export_range(0.1, 15.0, 0.1) var drive_speed: float = 1.5
@export_range(0.5, 100.0, 0.5) var travel_distance: float = 12.0
@export var start_facing_right: bool = true
@export var flip_sprite_with_direction: bool = true
@export_range(0.0, 10.0, 0.1) var start_delay: float = 0.0
@export_range(0.0, 10.0, 0.1) var wait_at_end: float = 1.0
@export_range(0.1, 10.0, 0.1) var boarding_duration: float = 2.0

@onready var sprite: AnimatedSprite3D = get_node_or_null("AnimatedSprite3D") as AnimatedSprite3D

var _start_position: Vector3 = Vector3.ZERO
var _base_direction: float = 1.0
var _direction: float = 1.0
var _target_x: float = 0.0
var _traveling_outward: bool = true
var _state: MotionState = MotionState.IDLE
var _next_action: NextAction = NextAction.BEGIN
var _seconds_left: float = 0.0
var _idle_animation: StringName = &""
var _drive_animation: StringName = &""
var _boarding_animation: StringName = &""


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_PAUSABLE
	_start_position = global_position
	_base_direction = 1.0 if start_facing_right else -1.0
	_direction = _base_direction

	if sprite == null or sprite.sprite_frames == null:
		push_error("Vehicle needs an AnimatedSprite3D child with SpriteFrames: " + name)
		set_physics_process(false)
		return

	_idle_animation = _find_animation("Idle")
	_drive_animation = _find_animation("Drive")
	_boarding_animation = _find_animation("Boarding")

	if _idle_animation == &"":
		push_warning("Missing Idle animation on " + name)
	if _drive_animation == &"":
		push_warning("Missing Drive animation on " + name)
	if _boarding_animation == &"":
		push_warning("Missing Boarding animation on " + name)

	_play(_idle_animation)
	_set_direction(_base_direction)

	if movement_mode == MovementMode.IDLE_ONLY:
		return
	if start_delay > 0.0:
		_wait(NextAction.BEGIN, start_delay)
	else:
		_begin_initial_action()


func _physics_process(delta: float) -> void:
	match _state:
		MotionState.WAITING:
			_seconds_left -= delta
			if _seconds_left <= 0.0:
				_finish_wait()
		MotionState.BOARDING:
			_seconds_left -= delta
			if _seconds_left <= 0.0:
				boarding_finished.emit()
				_begin_driving()
		MotionState.DRIVING:
			_move_vehicle(delta)


func _begin_initial_action() -> void:
	if movement_mode == MovementMode.BOARD_THEN_DRIVE:
		start_boarding()
	else:
		_begin_driving()


func start_boarding() -> void:
	## Can also be called by a future [F] Board interaction.
	_state = MotionState.BOARDING
	_seconds_left = boarding_duration
	_play(_boarding_animation if _boarding_animation != &"" else _idle_animation)


func start_driving() -> void:
	## Public method for starting a parked vehicle from another script.
	_begin_driving()


func stop_vehicle() -> void:
	_state = MotionState.IDLE
	_play(_idle_animation)


func reset_vehicle() -> void:
	global_position = _start_position
	_traveling_outward = true
	_set_direction(_base_direction)
	stop_vehicle()
	if movement_mode != MovementMode.IDLE_ONLY:
		if start_delay > 0.0:
			_wait(NextAction.BEGIN, start_delay)
		else:
			_begin_initial_action()


func _begin_driving() -> void:
	_state = MotionState.DRIVING
	var destination: float = _start_position.x + _base_direction * travel_distance
	if movement_mode == MovementMode.PATROL and not _traveling_outward:
		destination = _start_position.x
	_target_x = destination
	_set_direction(1.0 if _target_x >= global_position.x else -1.0)
	_play(_drive_animation if _drive_animation != &"" else _idle_animation)


func _move_vehicle(delta: float) -> void:
	var next_x: float = move_toward(global_position.x, _target_x, drive_speed * delta)
	# Move as 2.5D scenery, even if a CollisionShape3D has not been added yet.
	# Never overwrite the vehicle's original Y or Z placement.
	global_position = Vector3(next_x, _start_position.y, _start_position.z)
	if is_equal_approx(next_x, _target_x):
		reached_destination.emit()
		match movement_mode:
			MovementMode.DRIVE_LOOP, MovementMode.BOARD_THEN_DRIVE:
				_wait(NextAction.LOOP_RESTART, wait_at_end)
			MovementMode.PATROL:
				_traveling_outward = not _traveling_outward
				_wait(NextAction.PATROL_REVERSE, wait_at_end)
			_:
				stop_vehicle()


func _wait(action: NextAction, duration: float) -> void:
	_next_action = action
	_seconds_left = maxf(0.0, duration)
	_state = MotionState.WAITING
	_play(_idle_animation)
	if _seconds_left <= 0.0:
		_finish_wait()


func _finish_wait() -> void:
	match _next_action:
		NextAction.BEGIN:
			_begin_initial_action()
		NextAction.LOOP_RESTART:
			global_position = _start_position
			_traveling_outward = true
			_begin_driving()
		NextAction.PATROL_REVERSE:
			_begin_driving()


func _set_direction(new_direction: float) -> void:
	_direction = new_direction
	if sprite != null and flip_sprite_with_direction:
		# The supplied jeep/tricycle artwork faces right in its original frames.
		sprite.flip_h = _direction < 0.0


func _find_animation(wanted: String) -> StringName:
	for animation_name: StringName in sprite.sprite_frames.get_animation_names():
		if String(animation_name).to_lower() == wanted.to_lower():
			return animation_name
	return &""


func _play(animation_name: StringName) -> void:
	if sprite == null or animation_name == &"":
		return
	if sprite.animation != animation_name or not sprite.is_playing():
		sprite.play(animation_name)
