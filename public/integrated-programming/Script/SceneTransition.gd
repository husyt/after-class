extends CanvasLayer
## Autoload as SceneTransition. 2-second total black fade while switching rooms.

signal transition_started
signal transition_finished

@export_range(0.1, 1.0, 0.05) var fade_out_duration: float = 0.45
@export_range(0.0, 3.0, 0.05) var black_hold_duration: float = 1.10
@export_range(0.1, 1.0, 0.05) var fade_in_duration: float = 0.45

var is_transitioning: bool = false
var black_screen: ColorRect = null
var previous_pause: bool = false


func _ready() -> void:
	layer = 200
	process_mode = Node.PROCESS_MODE_ALWAYS
	black_screen = ColorRect.new()
	black_screen.name = "SceneBlackScreen"
	black_screen.color = Color.BLACK
	black_screen.modulate.a = 0.0
	black_screen.mouse_filter = Control.MOUSE_FILTER_STOP
	black_screen.process_mode = Node.PROCESS_MODE_ALWAYS
	add_child(black_screen)
	black_screen.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	black_screen.hide()


func transition_to(scene_path: String) -> bool:
	if is_transitioning or scene_path.is_empty():
		return false
	if not ResourceLoader.exists(scene_path, "PackedScene"):
		push_error("SceneTransition: Scene not found: " + scene_path)
		return false
	is_transitioning = true
	_run_transition(scene_path)
	return true


func _run_transition(scene_path: String) -> void:
	previous_pause = get_tree().paused
	get_tree().paused = true
	transition_started.emit()
	black_screen.modulate.a = 0.0
	black_screen.show()
	await _fade_screen(1.0, fade_out_duration)
	var error: Error = get_tree().change_scene_to_file(scene_path)
	if error != OK:
		push_error("SceneTransition: Failed to load " + scene_path)
		await _fade_screen(0.0, fade_in_duration)
		_finish_transition()
		return
	await get_tree().process_frame
	if black_hold_duration > 0.0:
		await get_tree().create_timer(black_hold_duration, true, false, true).timeout
	await _fade_screen(0.0, fade_in_duration)
	_finish_transition()


func _fade_screen(target_alpha: float, duration: float) -> void:
	var tween: Tween = create_tween()
	tween.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
	tween.set_ignore_time_scale(true)
	tween.tween_property(black_screen, "modulate:a", target_alpha, duration)
	await tween.finished


func _finish_transition() -> void:
	black_screen.hide()
	black_screen.modulate.a = 0.0
	get_tree().paused = previous_pause
	is_transitioning = false
	transition_finished.emit()


func _exit_tree() -> void:
	if is_transitioning:
		get_tree().paused = previous_pause
