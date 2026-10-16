extends Control

@export_file("*.tscn") var return_scene_path: String = "res://Scene/school_clinic.tscn"
@export_range(1.5, 8.0, 0.5) var examination_seconds: float = 3.5

const TASK_ID: String = "health_form"

var student_name: LineEdit
var student_id: LineEdit
var student_section: LineEdit
var student_age: SpinBox
var reason_option: OptionButton
var consent_box: CheckBox
var feedback: Label
var submit_button: Button
var black_cover: ColorRect
var cover_message: Label
var progress_bar: ProgressBar
var done_button: Button
var activity_started: bool = false
var school_hud: CanvasLayer = null
var hud_was_visible: bool = false


func _ready() -> void:
	set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	school_hud = get_node_or_null("/root/SchoolHUD") as CanvasLayer
	if school_hud != null:
		hud_was_visible = school_hud.visible
		school_hud.hide()
	_build_interface()


func _exit_tree() -> void:
	if is_instance_valid(school_hud) and hud_was_visible:
		school_hud.show()


func _build_interface() -> void:
	var background: ColorRect = ColorRect.new()
	background.color = Color("#123b49")
	add_child(background)
	background.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var outside_margin: MarginContainer = MarginContainer.new()
	add_child(outside_margin)
	outside_margin.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	outside_margin.add_theme_constant_override("margin_left", 12)
	outside_margin.add_theme_constant_override("margin_right", 12)
	outside_margin.add_theme_constant_override("margin_top", 12)
	outside_margin.add_theme_constant_override("margin_bottom", 12)

	var paper: PanelContainer = PanelContainer.new()
	paper.add_theme_stylebox_override("panel", _style(Color("#fffaf0"), Color("#d7c9a5"), 12, 14))
	outside_margin.add_child(paper)

	var main: VBoxContainer = VBoxContainer.new()
	main.add_theme_constant_override("separation", 8)
	paper.add_child(main)

	var title_line: HBoxContainer = HBoxContainer.new()
	main.add_child(title_line)

	var titles: VBoxContainer = VBoxContainer.new()
	titles.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	title_line.add_child(titles)
	titles.add_child(_label("EQUALPATH  |  SCHOOL CLINIC", 14, Color("#557a7d")))
	titles.add_child(_label("Student Health Checkup", 27, Color("#173e43")))

	var cancel: Button = _button("Back to Clinic", Color("#7b6860"))
	cancel.pressed.connect(_return_to_clinic)
	title_line.add_child(cancel)

	main.add_child(HSeparator.new())
	main.add_child(_label("Fill in the student health form before the examination.", 17, Color("#526f70")))

	var scroll: ScrollContainer = ScrollContainer.new()
	scroll.size_flags_vertical = Control.SIZE_EXPAND_FILL
	scroll.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	main.add_child(scroll)

	var form: VBoxContainer = VBoxContainer.new()
	form.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	form.add_theme_constant_override("separation", 12)
	scroll.add_child(form)

	student_name = _make_line_edit(form, "Student full name", "Enter student name")
	student_id = _make_line_edit(form, "Student ID", "Enter student ID")
	student_section = _make_line_edit(form, "Grade and section", "Example: Grade 10 - Rizal")

	form.add_child(_label("Age", 16, Color("#314d53")))
	student_age = SpinBox.new()
	student_age.min_value = 5
	student_age.max_value = 25
	student_age.value = 16
	student_age.step = 1
	student_age.custom_minimum_size.y = 38
	form.add_child(student_age)

	form.add_child(_label("Reason for clinic visit", 16, Color("#314d53")))
	reason_option = OptionButton.new()
	for reason: String in ["Routine health check", "Headache", "Stomachache", "Dizziness", "Feeling tired", "Other concern"]:
		reason_option.add_item(reason)
	reason_option.custom_minimum_size.y = 38
	form.add_child(reason_option)

	consent_box = CheckBox.new()
	consent_box.text = "I confirm this information is for the in-game health checkup."
	consent_box.add_theme_font_size_override("font_size", 15)
	form.add_child(consent_box)

	feedback = _label("", 15, Color("#a3433c"))
	form.add_child(feedback)

	submit_button = _button("Submit Form and Begin Examination", Color("#287568"))
	submit_button.pressed.connect(_submit_form)
	form.add_child(submit_button)

	main.add_child(_label("Simulation only. No health form is uploaded or saved to a server.", 13, Color("#72807d")))

	_build_black_transition()


