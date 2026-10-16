extends Control

@export_file("*.tscn") var return_scene_path: String = "res://Scene/school_grounds.tscn"
@export var shoot_sound: AudioStream
@export var score_sound: AudioStream
@export var bounce_sound: AudioStream
@export_range(0.55, 1.10, 0.01) var view_distance_scale: float = 0.68
# Longer arc time = higher, softer basketball lob (ideal shot stays aimed at rim).
@export_range(1.05, 1.65, 0.05) var shot_arc_time: float = 1.25
# Approximate straight-up drag distance for a clean basket.
@export_range(120.0, 260.0, 5.0) var perfect_swipe_pixels: float = 180.0

const VIEW: Vector2 = Vector2(960.0, 600.0)
const CAMERA_Z: float = 12.0
const CAMERA_Y: float = 2.46
const FOCAL_LENGTH: float = 1900.0
const VIEW_CENTER: Vector2 = Vector2(480.0, 443.0)

const HOOP_HEIGHT_OFFSET: float = 0.35

const BOARD_Z: float = 0.08
const BOARD_THICKNESS: float = 0.14
const BOARD_MIN_Y: float = 2.65 + HOOP_HEIGHT_OFFSET
const BOARD_MAX_Y: float = 4.20 + HOOP_HEIGHT_OFFSET
const BOARD_HALF_WIDTH: float = 1.55

const RIM_CENTER: Vector3 = Vector3(0.0, 3.05 + HOOP_HEIGHT_OFFSET, 0.88)
const RIM_RADIUS: float = 0.62
const RIM_THICKNESS: float = 0.045
const BALL_RADIUS: float = 0.12
const BALL_HOME: Vector3 = Vector3(0.0, 1.95, 6.10)
const GRAVITY: float = 9.80
const FLOOR_Y: float = 0.0

var _view_scale: float = 1.0
var _view_offset: Vector2 = Vector2.ZERO
var _score: int = 0
var _dragging: bool = false
var _drag_begin: Vector2 = Vector2.ZERO
var _drag_end: Vector2 = Vector2.ZERO
var _ball: Vector3 = BALL_HOME
var _velocity: Vector3 = Vector3.ZERO
var _ball_flying: bool = false
var _shot_age: float = 0.0
var _reset_wait: float = 0.0
var _scored_this_shot: bool = false
var _bounce_cooldown: float = 0.0
var _score_flash: float = 0.0
var _ball_spin: float = 0.0
var _score_label: Label = null
var _back_button: Button = null
var _hud: CanvasLayer = null
var _hud_was_visible: bool = false
var _shoot_player: AudioStreamPlayer = null
var _score_player: AudioStreamPlayer = null
var _bounce_player: AudioStreamPlayer = null


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_PAUSABLE
	mouse_filter = Control.MOUSE_FILTER_IGNORE
	set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	Input.set_mouse_mode(Input.MOUSE_MODE_VISIBLE)
	_hud = get_node_or_null("/root/SchoolHUD") as CanvasLayer
	if is_instance_valid(_hud):
		_hud_was_visible = _hud.visible
		_hud.hide()
	_build_ui()
	_shoot_player = _make_audio(shoot_sound, "res://Sounds/basketball_shot.wav")
	_score_player = _make_audio(score_sound, "res://Sounds/basketball_swish.wav")
	_bounce_player = _make_audio(bounce_sound, "res://Sounds/basketball_bounce.wav")
	_update_view()


func _exit_tree() -> void:
	Input.set_mouse_mode(Input.MOUSE_MODE_VISIBLE)
	if is_instance_valid(_hud) and _hud_was_visible:
		_hud.show()


func _notification(what: int) -> void:
	if what == NOTIFICATION_RESIZED:
		_update_view()
		queue_redraw()


func _update_view() -> void:
	if size.x <= 0.0 or size.y <= 0.0:
		return
	_view_scale = minf(size.x / VIEW.x, size.y / VIEW.y)
	_view_offset = (size - VIEW * _view_scale) * 0.5


func _game_point(screen_point: Vector2) -> Vector2:
	return (screen_point - _view_offset) / maxf(_view_scale, 0.001)


func _perspective_factor(depth: float) -> float:
	return (FOCAL_LENGTH * view_distance_scale) / maxf(0.3, CAMERA_Z - depth)


func _project(world_position: Vector3) -> Vector2:
	var factor: float = _perspective_factor(world_position.z)
	return VIEW_CENTER + Vector2(world_position.x * factor, (CAMERA_Y - world_position.y) * factor)


func _project_ball_radius(world_position: Vector3) -> float:
	return BALL_RADIUS * _perspective_factor(world_position.z)


