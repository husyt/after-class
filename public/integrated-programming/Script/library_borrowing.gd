extends Control

@export_file("*.tscn") var return_scene_path: String = "res://Scene/school_library.tscn"

const TASK_ID: String = "borrow_book"
const MAX_BOOKS: int = 3
const BOOKS = [
	{"title": "Mathematics Essentials", "category": "Mathematics"},
	{"title": "Science in Everyday Life", "category": "Science"},
	{"title": "Philippine History", "category": "History"},
	{"title": "English Grammar Guide", "category": "English"},
	{"title": "Basic Computer Studies", "category": "Technology"},
	{"title": "Filipino Literature", "category": "Filipino"},
	{"title": "Earth and Environment", "category": "Geography"},
	{"title": "Introduction to Biology", "category": "Biology"},
	{"title": "Creative Writing Workshop", "category": "Writing"},
	{"title": "Digital Citizenship", "category": "ICT"}
]

var selected_books: Array[int] = []
var book_buttons: Array[Button] = []
var selection_label: Label
var selection_details: Label
var status_label: Label
var student_name_input: LineEdit
var student_id_input: LineEdit
var section_input: LineEdit
var submit_button: Button
var receipt_overlay: ColorRect
var receipt_details: Label
var submitted: bool = false
var school_hud: CanvasLayer = null
var hud_was_visible: bool = false


func _ready() -> void:
	set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	school_hud = get_node_or_null("/root/SchoolHUD") as CanvasLayer
	if school_hud != null:
		hud_was_visible = school_hud.visible
		school_hud.hide()
	_build_interface()
	_refresh_books()


func _exit_tree() -> void:
	if is_instance_valid(school_hud) and hud_was_visible:
		school_hud.show()


func _build_interface() -> void:
	var background: ColorRect = ColorRect.new()
	background.color = Color("#152e40")
	add_child(background)
	background.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var margin: MarginContainer = MarginContainer.new()
	add_child(margin)
	margin.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	margin.add_theme_constant_override("margin_left", 12)
	margin.add_theme_constant_override("margin_right", 12)
	margin.add_theme_constant_override("margin_top", 12)
	margin.add_theme_constant_override("margin_bottom", 12)

	var main_panel: PanelContainer = PanelContainer.new()
	main_panel.add_theme_stylebox_override("panel", _style(Color("#f4ecdb"), Color("#c2ad87"), 12, 14))
	margin.add_child(main_panel)

	var layout: VBoxContainer = VBoxContainer.new()
	layout.add_theme_constant_override("separation", 10)
	main_panel.add_child(layout)

	var header: HBoxContainer = HBoxContainer.new()
	header.add_theme_constant_override("separation", 10)
	layout.add_child(header)

	var header_text: VBoxContainer = VBoxContainer.new()
	header_text.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	header.add_child(header_text)
	header_text.add_child(_label("EQUALPATH  |  SCHOOL LIBRARY", 14, Color("#9b6844")))
	header_text.add_child(_label("Book Borrowing Desk", 28, Color("#273e42")))

	var exit_button: Button = _button("Back to Library", Color("#7c5750"))
	exit_button.pressed.connect(_return_to_library)
	header.add_child(exit_button)

	var instruction: Label = _label("10 available books  •  Choose up to 3  •  Fill out the form to borrow", 16, Color("#586664"))
	layout.add_child(instruction)
	layout.add_child(HSeparator.new())

	var content: HBoxContainer = HBoxContainer.new()
	content.add_theme_constant_override("separation", 12)
	content.size_flags_vertical = Control.SIZE_EXPAND_FILL
	layout.add_child(content)

	var books_panel: PanelContainer = PanelContainer.new()
	books_panel.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	books_panel.size_flags_stretch_ratio = 1.2
	books_panel.add_theme_stylebox_override("panel", _style(Color("#fff9ed"), Color("#d6c4a2"), 9, 10))
	content.add_child(books_panel)

	var catalog_layout: VBoxContainer = VBoxContainer.new()
	catalog_layout.add_theme_constant_override("separation", 8)
	books_panel.add_child(catalog_layout)
	catalog_layout.add_child(_label("AVAILABLE BOOKS (10)", 19, Color("#304b4b")))
	catalog_layout.add_child(_label("Click a title to select or deselect it.", 14, Color("#76817d")))

	var book_scroll: ScrollContainer = ScrollContainer.new()
	book_scroll.size_flags_vertical = Control.SIZE_EXPAND_FILL
	book_scroll.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	catalog_layout.add_child(book_scroll)

	var book_list: VBoxContainer = VBoxContainer.new()
	book_list.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	book_list.add_theme_constant_override("separation", 5)
	book_scroll.add_child(book_list)

	for book_index: int in range(BOOKS.size()):
		var button: Button = _button("", Color("#466e79"))
		button.custom_minimum_size.y = 54
		button.clip_text = true
		button.alignment = HORIZONTAL_ALIGNMENT_LEFT
		button.pressed.connect(_toggle_book.bind(book_index))
		book_list.add_child(button)
		book_buttons.append(button)

	var form_panel: PanelContainer = PanelContainer.new()
	form_panel.custom_minimum_size.x = 290
	form_panel.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	form_panel.add_theme_stylebox_override("panel", _style(Color("#e9e4d8"), Color("#c2ad87"), 9, 12))
	content.add_child(form_panel)

	var form_scroll: ScrollContainer = ScrollContainer.new()
	form_scroll.size_flags_vertical = Control.SIZE_EXPAND_FILL
	form_scroll.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	form_panel.add_child(form_scroll)

	var form: VBoxContainer = VBoxContainer.new()
	form.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	form.add_theme_constant_override("separation", 7)
	form_scroll.add_child(form)

	selection_label = _label("SELECTED BOOKS: 0 / 3", 18, Color("#304b4b"))
	form.add_child(selection_label)
	selection_details = _label("No books selected yet.", 15, Color("#52625f"))
	selection_details.custom_minimum_size.y = 80
	form.add_child(selection_details)
	form.add_child(HSeparator.new())
	form.add_child(_label("BORROWER INFORMATION", 18, Color("#304b4b")))

	form.add_child(_label("Student's full name", 14, Color("#485d58")))
	student_name_input = _line_edit("e.g. Alex Santos")
	form.add_child(student_name_input)

	form.add_child(_label("Student ID", 14, Color("#485d58")))
	student_id_input = _line_edit("Enter student ID")
	form.add_child(student_id_input)

	form.add_child(_label("Grade and section", 14, Color("#485d58")))
	section_input = _line_edit("e.g. Grade 10 - Rizal")
	form.add_child(section_input)

	form.add_child(_label("Return books within 7 days.", 14, Color("#8a694d")))

	status_label = _label("", 14, Color("#a34137"))
	status_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	form.add_child(status_label)

	submit_button = _button("Confirm Borrowing", Color("#31775f"))
	submit_button.custom_minimum_size.y = 48
	submit_button.pressed.connect(_submit_borrowing)
	form.add_child(submit_button)

	var footer: Label = _label("Choose at least one book. The Library task is completed only after confirming the borrowing form.", 14, Color("#66736d"))
	layout.add_child(footer)

	_build_receipt()


