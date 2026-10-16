extends CharacterBody3D
## After Class - Shared Jeep and Tricycle controller (Godot 4.x).
## At BusStop, pressing [F] Wait starts all traffic.
## Jeep alone stops on pass 3 for boarding, then changes to Neighborhood Street.
## Tricycle/Tricyle and Tricycle2/Tricyle2 NEVER stop for boarding; they repeat forever.
## Start after F; space vehicles so they do not overlap on the road.
## Recognizes the actual scene names Tricyle and Tricyle2 (missing second c).

# Preserve existing Inspector enum indexes.
enum MovementMode { IDLE_ONLY, DRIVE_ONCE, DRIVE_LOOP, PATROL, BOARD_THEN_DRIVE, TRAFFIC_THEN_BOARD }
enum MotionState { IDLE, WAITING, BOARDING, DRIVING, STOPPED_FOR_BOARDING, PLAYER_BOARDING, DRIVING_AWAY, DONE, QUEUED }
enum NextAction { BEGIN, LOOP_RESTART, PATROL_REVERSE, FIRST_TRAFFIC_PASS, SECOND_TRAFFIC_PASS, THIRD_TRAFFIC_ARRIVAL }

signal boarding_finished
signal reached_destination

const NEIGHBORHOOD_SCENE: String = "res://Scene/neighborhood_street.tscn"

@export_group("General Movement")
@export var movement_mode: MovementMode = MovementMode.TRAFFIC_THEN_BOARD
@export_range(0.1, 20.0, 0.1) var drive_speed: float = 4.0
@export_range(1.0, 200.0, 0.5) var travel_distance: float = 24.0
@export var start_facing_right: bool = true
@export var flip_sprite_with_direction: bool = true
@export_range(0.0, 30.0, 0.1) var start_delay: float = 0.0
@export_range(0.0, 30.0, 0.1) var wait_at_end: float = 1.0
@export_range(0.1, 10.0, 0.1) var boarding_duration: float = 2.0

@export_group("Three Traffic Passes")
@export_range(0.0, 120.0, 0.5) var first_arrival_gap: float = 7.0
@export_range(0.0, 120.0, 0.5) var second_arrival_gap: float = 10.0
@export_range(0.0, 120.0, 0.5) var third_arrival_gap: float = 10.0
@export var traffic_end_marker_name: String = ""
@export var boarding_stop_marker_name: String = ""
@export_range(-8.0, 8.0, 0.1) var stop_center_offset_x: float = -1.5
@export_range(1.0, 80.0, 0.5) var drive_away_distance: float = 12.0

@export_group("Automatic Boarding")
@export var vehicle_label: String = "Jeepney"
@export var wait_area_name: String = "Bus"
@export var boarding_seat_name: String = "BoardingSeat"
@export_range(0.0, 10.0, 0.1) var boarding_stop_seconds: float = 3.0
@export_file("*.tscn") var destination_scene: String = NEIGHBORHOOD_SCENE
@export var debug_vehicle: bool = false

@export_group("Traffic Loop Options")
@export var ambient_traffic_only: bool = false
@export var hide_vehicle_during_gaps: bool = true
@export_range(0.0, 120.0, 0.5) var additional_first_gap: float = 0.0

@export_group("Bus Stop Traffic Spacing")
# New staggered defaults. Set this to ON to use the per-vehicle
# First/Second/Third Arrival Gap fields above instead.
@export var use_custom_arrival_gaps: bool = false
@export_range(0.0, 10.0, 0.5) var minimum_road_clear_gap: float = 2.5

@export_group("Collision Configuration")
@export_flags_3d_physics var traffic_collision_layer: int = 4
@export_flags_3d_physics var traffic_collision_mask: int = 0

@onready var sprite: AnimatedSprite3D = get_node_or_null("AnimatedSprite3D") as AnimatedSprite3D

