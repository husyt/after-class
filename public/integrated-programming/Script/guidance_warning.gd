extends Control

@export_file("*.tscn") var return_scene_path: String = "res://Scene/guidance_office.tscn"

const LOG_META_KEY: String = "equalpath_guidance_logbook"
const APOLOGY_COMPLETE_META_KEY: String = "equalpath_guidance_apology_completed"
const VIOLATIONS = [
	"Select a school rule violation...",
	"Sleeping in the clinic without permission",
	"Playing games during computer class",
	"Skipping or leaving class without permission",
	"Disrupting a class",
	"Disrespectful behavior",
	"Being late repeatedly",
	"Other school rule violation"
]

var _game_state: Node = null
var _school_hud: CanvasLayer = null
var _hud_was_visible: bool = false
var _phase: int = 0
var _warning_submitted: bool = false
var _saved_record: bool = false
var _warning_data: Dictionary = {}
var _entry: Dictionary = {}

var _header_status: Label = null
var _warning_tab: Button = null
var _logbook_tab: Button = null
var _warning_view: HBoxContainer = null
var _logbook_view: HBoxContainer = null
var _complete_view: CenterContainer = null

var _student_name: LineEdit = null
var _student_id: LineEdit = null
var _grade_section: LineEdit = null
var _violation: OptionButton = null
var _incident_details: TextEdit = null
var _acknowledge: CheckBox = null
var _warning_feedback: Label = null
var _warning_submit: Button = null

var _signature: LineEdit = null
var _reflection: TextEdit = null
var _logbook_summary: Label = null
var _logbook_feedback: Label = null
var _logbook_entries: VBoxContainer = null
var _completed_summary: Label = null


func _ready() -> void:
	set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	_game_state = get_node_or_null("/root/GameState")
	_school_hud = get_node_or_null("/root/SchoolHUD") as CanvasLayer
	if is_instance_valid(_school_hud):
		_hud_was_visible = _school_hud.visible
		_school_hud.hide()
	_build_interface()
	_refresh_logbook()
	_show_phase(0)


func _exit_tree() -> void:
	if is_instance_valid(_school_hud) and _hud_was_visible:
		_school_hud.show()


func _unhandled_input(event: InputEvent) -> void:
	if event is InputEventKey:
		var key_event: InputEventKey = event as InputEventKey
		if key_event.pressed and not key_event.echo and key_event.keycode == KEY_ESCAPE:
			get_viewport().set_input_as_handled()
			_return_to_guidance()


func _build_interface() -> void:
	var background: ColorRect = ColorRect.new()
	background.color = Color("#162c36")
	add_child(background)
	background.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var outer: MarginContainer = MarginContainer.new()
	add_child(outer)
	outer.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	for side: String in ["left", "right", "top", "bottom"]:
		outer.add_theme_constant_override("margin_" + side, 16)

	var paper: PanelContainer = _panel(Color("#f3ead8"), Color("#bda784"), 14)
	outer.add_child(paper)

	var main: VBoxContainer = VBoxContainer.new()
	main.size_flags_vertical = Control.SIZE_EXPAND_FILL
	main.add_theme_constant_override("separation", 10)
	paper.add_child(main)

	var header: HBoxContainer = HBoxContainer.new()
	header.add_theme_constant_override("separation", 12)
	main.add_child(header)

	var heading: VBoxContainer = VBoxContainer.new()
	heading.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	heading.add_child(_label("EQUALPATH  /  STUDENT GUIDANCE OFFICE", 13, Color("#9a7951")))
	heading.add_child(_label("Student Warning Slip & Discipline Logbook", 24, Color("#263e42")))
	header.add_child(heading)

	var return_button: Button = _button("Return to Guidance", Color("#77584e"))
	return_button.pressed.connect(_return_to_guidance)
	header.add_child(return_button)

	_header_status = _label("STEP 1 OF 2  |  Complete the warning slip", 14, Color("#667976"))
	main.add_child(_header_status)
	main.add_child(HSeparator.new())

	var tabs: HBoxContainer = HBoxContainer.new()
	tabs.add_theme_constant_override("separation", 10)
	main.add_child(tabs)
	_warning_tab = _button("1  WARNING SLIP", Color("#265f5d"))
	_warning_tab.pressed.connect(_on_warning_tab)
	tabs.add_child(_warning_tab)
	_logbook_tab = _button("2  DISCIPLINE LOGBOOK", Color("#63736c"))
	_logbook_tab.pressed.connect(_on_logbook_tab)
	tabs.add_child(_logbook_tab)

	_warning_view = _build_warning_view()
	_warning_view.size_flags_vertical = Control.SIZE_EXPAND_FILL
	main.add_child(_warning_view)

	_logbook_view = _build_logbook_view()
	_logbook_view.size_flags_vertical = Control.SIZE_EXPAND_FILL
	main.add_child(_logbook_view)

	_complete_view = _build_complete_view()
	_complete_view.size_flags_vertical = Control.SIZE_EXPAND_FILL
	main.add_child(_complete_view)

	var footer: Label = _label("EqualPath simulation  |  Records are kept only during this game session. No online submission.", 12, Color("#78827c"))
	main.add_child(footer)


