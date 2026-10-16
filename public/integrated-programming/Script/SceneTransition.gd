
extends CanvasLayer
## Global scene transition with a black fade.
## Automatically corrects filename capitalization.

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
	black_screen.set_anchors_and_offsets_preset(
		Control.PRESET_FULL_RECT
	)
	black_screen.hide()


func transition_to(scene_path: String) -> bool:
	if is_transitioning:
		return false

	if scene_path.strip_edges().is_empty():
		push_error("SceneTransition: Empty scene path.")
		return false

	var correct_path: String = _resolve_scene_path(scene_path)

	if not ResourceLoader.exists(correct_path, "PackedScene"):
		push_error("SceneTransition: Scene not found: " + correct_path)
		return false

	is_transitioning = true
	_run_transition(correct_path)
	return true


func _resolve_scene_path(requested_path: String) -> String:
	var clean_path: String = requested_path.strip_edges().replace("\\", "/")

	if not clean_path.begins_with("res://"):
		return clean_path

	var relative_path: String = clean_path.trim_prefix("res://")
	var parts: PackedStringArray = relative_path.split("/", false)

	if parts.is_empty():
		return clean_path

	var current_dir: String = "res://"

	for index: int in range(parts.size()):
		var requested_name: String = parts[index]
		var folder: DirAccess = DirAccess.open(current_dir)

		if folder == null:
			return clean_path

		var entries: PackedStringArray = folder.get_directories()

		if index == parts.size() - 1:
			entries = folder.get_files()

		var matched_name: String = ""

		for entry: String in entries:
			if entry == requested_name:
				matched_name = entry
				break

		if matched_name.is_empty():
			for entry: String in entries:
				if entry.to_lower() == requested_name.to_lower():
					matched_name = entry
					break

		if matched_name.is_empty():
			return clean_path

		current_dir = current_dir.path_join(matched_name)

	if current_dir != clean_path:
		print(
			"SceneTransition: Corrected scene path: ",
			clean_path,
			" -> ",
			current_dir
		)

	return current_dir


func _run_transition(scene_path: String) -> void:
	previous_pause = get_tree().paused
	get_tree().paused = true
	transition_started.emit()

	black_screen.modulate.a = 0.0
	black_screen.show()

	await _fade_screen(1.0, fade_out_duration)

	var scene_error: Error = get_tree().change_scene_to_file(
		scene_path
	)

	if scene_error != OK:
		push_error(
			"SceneTransition: Failed to load: " + scene_path
		)
		await _fade_screen(0.0, fade_in_duration)
		_finish_transition()
		return

	await get_tree().process_frame

	if black_hold_duration > 0.0:
		await get_tree().create_timer(
			black_hold_duration,
			true,
			false,
			true
		).timeout

	await _fade_screen(0.0, fade_in_duration)
	_finish_transition()


func _fade_screen(
	target_alpha: float,
	duration: float
) -> void:
	var tween: Tween = create_tween()
	tween.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
	tween.set_ignore_time_scale(true)

	tween.tween_property(
		black_screen,
		"modulate:a",
		target_alpha,
		duration
	)

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