func _build_receipt() -> void:
	receipt_overlay = ColorRect.new()
	receipt_overlay.color = Color(0, 0, 0, 0.72)
	add_child(receipt_overlay)
	receipt_overlay.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	receipt_overlay.mouse_filter = Control.MOUSE_FILTER_STOP

	var center: CenterContainer = CenterContainer.new()
	center.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	receipt_overlay.add_child(center)

	var receipt_panel: PanelContainer = PanelContainer.new()
	receipt_panel.custom_minimum_size = Vector2(420, 330)
	receipt_panel.add_theme_stylebox_override("panel", _style(Color("#fff8e8"), Color("#c2ad87"), 14, 22))
	center.add_child(receipt_panel)

	var body: VBoxContainer = VBoxContainer.new()
	body.add_theme_constant_override("separation", 14)
	receipt_panel.add_child(body)
	body.add_child(_label("BOOKS BORROWED!", 28, Color("#277057")))
	body.add_child(_label("Your library borrowing form has been submitted.", 16, Color("#576b63")))

	receipt_details = _label("", 16, Color("#374e4a"))
	body.add_child(receipt_details)

	body.add_child(_label("Please return your books within 7 days.", 15, Color("#8a694d")))

	var done_button: Button = _button("Done - Return to Library", Color("#277057"))
	done_button.custom_minimum_size.y = 48
	done_button.pressed.connect(_return_to_library)
	body.add_child(done_button)
	receipt_overlay.hide()


func _toggle_book(book_index: int) -> void:
	if submitted:
		return
	var selected_index: int = selected_books.find(book_index)
	if selected_index >= 0:
		selected_books.remove_at(selected_index)
	elif selected_books.size() < MAX_BOOKS:
		selected_books.append(book_index)
	else:
		status_label.text = "You can borrow a maximum of 3 books."
		return
	status_label.text = ""
	_refresh_books()