var _start_position: Vector3 = Vector3.ZERO
var _direction: float = 1.0
var _target_x: float = 0.0
var _state: MotionState = MotionState.IDLE
var _next_action: NextAction = NextAction.BEGIN
var _seconds_left: float = 0.0
var _pass_number: int = 0
var _traffic_started: bool = false
var _is_boarding_pass: bool = false
var _traveling_outward: bool = true
var _end_marker: Marker3D = null
var _wait_area: Node = null
var _boarded_player: CharacterBody3D = null
var _idle_animation: StringName = &""
var _drive_animation: StringName = &""
var _boarding_animation: StringName = &""
var _queued_pass: int = 0
var _lane_reserved: bool = false
const LANE_OWNER_KEY: StringName = &"equalpath_bus_stop_lane_owner"
const LANE_CLEAR_KEY: StringName = &"equalpath_bus_stop_next_lane_time"


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_PAUSABLE
	_start_position = global_position
	_direction = 1.0 if start_facing_right else -1.0
	collision_layer = traffic_collision_layer
	collision_mask = traffic_collision_mask

	# Ignore old Inspector values in the BusStop scene.
	# In particular, Tricyle and Tricyle2 must NEVER use Jeep boarding mode.
	if _in_bus_stop() and (_is_jeep() or _is_tricycle()):
		movement_mode = MovementMode.TRAFFIC_THEN_BOARD
		collision_mask = 0  # Keep the CollisionShape3D but do not push the player.
		if _is_tricycle():
			ambient_traffic_only = true
			traffic_end_marker_name = "TricycleTrafficEnd"
			boarding_stop_marker_name = ""
		else:
			ambient_traffic_only = false
			traffic_end_marker_name = "JeepTrafficEnd"
			boarding_stop_marker_name = "JeepBoardingStop"
	if debug_vehicle:
		print("VEHICLE DETECTED: ", get_parent().name, " type=", "TRICYCLE (NONSTOP)" if _is_tricycle() else "JEEP" if _is_jeep() else "OTHER")

	if sprite == null or sprite.sprite_frames == null:
		push_error("Vehicle needs a child AnimatedSprite3D with SpriteFrames: " + name)
		set_physics_process(false)
		return

	_idle_animation = _find_animation("idle")
	_drive_animation = _find_animation("drive")
	_boarding_animation = _find_animation("boarding")
	_set_direction(_direction)
	_play(_idle_animation)
	_disable_old_board_prompt()

	if movement_mode == MovementMode.TRAFFIC_THEN_BOARD:
		visible = false
		_state = MotionState.IDLE
		_find_wait_area()
		if _has_wait_started():
			_start_traffic()
	elif movement_mode == MovementMode.IDLE_ONLY:
		stop_vehicle()
	elif start_delay > 0.0:
		_wait(NextAction.BEGIN, start_delay)
	else:
		_begin_initial_action()


func _in_bus_stop() -> bool:
	var parent_node: Node = self
	while parent_node != null:
		if String(parent_node.name).to_lower() == "busstop":
			return true
		parent_node = parent_node.get_parent()
	return false


func _vehicle_instance_name() -> String:
	# Jeep / Tricyle / Tricyle2 are the INSTANCED SCENE ROOTS.
	# vehicle.gd is attached to each root's CharacterBody3D child.
	if get_parent() != null:
		return String(get_parent().name).strip_edges().to_lower()
	return String(name).strip_edges().to_lower()


func _is_tricycle() -> bool:
	var root_name: String = _vehicle_instance_name()
	# Important: the user's roots are spelled Tricyle / Tricyle2.
	# Accept both the correct and misspelled names, plus Trike.
	if root_name.begins_with("tri"):
		return true
	if "jeep" in root_name:
		return false
	return vehicle_label.strip_edges().to_lower().begins_with("tri")


func _is_jeep() -> bool:
	var root_name: String = _vehicle_instance_name()
	if root_name.begins_with("tri"):
		return false
	if "jeep" in root_name:
		return true
	# Never guess that an unrecognized vehicle is a Jeep.
	return false


func _find_wait_area() -> void:
	var scene: Node = get_tree().current_scene
	if scene == null:
		return
	_wait_area = scene.get_node_or_null(NodePath(wait_area_name))
	if _wait_area == null:
		_wait_area = scene.find_child(wait_area_name, true, false)
	if is_instance_valid(_wait_area) and _wait_area.has_signal("waiting_for_transport"):
		var callback: Callable = Callable(self, "_on_wait_pressed")
		if not _wait_area.is_connected("waiting_for_transport", callback):
			_wait_area.connect("waiting_for_transport", callback)