func _build_warning_view() -> HBoxContainer:
	var row: HBoxContainer = HBoxContainer.new()
	row.add_theme_constant_override("separation", 12)

	var form_panel: PanelContainer = _panel(Color("#fff9ee"), Color("#d0b996"), 10)
	form_panel.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	form_panel.size_flags_stretch_ratio = 1.55
	row.add_child(form_panel)

	var scroller: ScrollContainer = ScrollContainer.new()
	scroller.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	form_panel.add_child(scroller)

	var form: VBoxContainer = VBoxContainer.new()
	form.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	form.add_theme_constant_override("separation", 8)
	scroller.add_child(form)

	form.add_child(_label("OFFICIAL STUDENT WARNING SLIP", 20, Color("#284e50")))
	form.add_child(_label("Explain what happened and acknowledge the school's rules.", 14, Color("#687971")))

	_student_name = _line_field(form, "Student full name", "e.g. Alex Reyes")
	_student_id = _line_field(form, "Student ID", "e.g. 2026-045")
	_grade_section = _line_field(form, "Grade / Year and section", "e.g. Grade 10 - Rizal")

	form.add_child(_label("Reason for referral", 15, Color("#344d50")))
	_violation = OptionButton.new()
	_violation.custom_minimum_size.y = 40
	for description: String in VIOLATIONS:
		_violation.add_item(description)
	form.add_child(_violation)

	form.add_child(_label("Describe what happened", 15, Color("#344d50")))
	_incident_details = TextEdit.new()
	_incident_details.custom_minimum_size.y = 86
	_incident_details.placeholder_text = "Briefly explain what happened and what rule was broken..."
	_incident_details.wrap_mode = TextEdit.LINE_WRAPPING_BOUNDARY
	form.add_child(_incident_details)

	_acknowledge = CheckBox.new()
	_acknowledge.text = "I understand the warning and will follow school rules."
	form.add_child(_acknowledge)

	_warning_feedback = _label("", 13, Color("#a44737"))
	form.add_child(_warning_feedback)
	_warning_submit = _button("Submit Warning Slip  →", Color("#26756b"))
	_warning_submit.pressed.connect(_submit_warning)
	form.add_child(_warning_submit)

	var sidebar: PanelContainer = _panel(Color("#e7eee8"), Color("#c3d0c3"), 10)
	sidebar.custom_minimum_size.x = 240
	sidebar.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	sidebar.size_flags_stretch_ratio = 0.7
	row.add_child(sidebar)
	var instructions: VBoxContainer = VBoxContainer.new()
	instructions.add_theme_constant_override("separation", 13)
	sidebar.add_child(instructions)
	instructions.add_child(_label("GUIDANCE PROCEDURE", 17, Color("#325e55")))
	instructions.add_child(_wrap_label("01  Complete the incident and student information.", 15, Color("#3d5955")))
	instructions.add_child(_wrap_label("02  Submit your warning slip.", 15, Color("#3d5955")))
	instructions.add_child(_wrap_label("03  Sign your name in the discipline logbook.", 15, Color("#3d5955")))
	instructions.add_child(HSeparator.new())
	instructions.add_child(_wrap_label("Every signed entry stays in the logbook during this play session. Your five school tasks and timer are not reset.", 14, Color("#6b736b")))
	return row