func _refresh_books() -> void:
	for book_index: int in range(BOOKS.size()):
		var data: Dictionary = BOOKS[book_index]
		var is_selected: bool = selected_books.has(book_index)
		var button: Button = book_buttons[book_index]
		button.text = "%02d  %s  (%s)  %s" % [book_index + 1, str(data["title"]), str(data["category"]), "[SELECTED]" if is_selected else "+"]
		button.disabled = submitted or (selected_books.size() >= MAX_BOOKS and not is_selected)
		button.modulate = Color("#b0e1c7") if is_selected else Color.WHITE

	selection_label.text = "SELECTED BOOKS: %d / %d" % [selected_books.size(), MAX_BOOKS]
	if selected_books.is_empty():
		selection_details.text = "No books selected yet."
	else:
		var chosen_titles: Array[String] = []
		for book_index: int in selected_books:
			chosen_titles.append("• " + str(BOOKS[book_index]["title"]))
		selection_details.text = "\n".join(PackedStringArray(chosen_titles))

	submit_button.disabled = selected_books.is_empty() or submitted


func _submit_borrowing() -> void:
	if submitted:
		return
	if selected_books.is_empty():
		status_label.text = "Please choose at least one book."
		return

	var borrower_name: String = student_name_input.text.strip_edges()
	var borrower_id: String = student_id_input.text.strip_edges()
	var borrower_section: String = section_input.text.strip_edges()

	if borrower_name.length() < 2:
		status_label.text = "Please enter your full name."
		student_name_input.grab_focus()
		return
	if borrower_id.is_empty():
		status_label.text = "Please enter your student ID."
		student_id_input.grab_focus()
		return
	if borrower_section.is_empty():
		status_label.text = "Please enter your grade and section."
		section_input.grab_focus()
		return

	var game_state: Node = get_node_or_null("/root/GameState")
	if game_state == null or not game_state.has_method("complete_school_task"):
		status_label.text = "GameState is missing. Cannot record this task."
		return

	var already_complete: bool = false
	if game_state.has_method("is_school_task_complete"):
		already_complete = bool(game_state.call("is_school_task_complete", TASK_ID))

	if not already_complete and not bool(game_state.call("complete_school_task", TASK_ID)):
		status_label.text = "The library task could not be recorded."
		return

	submitted = true
	_refresh_books()
	var titles: Array[String] = []
	for book_index: int in selected_books:
		titles.append("• " + str(BOOKS[book_index]["title"]))
	receipt_details.text = "Student: %s\nStudent ID: %s\nGrade/Section: %s\n\nBorrowed %d book(s):\n%s" % [borrower_name, borrower_id, borrower_section, selected_books.size(), "\n".join(PackedStringArray(titles))]
	receipt_overlay.show()


func _return_to_library() -> void:
	if not ResourceLoader.exists(return_scene_path, "PackedScene"):
		push_error("Library scene not found: " + return_scene_path)
		return

	var transition: Node = get_node_or_null("/root/SceneTransition")
	if transition != null and transition.has_method("transition_to"):
		if bool(transition.call("transition_to", return_scene_path)):
			return

	get_tree().change_scene_to_file.call_deferred(return_scene_path)


func _label(value: String, font_size: int, color: Color) -> Label:
	var control: Label = Label.new()
	control.text = value
	control.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	control.add_theme_font_size_override("font_size", font_size)
	control.add_theme_color_override("font_color", color)
	return control


func _line_edit(placeholder: String) -> LineEdit:
	var field: LineEdit = LineEdit.new()
	field.placeholder_text = placeholder
	field.custom_minimum_size.y = 38
	field.add_theme_font_size_override("font_size", 16)
	return field


func _button(value: String, background: Color) -> Button:
	var control: Button = Button.new()
	control.text = value
	control.custom_minimum_size.y = 42
	control.add_theme_font_size_override("font_size", 15)
	control.add_theme_color_override("font_color", Color.WHITE)
	control.add_theme_color_override("font_hover_color", Color.WHITE)
	control.add_theme_color_override("font_pressed_color", Color.WHITE)
	control.add_theme_stylebox_override("normal", _style(background, background.lightened(0.18), 8, 9))
	control.add_theme_stylebox_override("hover", _style(background.lightened(0.12), Color("#ffffff"), 8, 9))
	control.add_theme_stylebox_override("pressed", _style(background.darkened(0.1), Color("#f6e5ce"), 8, 9))
	return control


func _style(background: Color, border: Color, radius: int, padding: int) -> StyleBoxFlat:
	var style: StyleBoxFlat = StyleBoxFlat.new()
	style.bg_color = background
	style.border_color = border
	style.set_border_width_all(1)
	style.set_corner_radius_all(radius)
	style.content_margin_left = padding
	style.content_margin_right = padding
	style.content_margin_top = padding
	style.content_margin_bottom = padding
	return style