func _has_wait_started() -> bool:
	if not is_instance_valid(_wait_area):
		return false
	if _wait_area.has_method("is_waiting_for_transport"):
		return bool(_wait_area.call("is_waiting_for_transport"))
	return false


func _on_wait_pressed() -> void:
	_start_traffic()


func _start_traffic() -> void:
	if _traffic_started or movement_mode != MovementMode.TRAFFIC_THEN_BOARD:
		return
	_traffic_started = true
	_pass_number = 0
	_wait(NextAction.FIRST_TRAFFIC_PASS, _gap_before_pass(1))
	if debug_vehicle:
		print(get_parent().name, ": traffic started after [F] Wait")


func _gap_before_pass(pass_index: int) -> float:
	# Every gap is counted offscreen, after reaching the end marker.
	# These timings are deliberately staggered to reduce overlaps.
	if use_custom_arrival_gaps:
		var custom_gap: float = first_arrival_gap if pass_index == 1 else second_arrival_gap if pass_index == 2 else third_arrival_gap
		return custom_gap + additional_first_gap if pass_index == 1 else custom_gap

	if _in_bus_stop():
		if _is_tricycle():
			if _vehicle_instance_name().ends_with("2"):
				if pass_index == 1 and _pass_number == 0:
					return 27.0 + additional_first_gap
				return 21.0
			if pass_index == 1 and _pass_number == 0:
				return 18.0 + additional_first_gap
			return 19.0
		if _is_jeep():
			return (7.0 + additional_first_gap) if pass_index == 1 else 10.0

	return (first_arrival_gap + additional_first_gap) if pass_index == 1 else second_arrival_gap if pass_index == 2 else third_arrival_gap


func _traffic_scene() -> Node:
	return get_tree().current_scene


func _can_enter_road() -> bool:
	var scene: Node = _traffic_scene()
	if scene == null:
		return false

	# The first vehicle has no lane metadata yet. Never call get_meta()
	# until has_meta() confirms the key exists: Godot reports an error
	# every frame if we try to read an absent key with a null default.
	if scene.has_meta(LANE_OWNER_KEY):
		var road_occupant: Variant = scene.get_meta(LANE_OWNER_KEY)
		if is_instance_valid(road_occupant):
			if road_occupant == self:
				return true
			return false
		# A previous vehicle was freed; release its stale lane ownership.
		scene.remove_meta(LANE_OWNER_KEY)

	if scene.has_meta(LANE_CLEAR_KEY):
		return Time.get_ticks_msec() >= int(scene.get_meta(LANE_CLEAR_KEY))
	return true


func _reserve_road() -> bool:
	if not _can_enter_road():
		return false
	var scene: Node = _traffic_scene()
	if scene == null:
		return false
	scene.set_meta(LANE_OWNER_KEY, self)
	_lane_reserved = true
	return true


func _release_road() -> void:
	if not _lane_reserved:
		return
	var scene: Node = _traffic_scene()
	if scene != null and scene.has_meta(LANE_OWNER_KEY):
		var road_occupant: Variant = scene.get_meta(LANE_OWNER_KEY)
		if road_occupant == self:
			scene.remove_meta(LANE_OWNER_KEY)
			scene.set_meta(
				LANE_CLEAR_KEY,
				Time.get_ticks_msec() + int(minimum_road_clear_gap * 1000.0)
			)
	_lane_reserved = false


func _request_pass(pass_index: int) -> void:
	# Gap has finished. Do not appear until the road is clear.
	_queued_pass = pass_index
	_state = MotionState.QUEUED
	_attempt_queued_pass()


func _attempt_queued_pass() -> void:
	if _state != MotionState.QUEUED:
		return
	if not _reserve_road():
		return
	var pass_to_start: int = _queued_pass
	_queued_pass = 0
	_start_traffic_pass(pass_to_start)