func _build_logbook_view() -> HBoxContainer:
	var row: HBoxContainer = HBoxContainer.new()
	row.size_flags_vertical = Control.SIZE_EXPAND_FILL
	row.add_theme_constant_override("separation", 12)

	var sign_panel: PanelContainer = _panel(Color("#fff9ee"), Color("#d0b996"), 10)
	sign_panel.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	sign_panel.size_flags_vertical = Control.SIZE_EXPAND_FILL
	sign_panel.size_flags_stretch_ratio = 0.95
	row.add_child(sign_panel)

	var form_scroll: ScrollContainer = ScrollContainer.new()
	form_scroll.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	form_scroll.size_flags_vertical = Control.SIZE_EXPAND_FILL
	form_scroll.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	form_scroll.vertical_scroll_mode = ScrollContainer.SCROLL_MODE_SHOW_ALWAYS
	form_scroll.follow_focus = true
	sign_panel.add_child(form_scroll)

	var form: VBoxContainer = VBoxContainer.new()
	form.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	form.add_theme_constant_override("separation", 12)
	form_scroll.add_child(form)

	form.add_child(_label("SIGN THE DISCIPLINE LOGBOOK", 20, Color("#284e50")))
	form.add_child(_wrap_label("Review your warning slip, then sign to record your visit to the guidance office.", 14, Color("#667976")))
	_logbook_summary = _wrap_label("No warning slip submitted yet.", 15, Color("#3b5553"))
	form.add_child(_logbook_summary)
	_signature = _line_field(form, "Student signature (type full name)", "Type your full name to sign")
	form.add_child(_label("What will you do differently next time?", 15, Color("#344d50")))

	_reflection = TextEdit.new()
	_reflection.custom_minimum_size.y = 110
	_reflection.placeholder_text = "I will follow the rules by..."
	_reflection.wrap_mode = TextEdit.LINE_WRAPPING_BOUNDARY
	form.add_child(_reflection)

	form.add_child(HSeparator.new())
	_logbook_feedback = _wrap_label("", 13, Color("#a44737"))
	form.add_child(_logbook_feedback)
	var sign_button: Button = _button("Done - Sign & Save Logbook", Color("#26756b"))
	sign_button.pressed.connect(_sign_logbook)
	form.add_child(sign_button)

	var bottom_space: Control = Control.new()
	bottom_space.custom_minimum_size.y = 12
	form.add_child(bottom_space)

	var history_panel: PanelContainer = _panel(Color("#e8eee7"), Color("#b7c9bd"), 10)
	history_panel.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	history_panel.size_flags_vertical = Control.SIZE_EXPAND_FILL
	history_panel.size_flags_stretch_ratio = 1.05
	row.add_child(history_panel)

	var history_layout: VBoxContainer = VBoxContainer.new()
	history_layout.size_flags_vertical = Control.SIZE_EXPAND_FILL
	history_layout.add_theme_constant_override("separation", 8)
	history_panel.add_child(history_layout)
	history_layout.add_child(_label("STUDENT DISCIPLINE LOGBOOK", 18, Color("#325e55")))
	history_layout.add_child(_wrap_label("Names and school rule violations recorded in this game session.", 13, Color("#647873")))

	var history_scroll: ScrollContainer = ScrollContainer.new()
	history_scroll.size_flags_vertical = Control.SIZE_EXPAND_FILL
	history_scroll.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	history_layout.add_child(history_scroll)
	_logbook_entries = VBoxContainer.new()
	_logbook_entries.add_theme_constant_override("separation", 7)
	_logbook_entries.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	history_scroll.add_child(_logbook_entries)
	return row