func _unhandled_input(event: InputEvent) -> void:
	if event is InputEventKey:
		var key_event: InputEventKey = event as InputEventKey
		if key_event.pressed and not key_event.echo and key_event.keycode == KEY_ESCAPE:
			get_viewport().set_input_as_handled()
			_go_back()
			return

	if event is InputEventMouseButton:
		var mouse_event: InputEventMouseButton = event as InputEventMouseButton
		if mouse_event.button_index != MOUSE_BUTTON_LEFT:
			return
		var point: Vector2 = _game_point(mouse_event.position)
		if mouse_event.pressed:
			if _ball_flying or _reset_wait > 0.0:
				return
			var distance: float = point.distance_to(_project(BALL_HOME))
			if distance > maxf(75.0, _project_ball_radius(BALL_HOME) * 2.2):
				return
			_dragging = true
			_drag_begin = point
			_drag_end = point
		else:
			if not _dragging:
				return
			_drag_end = point
			_dragging = false
			_shoot_from_swipe()
		get_viewport().set_input_as_handled()
		queue_redraw()
	elif event is InputEventMouseMotion and _dragging:
		var move_event: InputEventMouseMotion = event as InputEventMouseMotion
		_drag_end = _game_point(move_event.position)
		queue_redraw()


# Compute a real ballistic arc, not a straight animation. Gravity is applied
# in _simulate(). The reference swipe arrives at the rim while DESCENDING.
func _launch_velocity(swipe: Vector2) -> Vector3:
	var flight_time: float = shot_arc_time
	var rise_to_rim: float = RIM_CENTER.y - BALL_HOME.y
	var travel_to_rim: float = BALL_HOME.z - RIM_CENTER.z

	# Solve y(t) = y0 + vy*t - gravity*t^2/2 for the basket height.
	var ideal_vertical: float = (rise_to_rim + 0.5 * GRAVITY * flight_time * flight_time) / flight_time
	var ideal_forward: float = travel_to_rim / flight_time

	# Too little force falls short, too much overshoots; sideways drags curve
	# the aim to the left/right without changing the gravitational parabola.
	var upward_drag: float = maxf(0.0, -swipe.y)
	var relative_power: float = clampf(upward_drag / perfect_swipe_pixels, 0.45, 2.0)
	var speed_multiplier: float = pow(relative_power, 0.55)
	var side_velocity: float = clampf(swipe.x * 0.0072, -2.5, 2.5)

	return Vector3(side_velocity, ideal_vertical * speed_multiplier, -ideal_forward * speed_multiplier)


func _shot_position(start_velocity: Vector3, elapsed: float) -> Vector3:
	return BALL_HOME + start_velocity * elapsed + Vector3(0.0, -0.5 * GRAVITY * elapsed * elapsed, 0.0)


func _shoot_from_swipe() -> void:
	var swipe: Vector2 = _drag_end - _drag_begin
	if swipe.y > -40.0:
		return
	_ball = BALL_HOME
	_velocity = _launch_velocity(swipe)
	_ball_flying = true
	_reset_wait = 0.0
	_shot_age = 0.0
	_scored_this_shot = false
	_score_flash = 0.0
	_ball_spin = 0.0
	_play(_shoot_player)


func _process(delta: float) -> void:
	if _ball_flying:
		_simulate(minf(delta, 0.08))
	elif _reset_wait > 0.0:
		_reset_wait = maxf(0.0, _reset_wait - delta)
		if _reset_wait <= 0.0:
			_ball = BALL_HOME
			_velocity = Vector3.ZERO
	_score_flash = maxf(0.0, _score_flash - delta)
	_bounce_cooldown = maxf(0.0, _bounce_cooldown - delta)
	queue_redraw()


func _simulate(delta: float) -> void:
	var steps: int = maxi(1, int(ceilf(delta / (1.0 / 240.0))))
	var dt: float = delta / float(steps)
	for i: int in range(steps):
		var previous: Vector3 = _ball
		_velocity.y -= GRAVITY * dt
		_ball += _velocity * dt
		_ball_spin += _velocity.length() * dt * 0.43
		_shot_age += dt
		_check_solid_board()
		_check_solid_rim()
		_check_basket(previous)
		_check_floor()
		if _scored_this_shot and _ball.y < RIM_CENTER.y - 0.66:
			_finish_shot(0.55)
			return
		if _ball.y < -2.0 or _ball.z < -3.0 or _ball.z > 13.0 or _shot_age >= 5.0:
			_finish_shot(0.55)
			return
		if _shot_age > 2.0 and _ball.y <= FLOOR_Y + BALL_RADIUS + 0.002 and _velocity.length() < 0.85:
			_finish_shot(0.5)
			return


