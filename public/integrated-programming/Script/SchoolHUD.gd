extends CanvasLayer

signal task_completed(task_id: StringName)
signal progress_changed(completed: int, total: int)
signal challenge_completed(seconds_used: float)
signal time_expired(completed: int, total: int)
signal challenge_restarted

@export_range(1.0, 120.0, 0.5) var time_limit_minutes: float = 10.0
@export var show_tasks_on_start: bool = false
@export_file("*.tscn") var restart_scene_path: String = ""
@export var clear_saved_positions_on_restart: bool = true
@export var ring_bell_on_time_up: bool = true

const TASK_IDS: Array[String] = [
	"attend_class", "borrow_book", "health_form", "eat_lunch", "computer_activity"
]

enum RunState { READY, RUNNING, COMPLETED, EXPIRED }

const TIMER_STATE_META: StringName = &"equalpath_school_timer_state"

@onready var hud_root: Control = get_node_or_null("HUDRoot") as Control
@onready var tasks_button: Button = get_node_or_null("HUDRoot/TasksButton") as Button
@onready var settings_button: Button = get_node_or_null("HUDRoot/SettingsButton") as Button
@onready var timer_label: Label = get_node_or_null("HUDRoot/TimerLabel") as Label
@onready var task_ui: Node = get_node_or_null("TaskUI")
@onready var settings_overlay: Control = get_node_or_null("SettingsOverlay") as Control
@onready var time_up_bell: AudioStreamPlayer = get_node_or_null("TimeUpBell") as AudioStreamPlayer

var volume_slider: HSlider = null
var fullscreen_toggle: CheckButton = null
var resume_button: Button = null
var restart_button: Button = null
var game_state: Node = null

var remaining_seconds: float = 600.0
var limit_seconds: float = 600.0
var run_state: RunState = RunState.READY
var _owns_pause: bool = false
var _pause_before_settings: bool = false
var _ignoring_reset: bool = false
var _last_completed: int = -1
var _last_timer_second: int = -1
var _starting_scene_path: String = ""


func _ready() -> void:
	var main_hud: Node = get_node_or_null("/root/SchoolHUD")
	if main_hud != null and main_hud != self:
		hide()
		process_mode = Node.PROCESS_MODE_DISABLED
		queue_free()
		return
	process_mode = Node.PROCESS_MODE_ALWAYS
	layer = 50
	if time_up_bell != null:
		time_up_bell.process_mode = Node.PROCESS_MODE_ALWAYS
		time_up_bell.autoplay = false
	if hud_root == null or tasks_button == null or settings_button == null or timer_label == null or settings_overlay == null:
		push_error("SchoolHUD: Missing HUDRoot/TasksButton, TimerLabel, SettingsButton or SettingsOverlay. Check Scene tree names.")
		return

	hud_root.mouse_filter = Control.MOUSE_FILTER_IGNORE
	_move_stray_settings_controls_inside_overlay()
	volume_slider = settings_overlay.find_child("HSlider", true, false) as HSlider
	fullscreen_toggle = settings_overlay.find_child("CheckButton", true, false) as CheckButton
	resume_button = settings_overlay.find_child("Button", true, false) as Button
	restart_button = settings_overlay.find_child("Button2", true, false) as Button

	settings_overlay.process_mode = Node.PROCESS_MODE_ALWAYS
	settings_overlay.mouse_filter = Control.MOUSE_FILTER_STOP
	var dimmer: ColorRect = settings_overlay.get_node_or_null("ColorRect") as ColorRect
	if dimmer != null:
		dimmer.mouse_filter = Control.MOUSE_FILTER_STOP
	settings_overlay.hide()
	show_tasks_on_start = false
	if task_ui != null:
		_set_tasks_visible(false)

	tasks_button.pressed.connect(toggle_tasks)
	settings_button.pressed.connect(toggle_settings)
	if resume_button != null:
		resume_button.pressed.connect(close_settings)
	else:
		push_warning("SchoolHUD: Couldn't find Settings Resume Button named 'Button'.")
	if restart_button != null:
		restart_button.pressed.connect(restart_challenge)
	else:
		push_warning("SchoolHUD: Couldn't find Settings Restart Button named 'Button2'.")
	if volume_slider != null:
		volume_slider.min_value = 0.0
		volume_slider.max_value = 1.0
		volume_slider.step = 0.01
		volume_slider.value_changed.connect(_set_volume)
	if fullscreen_toggle != null:
		fullscreen_toggle.toggled.connect(_set_fullscreen)

	game_state = get_node_or_null("/root/GameState")
	if game_state == null:
		push_error("SchoolHUD: Add the GameState Autoload first.")
		return
	if game_state.has_signal("school_task_updated"):
		game_state.connect("school_task_updated", _refresh_tasks)
	if game_state.has_signal("school_tasks_reset"):
		game_state.connect("school_tasks_reset", _on_school_tasks_reset)

	_restore_or_initialize_timer()
	call_deferred("_remember_starting_scene")


func _move_stray_settings_controls_inside_overlay() -> void:
	for node_name: String in ["Label2", "HSlider", "CheckButton", "Button", "Button2", "Label", "Label3"]:
		var stray: Control = get_node_or_null(node_name) as Control
		if stray != null:
			stray.reparent(settings_overlay, true)