func _build_complete_view() -> CenterContainer:
	var center: CenterContainer = CenterContainer.new()
	var panel: PanelContainer = _panel(Color("#fff9ee"), Color("#d0b996"), 16)
	panel.custom_minimum_size.x = 400
	center.add_child(panel)
	var column: VBoxContainer = VBoxContainer.new()
	column.add_theme_constant_override("separation", 15)
	panel.add_child(column)
	var check: Label = _label("RECORD SAVED", 26, Color("#276f62"))
	check.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	column.add_child(check)
	var desc: Label = _wrap_label("Your warning slip and logbook entry were recorded.", 17, Color("#39544d"))
	desc.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	column.add_child(desc)
	_completed_summary = _wrap_label("", 16, Color("#65736d"))
	_completed_summary.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	column.add_child(_completed_summary)
	var done: Button = _button("Done — Return to Guidance Office", Color("#26756b"))
	done.pressed.connect(_return_to_guidance)
	column.add_child(done)
	return center


func _on_warning_tab() -> void:
	if not _saved_record:
		_show_phase(0)


func _on_logbook_tab() -> void:
	if _warning_submitted and not _saved_record:
		_show_phase(1)


func _show_phase(next_phase: int) -> void:
	_phase = next_phase
	_warning_view.visible = _phase == 0
	_logbook_view.visible = _phase == 1
	_complete_view.visible = _phase == 2
	_warning_tab.disabled = _phase == 2
	_logbook_tab.disabled = not _warning_submitted or _phase == 2
	match _phase:
		0:
			_header_status.text = "STEP 1 OF 2  |  Complete the warning slip"
		1:
			_header_status.text = "STEP 2 OF 2  |  Sign the discipline logbook"
		2:
			_header_status.text = "FINISHED  |  Warning slip and logbook entry saved"


func _submit_warning() -> void:
	if _warning_submitted:
		_show_phase(1)
		return
	var full_name: String = _student_name.text.strip_edges()
	var school_id: String = _student_id.text.strip_edges()
	var section: String = _grade_section.text.strip_edges()
	var explanation: String = _incident_details.text.strip_edges()
	if full_name.length() < 2:
		_warning_feedback.text = "Enter the student's full name."
		return
	if school_id.is_empty():
		_warning_feedback.text = "Enter the student ID."
		return
	if section.is_empty():
		_warning_feedback.text = "Enter the grade and section."
		return
	if _violation.selected <= 0:
		_warning_feedback.text = "Choose the school rule violation."
		return
	if explanation.length() < 8:
		_warning_feedback.text = "Describe the incident using at least 8 characters."
		return
	if not _acknowledge.button_pressed:
		_warning_feedback.text = "Check the acknowledgement box before submitting."
		return
	_warning_data = {
		"student": full_name,
		"student_id": school_id,
		"grade_section": section,
		"violation": _violation.get_item_text(_violation.selected),
		"details": explanation
	}
	_warning_submitted = true
	_signature.text = full_name
	_logbook_summary.text = "Student: %s\nStudent ID: %s\nGrade / Section: %s\nViolation: %s" % [
		full_name, school_id, section, String(_warning_data["violation"])
	]
	_warning_feedback.text = "Warning slip submitted. Continue to the logbook."
	_show_phase(1)


func _sign_logbook() -> void:
	if not _warning_submitted or _saved_record:
		return
	var signed_name: String = _signature.text.strip_edges()
	var original_name: String = String(_warning_data.get("student", ""))
	if signed_name.to_lower() != original_name.to_lower():
		_logbook_feedback.text = "Type the same full name as your warning slip to sign."
		return
	if _reflection.text.strip_edges().length() < 8:
		_logbook_feedback.text = "Write a short reflection (at least 8 characters)."
		return
	_entry = _warning_data.duplicate(true)
	_entry["signed_by"] = signed_name
	_entry["reflection"] = _reflection.text.strip_edges()
	_entry["date"] = Time.get_datetime_string_from_system(false, true)
	var records: Array = _read_records()
	records.append(_entry.duplicate(true))
	if _game_state != null:
		_game_state.set_meta(LOG_META_KEY, records)
		_game_state.set_meta(APOLOGY_COMPLETE_META_KEY, true)
	else:
		push_warning("GameState Autoload missing: Guidance records cannot survive a scene change.")
	_saved_record = true
	_completed_summary.text = "Student: %s\nViolation: %s\nLogged: %s\nTotal student entries: %d" % [
		String(_entry["student"]),
		String(_entry["violation"]),
		String(_entry["date"]),
		records.size()
	]
	_refresh_logbook()
	_show_phase(2)