func _find_marker(marker_name: String) -> Marker3D:
	var scene: Node = get_tree().current_scene
	if scene == null:
		return null
	var direct: Marker3D = scene.get_node_or_null(NodePath(marker_name)) as Marker3D
	if direct != null:
		return direct
	return scene.find_child(marker_name, true, false) as Marker3D


func _resolve_end_marker() -> bool:
	var marker_name: String = traffic_end_marker_name.strip_edges()
	if marker_name.is_empty():
		marker_name = "TricycleTrafficEnd" if _is_tricycle() else "JeepTrafficEnd"
	_end_marker = _find_marker(marker_name)
	if _end_marker == null:
		push_error(get_parent().name + ": missing BusStop Marker3D '" + marker_name + "'.")
		return false
	if is_equal_approx(_end_marker.global_position.x, _start_position.x):
		push_error(get_parent().name + ": TrafficEnd has same X as vehicle start. Move it to road's end.")
		return false
	return true


func _physics_process(delta: float) -> void:
	if movement_mode == MovementMode.TRAFFIC_THEN_BOARD and not _traffic_started:
		if not is_instance_valid(_wait_area):
			_find_wait_area()
		if _has_wait_started():
			_start_traffic()
		return

	match _state:
		MotionState.WAITING, MotionState.BOARDING, MotionState.PLAYER_BOARDING:
			_seconds_left -= delta
			if _seconds_left <= 0.0:
				if _state == MotionState.BOARDING:
					boarding_finished.emit()
					_begin_driving()
				elif _state == MotionState.PLAYER_BOARDING:
					_begin_final_departure()
				else:
					_finish_wait()
		MotionState.DRIVING, MotionState.DRIVING_AWAY:
			_move_vehicle(delta)
		MotionState.STOPPED_FOR_BOARDING:
			# This is NEVER reachable for either Tricycle.
			if _is_jeep():
				_board_automatically()
		MotionState.QUEUED:
			_attempt_queued_pass()


func _wait(action: NextAction, seconds: float) -> void:
	_next_action = action
	_seconds_left = maxf(0.0, seconds)
	_state = MotionState.WAITING
	_play(_idle_animation)
	if debug_vehicle:
		print(get_parent().name, " gap ", _seconds_left, " s (offscreen)")
	if _seconds_left <= 0.0:
		_finish_wait()


func _finish_wait() -> void:
	match _next_action:
		NextAction.FIRST_TRAFFIC_PASS:
			_request_pass(1)
		NextAction.SECOND_TRAFFIC_PASS:
			_request_pass(2)
		NextAction.THIRD_TRAFFIC_ARRIVAL:
			_request_pass(3)
		NextAction.LOOP_RESTART:
			global_position = _start_position
			_begin_driving()
		NextAction.PATROL_REVERSE:
			_begin_driving()
		NextAction.BEGIN:
			_begin_initial_action()


func _start_traffic_pass(pass_index: int) -> void:
	if not _resolve_end_marker():
		_release_road()
		_state = MotionState.IDLE
		return

	_pass_number = pass_index
	_direction = 1.0 if _end_marker.global_position.x > _start_position.x else -1.0
	global_position = _start_position
	_target_x = _end_marker.global_position.x
	_is_boarding_pass = false

	# Tricycle and Tricycle2 ALWAYS go directly to TricycleTrafficEnd,
	# on pass 1, 2, 3, 4, and every later cycle.
	if _is_jeep() and not ambient_traffic_only and pass_index == 3 and _has_selected_waiting_player():
		var stop_x: float = _boarding_stop_x()
		var valid_stop: bool = (
			(stop_x - _start_position.x) * _direction > 0.0
			and (_target_x - stop_x) * _direction > 0.0
		)
		if valid_stop:
			_target_x = stop_x
			_is_boarding_pass = true
		else:
			push_warning("JeepBoardingStop must be BETWEEN Jeep start and JeepTrafficEnd.")

	visible = true
	_state = MotionState.DRIVING
	_set_direction(_direction)
	_play(_drive_animation if _drive_animation != &"" else _idle_animation)
	if debug_vehicle:
		print(get_parent().name, " PASS ", pass_index, " driving to X=", _target_x)