func _check_solid_board() -> void:
	var board_min: Vector3 = Vector3(-BOARD_HALF_WIDTH, BOARD_MIN_Y, BOARD_Z - BOARD_THICKNESS * 0.5)
	var board_max: Vector3 = Vector3(BOARD_HALF_WIDTH, BOARD_MAX_Y, BOARD_Z + BOARD_THICKNESS * 0.5)
	var closest: Vector3 = Vector3(
		clampf(_ball.x, board_min.x, board_max.x),
		clampf(_ball.y, board_min.y, board_max.y),
		clampf(_ball.z, board_min.z, board_max.z)
	)
	var difference: Vector3 = _ball - closest
	var distance: float = difference.length()
	if distance >= BALL_RADIUS:
		return
	var normal: Vector3 = Vector3(0.0, 0.0, 1.0)
	if distance > 0.0001:
		normal = difference / distance
	elif _velocity.z > 0.0:
		normal = Vector3(0.0, 0.0, -1.0)
	_ball += normal * (BALL_RADIUS - distance + 0.0005)
	_reflect_from_surface(normal, 0.76)


func _check_solid_rim() -> void:
	var direction: Vector2 = Vector2(_ball.x - RIM_CENTER.x, _ball.z - RIM_CENTER.z)
	var radius_from_center: float = direction.length()
	if radius_from_center <= 0.000001:
		return
	var nearest: Vector3 = RIM_CENTER + Vector3(
		direction.x / radius_from_center * RIM_RADIUS,
		0.0,
		direction.y / radius_from_center * RIM_RADIUS
	)
	var difference: Vector3 = _ball - nearest
	var distance: float = difference.length()
	var clearance: float = BALL_RADIUS + RIM_THICKNESS
	if distance >= clearance:
		return
	var normal: Vector3 = Vector3.UP
	if distance > 0.0001:
		normal = difference / distance
	_ball += normal * (clearance - distance + 0.0005)
	_reflect_from_surface(normal, 0.72)


func _reflect_from_surface(normal: Vector3, restitution: float) -> void:
	var speed_in: float = _velocity.dot(normal)
	if speed_in < 0.0:
		_velocity -= normal * ((1.0 + restitution) * speed_in)
		_velocity *= 0.985
		_bounce()


func _check_basket(previous: Vector3) -> void:
	if _scored_this_shot or _velocity.y >= 0.0:
		return
	if previous.y < RIM_CENTER.y or _ball.y > RIM_CENTER.y:
		return
	var fraction: float = (previous.y - RIM_CENTER.y) / maxf(previous.y - _ball.y, 0.000001)
	var crossed_at: Vector3 = previous.lerp(_ball, clampf(fraction, 0.0, 1.0))
	var radial_distance: float = Vector2(crossed_at.x - RIM_CENTER.x, crossed_at.z - RIM_CENTER.z).length()
	if radial_distance <= RIM_RADIUS - BALL_RADIUS - 0.025:
		_scored_this_shot = true
		_score += 1
		_score_flash = 1.0
		_score_label.text = str(_score)
		_play(_score_player)


func _check_floor() -> void:
	if _ball.y > FLOOR_Y + BALL_RADIUS:
		return
	_ball.y = FLOOR_Y + BALL_RADIUS
	if _velocity.y < 0.0:
		_velocity.y = -_velocity.y * 0.42
		_velocity.x *= 0.88
		_velocity.z *= 0.88
		if _velocity.y > 0.50:
			_bounce()


func _bounce() -> void:
	if _bounce_cooldown <= 0.0:
		_play(_bounce_player)
		_bounce_cooldown = 0.10


func _finish_shot(delay: float) -> void:
	_ball_flying = false
	_reset_wait = delay


func _play(player: AudioStreamPlayer) -> void:
	if is_instance_valid(player) and player.stream != null:
		player.play()


func _make_audio(chosen: AudioStream, fallback: String) -> AudioStreamPlayer:
	var player: AudioStreamPlayer = AudioStreamPlayer.new()
	add_child(player)
	player.stream = chosen
	if player.stream == null and ResourceLoader.exists(fallback):
		player.stream = load(fallback) as AudioStream
	return player