func _build_black_transition() -> void:
	black_cover = ColorRect.new()
	black_cover.color = Color.BLACK
	black_cover.modulate.a = 0.0
	black_cover.mouse_filter = Control.MOUSE_FILTER_STOP
	add_child(black_cover)
	black_cover.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var center: CenterContainer = CenterContainer.new()
	black_cover.add_child(center)
	center.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var content: VBoxContainer = VBoxContainer.new()
	content.custom_minimum_size.x = 330
	content.add_theme_constant_override("separation", 20)
	center.add_child(content)

	cover_message = _label("Examining your health...", 28, Color.WHITE)
	cover_message.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	content.add_child(cover_message)

	progress_bar = ProgressBar.new()
	progress_bar.min_value = 0
	progress_bar.max_value = 100
	progress_bar.value = 0
	progress_bar.custom_minimum_size.y = 24
	content.add_child(progress_bar)

	done_button = _button("Done - Return to Clinic", Color("#287568"))
	done_button.pressed.connect(_return_to_clinic)
	content.add_child(done_button)
	done_button.hide()
	black_cover.hide()


func _submit_form() -> void:
	if activity_started:
		return
	if student_name.text.strip_edges().length() < 2:
		feedback.text = "Please enter the student's full name."
		return
	if student_id.text.strip_edges().is_empty():
		feedback.text = "Please enter the student ID."
		return
	if student_section.text.strip_edges().is_empty():
		feedback.text = "Please enter the grade and section."
		return
	if not consent_box.button_pressed:
		feedback.text = "Please check the confirmation box."
		return

	activity_started = true
	submit_button.disabled = true
	black_cover.show()
	progress_bar.value = 0.0
	done_button.hide()
	cover_message.text = "Starting the health examination..."

	var fade: Tween = create_tween()
	fade.tween_property(black_cover, "modulate:a", 1.0, 0.6)
	await fade.finished
	if not is_inside_tree():
		return

	cover_message.text = "Examining your health..."
	var loading: Tween = create_tween()
	loading.tween_property(progress_bar, "value", 100.0, examination_seconds)
	await loading.finished
	if not is_inside_tree():
		return

	var state: Node = get_node_or_null("/root/GameState")
	var task_recorded: bool = false
	if state != null and state.has_method("is_school_task_complete"):
		task_recorded = bool(state.call("is_school_task_complete", TASK_ID))
	if not task_recorded and state != null and state.has_method("complete_school_task"):
		task_recorded = bool(state.call("complete_school_task", TASK_ID))

	if task_recorded:
		cover_message.text = "Checkup complete!\nYour health form has been submitted."
	else:
		cover_message.text = "Checkup finished.\nThe health task could not be recorded."
	done_button.show()


func _return_to_clinic() -> void:
	if not ResourceLoader.exists(return_scene_path, "PackedScene"):
		push_error("Missing clinic scene: " + return_scene_path)
		return
	var transition: Node = get_node_or_null("/root/SceneTransition")
	if transition != null and transition.has_method("transition_to"):
		if bool(transition.call("transition_to", return_scene_path)):
			return
	get_tree().change_scene_to_file.call_deferred(return_scene_path)


func _make_line_edit(parent: VBoxContainer, field_name: String, placeholder: String) -> LineEdit:
	parent.add_child(_label(field_name, 16, Color("#314d53")))
	var entry: LineEdit = LineEdit.new()
	entry.placeholder_text = placeholder
	entry.custom_minimum_size.y = 39
	parent.add_child(entry)
	return entry


func _label(value: String, font_size: int, text_color: Color) -> Label:
	var result: Label = Label.new()
	result.text = value
	result.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	result.add_theme_font_size_override("font_size", font_size)
	result.add_theme_color_override("font_color", text_color)
	return result


func _button(value: String, bg_color: Color) -> Button:
	var result: Button = Button.new()
	result.text = value
	result.custom_minimum_size.y = 43
	result.add_theme_font_size_override("font_size", 16)
	result.add_theme_color_override("font_color", Color.WHITE)
	result.add_theme_color_override("font_hover_color", Color.WHITE)
	result.add_theme_stylebox_override("normal", _style(bg_color, bg_color.lightened(0.2), 8, 10))
	result.add_theme_stylebox_override("hover", _style(bg_color.lightened(0.1), Color.WHITE, 8, 10))
	return result


func _style(bg_color: Color, border_color: Color, radius: int, pad: int) -> StyleBoxFlat:
	var box_style: StyleBoxFlat = StyleBoxFlat.new()
	box_style.bg_color = bg_color
	box_style.border_color = border_color
	box_style.set_border_width_all(1)
	box_style.set_corner_radius_all(radius)
	box_style.content_margin_left = pad
	box_style.content_margin_right = pad
	box_style.content_margin_top = pad
	box_style.content_margin_bottom = pad
	return box_style