func _boarding_stop_x() -> float:
	var marker_name: String = boarding_stop_marker_name.strip_edges()
	if marker_name.is_empty():
		marker_name = "JeepBoardingStop"
	var marker: Marker3D = _find_marker(marker_name)
	if marker != null:
		return marker.global_position.x
	var player: CharacterBody3D = _get_waiting_player()
	if is_instance_valid(player):
		return player.global_position.x + stop_center_offset_x * _direction
	return lerpf(_start_position.x, _end_marker.global_position.x, 0.65)


func _move_vehicle(delta: float) -> void:
	var next_x: float = move_toward(global_position.x, _target_x, drive_speed * delta)
	global_position = Vector3(next_x, _start_position.y, _start_position.z)
	if not is_equal_approx(next_x, _target_x):
		return

	if _state == MotionState.DRIVING_AWAY:
		_arrived_at_end(true)
	elif movement_mode == MovementMode.TRAFFIC_THEN_BOARD:
		# Strict rule: every Tricyle pass travels to the end; no boarding stop.
		if _is_tricycle():
			_arrived_at_end(false)
		elif _is_jeep() and _pass_number == 3 and _is_boarding_pass:
			_state = MotionState.STOPPED_FOR_BOARDING
			_play(_idle_animation)
			_board_automatically()
		else:
			_arrived_at_end(false)
	else:
		reached_destination.emit()
		match movement_mode:
			MovementMode.DRIVE_LOOP, MovementMode.BOARD_THEN_DRIVE:
				_wait(NextAction.LOOP_RESTART, wait_at_end)
			MovementMode.PATROL:
				_traveling_outward = not _traveling_outward
				_wait(NextAction.PATROL_REVERSE, wait_at_end)
			_:
				stop_vehicle()


func _arrived_at_end(after_boarding: bool) -> void:
	# A vehicle may ONLY vanish after its body reaches its end marker.
	if not is_instance_valid(_end_marker):
		push_error(get_parent().name + ": TrafficEnd marker was lost.")
		_state = MotionState.IDLE
		return
	if not is_equal_approx(global_position.x, _end_marker.global_position.x):
		_target_x = _end_marker.global_position.x
		return

	visible = false
	_release_road()
	reached_destination.emit()
	if debug_vehicle:
		print(get_parent().name, " reached ", _end_marker.name, " and disappeared")

	if _is_tricycle():
		# NONSTOP Tricyle/Tricyle2: ONLY the end marker ends a pass.
		# Wait offscreen for its configured gap, then loop forever.
		if _pass_number == 1:
			_wait(NextAction.SECOND_TRAFFIC_PASS, _gap_before_pass(2))
		elif _pass_number == 2:
			_wait(NextAction.THIRD_TRAFFIC_ARRIVAL, _gap_before_pass(3))
		else:
			_wait(NextAction.FIRST_TRAFFIC_PASS, _gap_before_pass(1))
	elif after_boarding and _is_jeep():
		_state = MotionState.DONE
		_transition_to_neighborhood()
	elif _pass_number == 1:
		_wait(NextAction.SECOND_TRAFFIC_PASS, _gap_before_pass(2))
	elif _pass_number == 2:
		_wait(NextAction.THIRD_TRAFFIC_ARRIVAL, _gap_before_pass(3))
	else:
		_wait(NextAction.FIRST_TRAFFIC_PASS, _gap_before_pass(1))


func _get_waiting_player() -> CharacterBody3D:
	if not is_instance_valid(_wait_area):
		_find_wait_area()
	if is_instance_valid(_wait_area) and _wait_area.has_method("get_waiting_player"):
		return _wait_area.call("get_waiting_player") as CharacterBody3D
	return null


func _has_selected_waiting_player() -> bool:
	if _is_tricycle():
		return false
	var player: CharacterBody3D = _get_waiting_player()
	if not is_instance_valid(player):
		return false
	if is_instance_valid(_wait_area) and _wait_area.has_method("get_selected_transport"):
		var choice: String = String(_wait_area.call("get_selected_transport")).to_lower()
		return choice == "jeep" or choice == "jeepney"
	return true