func _build_ui() -> void:
	_back_button = Button.new()
	_back_button.text = "← Back"
	_back_button.custom_minimum_size = Vector2(105.0, 45.0)
	_back_button.focus_mode = Control.FOCUS_NONE
	_back_button.add_theme_font_size_override("font_size", 18)
	_back_button.add_theme_color_override("font_color", Color.WHITE)
	var style: StyleBoxFlat = StyleBoxFlat.new()
	style.bg_color = Color("#242424")
	style.set_corner_radius_all(9)
	style.set_content_margin_all(9)
	_back_button.add_theme_stylebox_override("normal", style)
	_back_button.add_theme_stylebox_override("hover", style)
	add_child(_back_button)
	_back_button.position = Vector2(20.0, 20.0)
	_back_button.pressed.connect(_go_back)

	_score_label = Label.new()
	_score_label.text = "0"
	_score_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_score_label.mouse_filter = Control.MOUSE_FILTER_IGNORE
	_score_label.add_theme_font_size_override("font_size", 48)
	_score_label.add_theme_color_override("font_color", Color("#242424"))
	add_child(_score_label)
	_score_label.set_anchors_preset(Control.PRESET_CENTER_TOP)
	_score_label.offset_left = -70.0
	_score_label.offset_right = 70.0
	_score_label.offset_top = 14.0
	_score_label.offset_bottom = 80.0


func _draw() -> void:
	draw_rect(Rect2(Vector2.ZERO, size), Color.WHITE)
	draw_set_transform(_view_offset, 0.0, Vector2(_view_scale, _view_scale))
	_draw_backboard()
	_draw_rim_back()
	if _ball_flying or _reset_wait <= 0.0:
		_draw_ball()
	_draw_rim_front()
	if _dragging:
		_draw_swipe_guide()
	if _score_flash > 0.0:
		var font: Font = ThemeDB.fallback_font
		var text_pos: Vector2 = _project(RIM_CENTER) + Vector2(92.0, -55.0 - (1.0 - _score_flash) * 25.0)
		draw_string(font, text_pos, "+1", HORIZONTAL_ALIGNMENT_LEFT, -1.0, 30, Color(0.18, 0.56, 0.27, _score_flash))
	draw_set_transform(Vector2.ZERO, 0.0, Vector2.ONE)


func _draw_backboard() -> void:
	var top_left: Vector2 = _project(Vector3(-BOARD_HALF_WIDTH, BOARD_MAX_Y, BOARD_Z))
	var bottom_right: Vector2 = _project(Vector3(BOARD_HALF_WIDTH, BOARD_MIN_Y, BOARD_Z))
	var bounds: Rect2 = Rect2(top_left, bottom_right - top_left)
	draw_rect(bounds, Color("#202020"))
	var inside: Rect2 = bounds.grow(-10.0)
	draw_rect(inside, Color.WHITE)
	draw_rect(inside.grow(-10.0), Color("#282828"), false, 4.0)

	var square_top: Vector2 = _project(Vector3(-0.59, 3.74 + HOOP_HEIGHT_OFFSET, BOARD_Z))
	var square_bottom: Vector2 = _project(Vector3(0.59, 2.98 + HOOP_HEIGHT_OFFSET, BOARD_Z))
	draw_rect(Rect2(square_top, square_bottom - square_top), Color("#252525"), false, 8.0)

	var rim_screen: Vector2 = _project(RIM_CENTER)
	var attachment: Rect2 = Rect2(rim_screen + Vector2(-20.0, -10.0), Vector2(40.0, 63.0))
	draw_rect(attachment, Color("#f06010"))
	draw_rect(attachment, Color("#bd4400"), false, 3.0)


func _ellipse_arc(center: Vector2, radius_x: float, radius_y: float, start: float, end: float, color: Color, thickness: float) -> void:
	var points: PackedVector2Array = PackedVector2Array()
	for index: int in range(49):
		var angle: float = lerpf(start, end, float(index) / 48.0)
		points.append(center + Vector2(cos(angle) * radius_x, sin(angle) * radius_y))
	draw_polyline(points, color, thickness, true)


func _draw_rim_back() -> void:
	var center: Vector2 = _project(RIM_CENTER)
	var radius_x: float = RIM_RADIUS * _perspective_factor(RIM_CENTER.z)
	var radius_y: float = radius_x * 0.255
	_ellipse_arc(center, radius_x, radius_y, PI, TAU, Color("#c64d05"), 11.0)
	_ellipse_arc(center, radius_x, radius_y, PI, TAU, Color("#ff791b"), 6.0)


func _draw_rim_front() -> void:
	var center: Vector2 = _project(RIM_CENTER)
	var radius_x: float = RIM_RADIUS * _perspective_factor(RIM_CENTER.z)
	var radius_y: float = radius_x * 0.255
	_ellipse_arc(center, radius_x, radius_y, 0.0, PI, Color("#bf4503"), 12.0)
	_ellipse_arc(center, radius_x, radius_y, 0.0, PI, Color("#ff7414"), 7.0)


