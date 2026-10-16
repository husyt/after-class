extends CanvasLayer
## Add as a CanvasLayer CHILD to SchoolHUD.tscn, not a separate Autoload.
## Since SchoolHUD is already an Autoload, the message persists between scenes.

@export var title_text: String = "ALL 5 TASKS COMPLETE!"
@export var instruction_text: String = "Go back to Hallway and press [G] Exit School to reach the transport station."

var _game_state: Node = null
var _holder: CenterContainer = null
var _refresh_timer: float = 0.0


func _ready() -> void:
	layer = 110
	process_mode = Node.PROCESS_MODE_ALWAYS
	_game_state = get_node_or_null("/root/GameState")
	_create_message()
	if _game_state == null:
		push_error("SchoolExitGuide: GameState Autoload is missing.")
		return
	var refresh_callback: Callable = Callable(self, "_refresh")
	if _game_state.has_signal("school_task_updated"):
		if not _game_state.is_connected("school_task_updated", refresh_callback):
			_game_state.connect("school_task_updated", refresh_callback)
	if _game_state.has_signal("school_tasks_reset"):
		if not _game_state.is_connected("school_tasks_reset", refresh_callback):
			_game_state.connect("school_tasks_reset", refresh_callback)
	_refresh()


func _process(delta: float) -> void:
	_refresh_timer -= delta
	if _refresh_timer <= 0.0:
		_refresh_timer = 0.25
		_refresh()


func _create_message() -> void:
	_holder = CenterContainer.new()
	_holder.name = "SchoolExitMessagePosition"
	_holder.mouse_filter = Control.MOUSE_FILTER_IGNORE
	add_child(_holder)
	_holder.set_anchors_and_offsets_preset(Control.PRESET_TOP_WIDE)
	_holder.offset_top = 112.0
	_holder.offset_bottom = 205.0

	var panel: PanelContainer = PanelContainer.new()
	panel.name = "SchoolExitMessage"
	panel.custom_minimum_size = Vector2(640.0, 0.0)
	panel.mouse_filter = Control.MOUSE_FILTER_IGNORE
	_holder.add_child(panel)

	var panel_style: StyleBoxFlat = StyleBoxFlat.new()
	panel_style.bg_color = Color(0.07, 0.15, 0.18, 0.91)
	panel_style.border_color = Color(0.95, 0.80, 0.38, 1.0)
	panel_style.set_border_width_all(2)
	panel_style.set_corner_radius_all(9)
	panel_style.content_margin_left = 14.0
	panel_style.content_margin_right = 14.0
	panel_style.content_margin_top = 9.0
	panel_style.content_margin_bottom = 9.0
	panel.add_theme_stylebox_override("panel", panel_style)

	var lines: VBoxContainer = VBoxContainer.new()
	lines.mouse_filter = Control.MOUSE_FILTER_IGNORE
	lines.add_theme_constant_override("separation", 4)
	panel.add_child(lines)

	var heading: Label = Label.new()
	heading.text = title_text
	heading.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	heading.add_theme_font_size_override("font_size", 21)
	heading.add_theme_color_override("font_color", Color("#ffe19a"))
	heading.mouse_filter = Control.MOUSE_FILTER_IGNORE
	lines.add_child(heading)

	var instructions: Label = Label.new()
	instructions.text = instruction_text
	instructions.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	instructions.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	instructions.add_theme_font_size_override("font_size", 15)
	instructions.add_theme_color_override("font_color", Color.WHITE)
	instructions.mouse_filter = Control.MOUSE_FILTER_IGNORE
	lines.add_child(instructions)

	_holder.hide()


func _refresh() -> void:
	if _game_state == null or _holder == null:
		return
	_holder.visible = bool(_game_state.call("school_tasks_complete")) and _is_school_scene()


func _is_school_scene() -> bool:
	var current_scene: Node = get_tree().current_scene
	if current_scene == null:
		return false
	var filename: String = current_scene.scene_file_path.get_file().to_lower()
	if filename.begins_with("school_"):
		return true
	return filename in [
		"classroom.tscn",
		"class_activity.tscn",
		"computer_room.tscn",
		"computer_activity.tscn",
		"library_borrowing.tscn",
		"clinic_health_checkup.tscn",
		"school_clinic.tscn",
		"school_cafeteria.tscn",
		"guidance_office.tscn"
	]