func _board_automatically() -> void:
	if _state != MotionState.STOPPED_FOR_BOARDING or not _is_jeep():
		return
	_boarded_player = _get_waiting_player()
	if not is_instance_valid(_boarded_player):
		# No waiting player: jeep drives on rather than blocking traffic forever.
		_begin_final_departure()
		return
	_boarded_player.velocity = Vector3.ZERO
	_boarded_player.set_physics_process(false)
	_boarded_player.hide()  # No jump or movement tween.
	boarding_finished.emit()
	_state = MotionState.PLAYER_BOARDING
	_seconds_left = boarding_stop_seconds
	if debug_vehicle:
		print("JEEP ONLY: player boarded; stopped for ", _seconds_left, " seconds")
	if _seconds_left <= 0.0:
		_begin_final_departure()


func _begin_final_departure() -> void:
	if not is_instance_valid(_end_marker):
		push_error("Jeep cannot leave without JeepTrafficEnd.")
		_state = MotionState.IDLE
		return
	_state = MotionState.DRIVING_AWAY
	_target_x = _end_marker.global_position.x
	_set_direction(_direction)
	_play(_drive_animation if _drive_animation != &"" else _idle_animation)


func _transition_to_neighborhood() -> void:
	# Force the destination so an old empty Inspector value cannot break it.
	if not ResourceLoader.exists(NEIGHBORHOOD_SCENE, "PackedScene"):
		push_error("Missing scene: " + NEIGHBORHOOD_SCENE)
		return
	var transition: Node = get_node_or_null("/root/SceneTransition")
	if transition != null and transition.has_method("transition_to"):
		transition.call("transition_to", NEIGHBORHOOD_SCENE)
	else:
		push_error("Add your existing SceneTransition.gd as the SceneTransition Autoload for the fade.")


func _disable_old_board_prompt() -> void:
	var area: Area3D = find_child("BoardingArea", true, false) as Area3D
	if area != null:
		area.monitoring = false
		var label: Label3D = area.get_node_or_null("FPrompt") as Label3D
		if label != null:
			label.hide()


# Preserve the original controller's public methods for any other scenes.
func _begin_initial_action() -> void:
	if movement_mode == MovementMode.BOARD_THEN_DRIVE:
		start_boarding()
	else:
		_begin_driving()


func _begin_driving() -> void:
	_state = MotionState.DRIVING
	_target_x = _start_position.x + _direction * travel_distance
	if movement_mode == MovementMode.PATROL and not _traveling_outward:
		_target_x = _start_position.x
	visible = true
	_set_direction(1.0 if _target_x >= global_position.x else -1.0)
	_play(_drive_animation if _drive_animation != &"" else _idle_animation)


func start_boarding() -> void:
	_state = MotionState.BOARDING
	_seconds_left = boarding_duration
	_play(_boarding_animation if _boarding_animation != &"" else _idle_animation)


func start_driving() -> void:
	_begin_driving()


func stop_vehicle() -> void:
	_state = MotionState.IDLE
	_play(_idle_animation)


func reset_vehicle() -> void:
	_release_road()
	global_position = _start_position
	_pass_number = 0
	_traffic_started = false
	_is_boarding_pass = false
	_boarded_player = null
	_traveling_outward = true
	if movement_mode == MovementMode.TRAFFIC_THEN_BOARD:
		visible = false
		_state = MotionState.IDLE
		_find_wait_area()
		if _has_wait_started():
			_start_traffic()
	elif movement_mode == MovementMode.IDLE_ONLY:
		stop_vehicle()
	else:
		_begin_initial_action()


func _set_direction(new_direction: float) -> void:
	_direction = new_direction
	if sprite != null and flip_sprite_with_direction:
		sprite.flip_h = _direction < 0.0


func _find_animation(wanted: String) -> StringName:
	if sprite == null or sprite.sprite_frames == null:
		return &""
	for animation_name: StringName in sprite.sprite_frames.get_animation_names():
		if String(animation_name).to_lower() == wanted.to_lower():
			return animation_name
	return &""


func _play(animation_name: StringName) -> void:
	if sprite == null or animation_name == &"":
		return
	if sprite.animation != animation_name or not sprite.is_playing():
		sprite.play(animation_name)


func _exit_tree() -> void:
	_release_road()