func _restore_or_initialize_timer() -> void:
	if game_state != null and game_state.has_meta(TIMER_STATE_META):
		var snapshot: Variant = game_state.get_meta(TIMER_STATE_META)
		if snapshot is Dictionary:
			var saved: Dictionary = snapshot
			if saved.has("remaining") and saved.has("limit") and saved.has("status"):
				limit_seconds = maxf(float(saved["limit"]), 1.0)
				remaining_seconds = clampf(float(saved["remaining"]), 0.0, limit_seconds)
				match int(saved["status"]):
					RunState.READY:
						run_state = RunState.READY
					RunState.COMPLETED:
						run_state = RunState.COMPLETED
					RunState.EXPIRED:
						run_state = RunState.EXPIRED
					_:
						run_state = RunState.RUNNING
				_last_completed = -1
				_last_timer_second = -1
				_refresh_tasks()
				_refresh_timer()
				print("SCHOOL TIMER RESTORED: ", remaining_seconds)
				return

	limit_seconds = maxf(time_limit_minutes * 60.0, 1.0)
	remaining_seconds = limit_seconds
	_run_start()
	_store_timer_state()


func _store_timer_state() -> void:
	if game_state == null or not is_instance_valid(game_state):
		return
	game_state.set_meta(TIMER_STATE_META, {
		"remaining": remaining_seconds,
		"limit": limit_seconds,
		"status": int(run_state)
	})


func _run_start() -> void:
	run_state = RunState.RUNNING
	_last_completed = -1
	_last_timer_second = -1
	_refresh_tasks()
	_refresh_timer()


func _process(delta: float) -> void:
	var locked: bool = _is_interaction_busy() or _is_scene_transitioning()
	if tasks_button != null:
		tasks_button.disabled = locked or (settings_overlay != null and settings_overlay.visible)
	if settings_button != null:
		settings_button.disabled = locked
	if run_state == RunState.RUNNING and not get_tree().paused and not locked:
		remaining_seconds = maxf(0.0, remaining_seconds - delta)
		if remaining_seconds <= 0.0:
			_finish_time_limit()
		_store_timer_state()
	_refresh_timer()


func _input(event: InputEvent) -> void:
	if not visible or not event is InputEventKey:
		return
	var key: InputEventKey = event as InputEventKey
	if not key.pressed or key.echo or _is_interaction_busy() or _is_scene_transitioning():
		return
	if key.keycode == KEY_ESCAPE or key.physical_keycode == KEY_ESCAPE:
		if settings_overlay.visible:
			close_settings()
		elif not get_tree().paused:
			open_settings()
		get_viewport().set_input_as_handled()
	elif key.keycode == KEY_Q or key.physical_keycode == KEY_Q:
		if not settings_overlay.visible and not get_tree().paused:
			toggle_tasks()
			get_viewport().set_input_as_handled()


func hide_tasks() -> void:
	_set_tasks_visible(false)


func toggle_tasks() -> void:
	if task_ui == null or get_tree().paused or _is_interaction_busy() or _is_scene_transitioning():
		return
	_set_tasks_visible(not _tasks_are_visible())


func _tasks_are_visible() -> bool:
	if task_ui is CanvasLayer:
		return (task_ui as CanvasLayer).visible
	if task_ui is CanvasItem:
		return (task_ui as CanvasItem).visible
	return false


func _set_tasks_visible(should_show: bool) -> void:
	if task_ui is CanvasLayer:
		(task_ui as CanvasLayer).visible = should_show
	elif task_ui is CanvasItem:
		(task_ui as CanvasItem).visible = should_show


func toggle_settings() -> void:
	if settings_overlay == null or _is_interaction_busy() or _is_scene_transitioning():
		return
	if settings_overlay.visible:
		close_settings()
	else:
		open_settings()


func open_settings() -> void:
	if settings_overlay == null or settings_overlay.visible or get_tree().paused or _is_interaction_busy() or _is_scene_transitioning():
		return
	_sync_settings()
	_pause_before_settings = get_tree().paused
	_owns_pause = true
	settings_overlay.show()
	get_tree().paused = true
	if resume_button != null:
		resume_button.grab_focus()


func close_settings() -> void:
	if settings_overlay != null:
		settings_overlay.hide()
	if _owns_pause:
		get_tree().paused = _pause_before_settings
		_owns_pause = false


func _refresh_tasks() -> void:
	if game_state == null:
		return
	var count: int = get_completed_count()
	if tasks_button != null:
		tasks_button.text = "TASKS %d/%d [Q]" % [count, get_total_tasks()]
	if count != _last_completed:
		progress_changed.emit(count, get_total_tasks())
		_last_completed = count


func _refresh_timer() -> void:
	if timer_label == null:
		return

	var seconds_left: int = maxi(0, int(ceil(remaining_seconds)))

	if run_state == RunState.EXPIRED:
		timer_label.text = "TIME UP"
		_last_timer_second = seconds_left
		return

	if run_state == RunState.COMPLETED:
		timer_label.text = "COMPLETE!"
		_last_timer_second = seconds_left
		return

	if _last_timer_second == seconds_left:
		return

	_last_timer_second = seconds_left
	var minutes: int = floori(float(seconds_left) / 60.0)
	var seconds: int = seconds_left % 60
	timer_label.text = "%02d:%02d" % [minutes, seconds]