func _read_records() -> Array:
	if _game_state == null or not _game_state.has_meta(LOG_META_KEY):
		return []
	var stored: Variant = _game_state.get_meta(LOG_META_KEY)
	if stored is Array:
		return (stored as Array).duplicate(true)
	return []


func _refresh_logbook() -> void:
	if _logbook_entries == null:
		return
	for child: Node in _logbook_entries.get_children():
		child.queue_free()
	var records: Array = _read_records()
	if records.is_empty():
		_logbook_entries.add_child(_wrap_label("No student violations have been recorded yet. Sign the warning slip to make the first entry.", 14, Color("#62766f")))
		return
	for offset: int in range(mini(15, records.size())):
		var index: int = records.size() - 1 - offset
		var info: Dictionary = records[index] as Dictionary
		var item: PanelContainer = _panel(Color("#f8f6ec"), Color("#d1d8cb"), 7)
		_logbook_entries.add_child(item)
		var detail: VBoxContainer = VBoxContainer.new()
		detail.add_theme_constant_override("separation", 3)
		item.add_child(detail)
		detail.add_child(_wrap_label("%d. %s" % [index + 1, String(info.get("student", "Unknown"))], 16, Color("#34554f")))
		detail.add_child(_wrap_label(String(info.get("violation", "")), 13, Color("#64776a")))
		detail.add_child(_wrap_label(String(info.get("date", "")), 12, Color("#7c847a")))


func _return_to_guidance() -> void:
	if not ResourceLoader.exists(return_scene_path, "PackedScene"):
		push_error("Missing Guidance Office scene: " + return_scene_path)
		return
	var transition: Node = get_node_or_null("/root/SceneTransition")
	if transition != null and transition.has_method("transition_to"):
		if bool(transition.call("transition_to", return_scene_path)):
			return
	get_tree().change_scene_to_file.call_deferred(return_scene_path)


func _line_field(parent: VBoxContainer, label_text: String, placeholder: String) -> LineEdit:
	parent.add_child(_label(label_text, 15, Color("#344d50")))
	var input: LineEdit = LineEdit.new()
	input.placeholder_text = placeholder
	input.custom_minimum_size.y = 38
	input.add_theme_font_size_override("font_size", 15)
	parent.add_child(input)
	return input


func _label(value: String, font_size: int, tone: Color) -> Label:
	var item: Label = Label.new()
	item.text = value
	item.add_theme_font_size_override("font_size", font_size)
	item.add_theme_color_override("font_color", tone)
	return item


func _wrap_label(value: String, font_size: int, tone: Color) -> Label:
	var item: Label = _label(value, font_size, tone)
	item.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	return item


func _button(value: String, background_color: Color) -> Button:
	var item: Button = Button.new()
	item.text = value
	item.custom_minimum_size.y = 42
	item.add_theme_font_size_override("font_size", 15)
	item.add_theme_color_override("font_color", Color.WHITE)
	item.add_theme_stylebox_override("normal", _style(background_color, background_color.lightened(0.2), 8))
	item.add_theme_stylebox_override("hover", _style(background_color.lightened(0.12), background_color.lightened(0.2), 8))
	item.add_theme_stylebox_override("pressed", _style(background_color.darkened(0.1), background_color.lightened(0.2), 8))
	return item


func _panel(background_color: Color, border_color: Color, padding: int) -> PanelContainer:
	var item: PanelContainer = PanelContainer.new()
	item.add_theme_stylebox_override("panel", _style(background_color, border_color, padding))
	return item


func _style(background_color: Color, border_color: Color, padding: int) -> StyleBoxFlat:
	var item: StyleBoxFlat = StyleBoxFlat.new()
	item.bg_color = background_color
	item.border_color = border_color
	item.set_border_width_all(1)
	item.set_corner_radius_all(9)
	item.content_margin_left = float(padding)
	item.content_margin_right = float(padding)
	item.content_margin_top = float(padding)
	item.content_margin_bottom = float(padding)
	return item