func _draw_ball() -> void:
	var center: Vector2 = _project(_ball)
	var radius: float = _project_ball_radius(_ball)
	if center.x < -radius or center.x > VIEW.x + radius or center.y < -radius or center.y > VIEW.y + radius:
		return

	var groove_color: Color = Color("#442517")
	var groove_width: float = maxf(1.6, radius * 0.068)
	var groove_rotation: float = _ball_spin

	# Leather shading kept inside the outer edge of the basketball.
	draw_circle(center, radius, Color("#382319"))
	draw_circle(center, radius - maxf(1.0, radius * 0.045), Color("#c96c29"))
	draw_circle(center + Vector2(-radius * 0.10, -radius * 0.12), radius * 0.82, Color("#e68a38"))
	draw_circle(center + Vector2(-radius * 0.28, -radius * 0.38), radius * 0.12, Color("#f1a44c", 0.67))

	# The four long curved grooves reproduce the panels on a real basketball.
	var middle_horizontal: PackedVector2Array = PackedVector2Array()
	var middle_vertical: PackedVector2Array = PackedVector2Array()
	var curved_left: PackedVector2Array = PackedVector2Array()
	var curved_right: PackedVector2Array = PackedVector2Array()

	for index: int in range(41):
		var t: float = float(index) / 40.0
		var u: float = t * 2.0 - 1.0
		var x: float = u * radius * 0.94
		var y: float = u * radius * 0.94
		var horizontal_curve: Vector2 = Vector2(x, radius * 0.035 * sin(u * PI))
		var vertical_curve: Vector2 = Vector2(radius * 0.035 * sin(u * PI), y)
		var wide_arc_x: float = radius * (0.22 + 0.53 * (1.0 - u * u))
		var left_arc: Vector2 = Vector2(-wide_arc_x, u * radius * 0.92)
		var right_arc: Vector2 = Vector2(wide_arc_x, u * radius * 0.92)
		middle_horizontal.append(center + horizontal_curve.rotated(groove_rotation))
		middle_vertical.append(center + vertical_curve.rotated(groove_rotation))
		curved_left.append(center + left_arc.rotated(groove_rotation))
		curved_right.append(center + right_arc.rotated(groove_rotation))

	draw_polyline(curved_left, groove_color, groove_width, true)
	draw_polyline(curved_right, groove_color, groove_width, true)
	draw_polyline(middle_horizontal, groove_color, groove_width, true)
	draw_polyline(middle_vertical, groove_color, groove_width, true)


func _draw_swipe_guide() -> void:
	var swipe: Vector2 = _drag_end - _drag_begin
	if swipe.y > -40.0:
		return

	# A faint dotted PARABOLIC guide is displayed only while dragging.
	# It uses the same launch calculation as the actual moving ball.
	var start_velocity: Vector3 = _launch_velocity(swipe)
	var total_time: float = shot_arc_time * 1.18
	for index: int in range(1, 32):
		var fraction: float = float(index) / 31.0
		var future: Vector3 = _shot_position(start_velocity, total_time * fraction)
		if future.y < FLOOR_Y + BALL_RADIUS:
			break
		var screen_position: Vector2 = _project(future)
		if screen_position.y < 7.0 or screen_position.y > VIEW.y - 7.0:
			continue
		draw_circle(screen_position, 2.4, Color(0.89, 0.43, 0.14, 0.55 - 0.34 * fraction))


func _go_back() -> void:
	var paths: Array[String] = [
		return_scene_path,
		"res://Scene/school_grounds.tscn",
		"res://Scene/school_grounds.tscn.tscn"
	]
	var destination: String = ""
	for path: String in paths:
		if ResourceLoader.exists(path, "PackedScene"):
			destination = path
			break
	if destination.is_empty():
		var folder: DirAccess = DirAccess.open("res://Scene")
		if folder != null:
			for filename: String in folder.get_files():
				if filename.begins_with("school_grounds") and filename.ends_with(".tscn"):
					var candidate: String = "res://Scene/" + filename
					if ResourceLoader.exists(candidate, "PackedScene"):
						destination = candidate
						break
	if destination.is_empty():
		push_error("Basketball: School Grounds scene not found. Check Return Scene Path in the Inspector.")
		return
	var transition: Node = get_node_or_null("/root/SceneTransition")
	if transition != null and transition.has_method("transition_to"):
		if bool(transition.call("transition_to", destination)):
			return
	get_tree().change_scene_to_file.call_deferred(destination)