func _finish_time_limit() -> void:
	if run_state != RunState.RUNNING:
		return
	remaining_seconds = 0.0
	if ring_bell_on_time_up and time_up_bell != null and time_up_bell.stream != null:
		time_up_bell.play()
	if all_tasks_complete():
		run_state = RunState.COMPLETED
		challenge_completed.emit(limit_seconds)
	else:
		run_state = RunState.EXPIRED
		time_expired.emit(get_completed_count(), get_total_tasks())
	_last_timer_second = -1
	_store_timer_state()


func start_challenge(reset_progress: bool = true) -> void:
	if time_up_bell != null:
		time_up_bell.stop()
	close_settings()
	_set_tasks_visible(false)
	if game_state == null:
		return
	if reset_progress:
		_ignoring_reset = true
		game_state.call("reset_school_tasks")
		_ignoring_reset = false
	limit_seconds = maxf(time_limit_minutes * 60.0, 1.0)
	remaining_seconds = limit_seconds
	_run_start()
	_store_timer_state()


func restart_challenge() -> void:
	if game_state == null:
		return
	if clear_saved_positions_on_restart:
		game_state.call("clear_positions")
	start_challenge(true)
	challenge_restarted.emit()
	var destination: String = restart_scene_path
	if destination.is_empty():
		destination = _starting_scene_path
	if destination.is_empty() or not ResourceLoader.exists(destination, "PackedScene"):
		return
	var trans: Node = get_node_or_null("/root/SceneTransition")
	if trans != null and trans.has_method("transition_to"):
		trans.call("transition_to", destination)
	else:
		get_tree().change_scene_to_file.call_deferred(destination)


func _remember_starting_scene() -> void:
	await get_tree().process_frame
	var scene: Node = get_tree().current_scene
	if scene != null and not scene.scene_file_path.is_empty():
		_starting_scene_path = scene.scene_file_path


func _on_school_tasks_reset() -> void:
	if _ignoring_reset:
		return
	start_challenge(false)
	challenge_restarted.emit()


func can_accept_task_completion() -> bool:
	return run_state == RunState.RUNNING and remaining_seconds > 0.0 and settings_overlay != null and not settings_overlay.visible and not get_tree().paused


func complete_task(task_id: StringName) -> bool:
	if game_state == null or not can_accept_task_completion():
		return false
	var accepted: bool = bool(game_state.call("complete_school_task", String(task_id)))
	if accepted:
		task_completed.emit(task_id)
	return accepted


func is_task_complete(task_id: StringName) -> bool:
	return game_state != null and bool(game_state.call("is_school_task_complete", String(task_id)))


func get_completed_count() -> int:
	return int(game_state.call("get_school_task_count")) if game_state != null else 0


func get_total_tasks() -> int:
	return int(game_state.call("get_school_task_total")) if game_state != null else 5


func all_tasks_complete() -> bool:
	return game_state != null and bool(game_state.call("school_tasks_complete"))


func get_remaining_seconds() -> float:
	return remaining_seconds


func get_run_state() -> RunState:
	return run_state


func _is_interaction_busy() -> bool:
	return game_state != null and bool(game_state.call("is_interaction_busy"))


func _is_scene_transitioning() -> bool:
	var trans: Node = get_node_or_null("/root/SceneTransition")
	return trans != null and bool(trans.get("is_transitioning"))


func _sync_settings() -> void:
	if volume_slider != null:
		var bus: int = AudioServer.get_bus_index("Master")
		var current_volume: float = 1.0
		if bus >= 0:
			current_volume = 0.0 if AudioServer.is_bus_mute(bus) else db_to_linear(AudioServer.get_bus_volume_db(bus))
		volume_slider.set_value_no_signal(clampf(current_volume, 0.0, 1.0))
	if fullscreen_toggle != null:
		var mode: int = DisplayServer.window_get_mode()
		fullscreen_toggle.set_pressed_no_signal(mode == DisplayServer.WINDOW_MODE_FULLSCREEN or mode == DisplayServer.WINDOW_MODE_EXCLUSIVE_FULLSCREEN)


func _set_volume(value: float) -> void:
	var bus: int = AudioServer.get_bus_index("Master")
	if bus >= 0:
		AudioServer.set_bus_mute(bus, value <= 0.0)
		AudioServer.set_bus_volume_db(bus, linear_to_db(maxf(value, 0.001)))


func _set_fullscreen(enabled: bool) -> void:
	DisplayServer.window_set_mode(DisplayServer.WINDOW_MODE_FULLSCREEN if enabled else DisplayServer.WINDOW_MODE_WINDOWED)


func _exit_tree() -> void:
	if get_node_or_null("/root/SchoolHUD") == self:
		_store_timer_state()
	if _owns_pause:
		get_tree().paused = _pause_before_settings
		_owns_pause = false
