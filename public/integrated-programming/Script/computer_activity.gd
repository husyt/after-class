extends Control

@export_file("*.tscn") var return_scene_path: String = "res://Scene/computer_room.tscn"
@export_file("*.tscn") var guidance_office_scene_path: String = "res://Scene/guidance_office.tscn"
@export_range(8.0, 30.0, 0.5) var teacher_catch_seconds: float = 13.0

const TASK_ID: String = "computer_activity"
const MINE_COLS: int = 8
const MINE_ROWS: int = 8
const MINE_COUNT: int = 10
const REQUIRED_APPS: Array[String] = ["quiz", "typing", "matching", "number"]
const MATCH_CHOICES: Array[String] = ["Input device", "Output device", "Storage", "Software"]
const QUIZ_DATA = [
	{"q": "What does a CPU do?", "a": ["Processes instructions", "Prints paper", "Shows images"], "correct": 0},
	{"q": "Which device is used to type?", "a": ["Monitor", "Keyboard", "Speaker"], "correct": 1},
	{"q": "Which shortcut copies text?", "a": ["Ctrl + V", "Ctrl + X", "Ctrl + C"], "correct": 2},
	{"q": "What should you do with an unknown link?", "a": ["Open it", "Avoid it", "Share it"], "correct": 1}
]
const TYPING_WORDS: Array[String] = ["computer", "keyboard", "digital citizenship"]
const MATCH_DATA = [
	{"term": "Keyboard", "answer": "Input device"},
	{"term": "Monitor", "answer": "Output device"},
	{"term": "Mouse", "answer": "Input device"},
	{"term": "Printer", "answer": "Output device"}
]

var completed_apps: Dictionary = {"quiz": false, "typing": false, "matching": false, "number": false}
var game_state: Node = null
var school_hud: CanvasLayer = null
var hud_was_visible: bool = false
var current_app: String = ""
var finished: bool = false
var caught: bool = false
var transitioning: bool = false

var desktop_icons: Array[Control] = []
var progress_label: Label
var clock_label: Label
var desktop_bar: PanelContainer
var window_layer: Control
var window_panel: PanelContainer
var window_title: Label
var window_content: VBoxContainer
var teacher_timer: Timer
var warning_timer: Timer
var warning_label: Label = null

var quiz_index: int = 0
var quiz_answered: bool = false
var quiz_feedback: Label = null
var quiz_next: Button = null
var quiz_buttons: Array[Button] = []
var typing_index: int = 0
var match_selects: Dictionary = {}
var number_secret: int = 0
var number_tries: int = 0
var number_feedback: Label = null
var number_tries_label: Label = null
var mine_values: Array[int] = []
var mine_revealed: Array[bool] = []
var mine_flagged: Array[bool] = []
var mine_buttons: Array[Button] = []
var mine_generated: bool = false
var mine_flag_mode: bool = false
var mine_flag_button: Button = null
var mine_finished: bool = false
var mine_reveal_count: int = 0
var mine_flag_count: int = 0
var mine_wins: int = 0
var mine_status: Label = null


func _ready() -> void:
	game_state = get_node_or_null("/root/GameState")
	school_hud = get_node_or_null("/root/SchoolHUD") as CanvasLayer
	if school_hud != null:
		hud_was_visible = school_hud.visible
		school_hud.hide()
	set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	mouse_filter = Control.MOUSE_FILTER_STOP
	randomize()
	_build_ui()
	_layout_desktop()
	_refresh_progress()
	resized.connect(_layout_desktop)


func _exit_tree() -> void:
	if is_instance_valid(school_hud) and hud_was_visible:
		school_hud.show()


func _process(_delta: float) -> void:
	if clock_label != null:
		var time_data: Dictionary = Time.get_time_dict_from_system()
		var hour_24: int = int(time_data["hour"])
		var hour_12: int = hour_24 % 12
		if hour_12 == 0:
			hour_12 = 12
		var meridiem: String = "AM" if hour_24 < 12 else "PM"
		clock_label.text = "%02d:%02d %s" % [hour_12, int(time_data["minute"]), meridiem]


func _draw() -> void:
	draw_rect(Rect2(Vector2.ZERO, size), Color("#12345c"))
	var w: float = size.x
	var h: float = size.y
	var focus: Vector2 = Vector2(w * 0.72, h * 0.49)
	draw_colored_polygon(PackedVector2Array([Vector2(0, 0), Vector2(w, 0), focus]), Color("#164774"))
	draw_colored_polygon(PackedVector2Array([Vector2(w, 0), Vector2(w, h), focus]), Color("#1b679f"))
	draw_colored_polygon(PackedVector2Array([Vector2(0, h), Vector2(w, h), focus]), Color("#105280"))
	draw_colored_polygon(PackedVector2Array([Vector2(0, h * 0.55), Vector2(0, h), focus]), Color("#0b294d"))
	var cell: float = minf(w * 0.10, h * 0.18)
	var gap: float = maxf(5.0, cell * 0.08)
	var x: float = w * 0.74
	var y: float = h * 0.38
	for row: int in range(2):
		for col: int in range(2):
			draw_rect(Rect2(Vector2(x + col * (cell + gap), y + row * (cell + gap)), Vector2(cell, cell)), Color(0.43, 0.80, 1.0, 0.43))


func _build_ui() -> void:
	var desktop: Control = Control.new()
	desktop.name = "Desktop"
	add_child(desktop)
	desktop.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	_add_desktop_icon(desktop, "quiz", "Q", "Quiz Activity")
	_add_desktop_icon(desktop, "typing", "T", "Typing Activity")
	_add_desktop_icon(desktop, "matching", "M", "Matching Activity")
	_add_desktop_icon(desktop, "number", "N", "Number Guess")
	_add_desktop_icon(desktop, "mini", "*", "Minesweeper")

	progress_label = _label("Activities completed: 0 / 4", 16, Color.WHITE)
	progress_label.autowrap_mode = TextServer.AUTOWRAP_OFF
	progress_label.custom_minimum_size = Vector2(310.0, 30.0)
	progress_label.size = Vector2(310.0, 30.0)
	desktop.add_child(progress_label)

	desktop_bar = PanelContainer.new()
	desktop_bar.name = "Taskbar"
	desktop_bar.add_theme_stylebox_override("panel", _style(Color("#081525"), Color("#365779"), 0, 1))
	add_child(desktop_bar)
	desktop_bar.set_anchors_and_offsets_preset(Control.PRESET_BOTTOM_WIDE)
	desktop_bar.offset_top = -48
	desktop_bar.offset_bottom = 0

	var bar: HBoxContainer = HBoxContainer.new()
	bar.add_theme_constant_override("separation", 9)
	desktop_bar.add_child(bar)
	var start: Button = _button("Start", Color("#286da9"), 34)
	start.pressed.connect(_open_start_menu)
	bar.add_child(start)
	var exit_button: Button = _button("Exit", Color("#913d52"), 34)
	exit_button.pressed.connect(_return_to_room)
	bar.add_child(exit_button)
	var stretch: Control = Control.new()
	stretch.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	bar.add_child(stretch)
	clock_label = _label("12:00 AM", 15, Color.WHITE)
	clock_label.autowrap_mode = TextServer.AUTOWRAP_OFF
	clock_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_RIGHT
	clock_label.custom_minimum_size.x = 104
	bar.add_child(clock_label)

	window_layer = Control.new()
	window_layer.name = "AppOverlay"
	add_child(window_layer)
	window_layer.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	window_layer.mouse_filter = Control.MOUSE_FILTER_STOP
	var shade: ColorRect = ColorRect.new()
	shade.color = Color(0.0, 0.0, 0.0, 0.50)
	window_layer.add_child(shade)
	shade.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	window_panel = PanelContainer.new()
	window_panel.name = "AppWindow"
	window_panel.add_theme_stylebox_override("panel", _style(Color("#f4f7fb"), Color("#708aa9"), 12, 2))
	window_layer.add_child(window_panel)
	var column: VBoxContainer = VBoxContainer.new()
	column.add_theme_constant_override("separation", 0)
	window_panel.add_child(column)

	var header: HBoxContainer = HBoxContainer.new()
	header.custom_minimum_size.y = 47
	header.add_theme_constant_override("separation", 8)
	column.add_child(header)
	window_title = _label("Application", 20, Color("#263950"))
	window_title.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	header.add_child(window_title)
	var close: Button = _button("X", Color("#ab4554"), 35)
	close.custom_minimum_size.x = 43
	close.pressed.connect(_close_app)
	header.add_child(close)
	column.add_child(HSeparator.new())
	var scroll: ScrollContainer = ScrollContainer.new()
	scroll.size_flags_vertical = Control.SIZE_EXPAND_FILL
	scroll.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	column.add_child(scroll)
	var content_margin: MarginContainer = MarginContainer.new()
	content_margin.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	content_margin.add_theme_constant_override("margin_left", 12)
	content_margin.add_theme_constant_override("margin_right", 12)
	content_margin.add_theme_constant_override("margin_top", 12)
	content_margin.add_theme_constant_override("margin_bottom", 12)
	scroll.add_child(content_margin)
	window_content = VBoxContainer.new()
	window_content.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	window_content.add_theme_constant_override("separation", 12)
	content_margin.add_child(window_content)
	window_layer.hide()

	teacher_timer = Timer.new()
	teacher_timer.one_shot = true
	teacher_timer.timeout.connect(_teacher_caught)
	add_child(teacher_timer)
	warning_timer = Timer.new()
	warning_timer.one_shot = true
	warning_timer.timeout.connect(_teacher_warning)
	add_child(warning_timer)


func _add_desktop_icon(parent: Control, app_id: String, letter: String, label_text: String) -> void:
	var group: VBoxContainer = VBoxContainer.new()
	group.add_theme_constant_override("separation", 3)
	parent.add_child(group)
	desktop_icons.append(group)
	var icon_button: Button = _button(letter, Color("#287bc3"), 60)
	icon_button.add_theme_font_size_override("font_size", 29)
	icon_button.pressed.connect(_open_app.bind(app_id))
	group.add_child(icon_button)
	var caption: Label = _label(label_text, 13, Color.WHITE)
	caption.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	caption.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	group.add_child(caption)


func _layout_desktop() -> void:
	if desktop_bar == null:
		return
	var scale_factor: float = clampf(minf(size.x / 800.0, size.y / 600.0), 0.68, 1.15)
	var icon_width: float = 108.0 * scale_factor
	var row_step: float = 136.0 * scale_factor
	var col_step: float = 124.0 * scale_factor
	for index: int in range(desktop_icons.size()):
		var icon: Control = desktop_icons[index]
		icon.position = Vector2(24.0 * scale_factor + float(index % 2) * col_step, 28.0 * scale_factor + floorf(float(index) / 2.0) * row_step)
		icon.custom_minimum_size.x = icon_width
		icon.size.x = icon_width
	progress_label.position = Vector2(22.0 * scale_factor, size.y - 91.0)
	progress_label.size = Vector2(310.0, 30.0)
	var width: float = minf(635.0, maxf(340.0, size.x - 28.0))
	var height: float = minf(465.0, maxf(300.0, size.y - 85.0))
	window_panel.position = Vector2((size.x - width) * 0.5, maxf(8.0, (size.y - 48.0 - height) * 0.5))
	window_panel.size = Vector2(width, height)
	queue_redraw()


func _open_start_menu() -> void:
	if caught or transitioning:
		return
	_stop_teacher()
	current_app = "start"
	_show_window("EqualOS - Start")
	_add_text("EQUALPATH COMPUTER", 26, Color("#203b5a"))
	_add_text("Choose an application from the desktop.", 17, Color("#455a71"))
	_add_text("Four required activities must be completed to earn the Computer Activity task.", 16, Color("#455a71"))
	_add_text("Minesweeper is a distraction. If the teacher catches you playing it, the school challenge restarts.", 16, Color("#ad4d44"))


func _open_app(app_id: String) -> void:
	if finished or caught or transitioning:
		return
	_stop_teacher()
	current_app = app_id
	match app_id:
		"quiz":
			quiz_index = 0
			_show_quiz()
		"typing":
			typing_index = 0
			_show_typing()
		"matching":
			_show_matching()
		"number":
			number_secret = randi_range(1, 10)
			number_tries = 0
			_show_number()
		"mini":
			_show_mini_game()


func _show_window(title_text: String) -> void:
	_clear_content()
	window_title.text = title_text
	window_layer.show()
	_layout_desktop()


func _close_app() -> void:
	if caught or transitioning:
		return
	_stop_teacher()
	current_app = ""
	window_layer.hide()


func _clear_content() -> void:
	for child: Node in window_content.get_children():
		window_content.remove_child(child)
		child.queue_free()
	quiz_buttons.clear()
	match_selects.clear()


func _show_quiz() -> void:
	_show_window("Quiz Activity")
	quiz_answered = false
	var data: Dictionary = QUIZ_DATA[quiz_index]
	_add_text("Question %d of %d" % [quiz_index + 1, QUIZ_DATA.size()], 15, Color("#697b91"))
	_add_text(str(data["q"]), 23, Color("#203b5a"))
	var choices: Array = data["a"]
	for index: int in range(choices.size()):
		var option: Button = _button("%s.  %s" % [["A", "B", "C"][index], str(choices[index])], Color("#3d6d9a"), 48)
		option.pressed.connect(_quiz_choice.bind(index))
		window_content.add_child(option)
		quiz_buttons.append(option)
	quiz_feedback = _label("Choose the correct answer.", 16, Color("#54657a"))
	window_content.add_child(quiz_feedback)
	quiz_next = _button("Next Question", Color("#277d65"), 44)
	quiz_next.pressed.connect(_quiz_advance)
	window_content.add_child(quiz_next)
	quiz_next.hide()


func _quiz_choice(index: int) -> void:
	if quiz_answered:
		return
	var data: Dictionary = QUIZ_DATA[quiz_index]
	if index != int(data["correct"]):
		quiz_feedback.text = "Not quite. Try another answer."
		quiz_buttons[index].disabled = true
		return
	quiz_answered = true
	quiz_feedback.text = "Correct!"
	for button: Button in quiz_buttons:
		button.disabled = true
	quiz_next.text = "Finish Quiz" if quiz_index == QUIZ_DATA.size() - 1 else "Next Question"
	quiz_next.show()


func _quiz_advance() -> void:
	if not quiz_answered:
		return
	quiz_index += 1
	if quiz_index >= QUIZ_DATA.size():
		_complete_activity("quiz")
	else:
		_show_quiz()


func _show_typing() -> void:
	_show_window("Typing Activity")
	_add_text("Word %d of %d" % [typing_index + 1, TYPING_WORDS.size()], 15, Color("#697b91"))
	_add_text("Type the word or phrase exactly:", 18, Color("#344a63"))
	var expected: String = TYPING_WORDS[typing_index]
	_add_text(expected, 25, Color("#285f99"))
	var entry: LineEdit = LineEdit.new()
	entry.placeholder_text = "Type here"
	entry.custom_minimum_size.y = 45
	window_content.add_child(entry)
	var feedback: Label = _label("", 16, Color("#a34c40"))
	window_content.add_child(feedback)
	var submit: Button = _button("Submit", Color("#277d65"), 44)
	submit.pressed.connect(_typing_submit.bind(entry, feedback, submit))
	window_content.add_child(submit)
	entry.text_submitted.connect(_typing_entered.bind(entry, feedback, submit))
	entry.grab_focus.call_deferred()


func _typing_entered(_entered: String, entry: LineEdit, feedback: Label, submit: Button) -> void:
	_typing_submit(entry, feedback, submit)


func _typing_submit(entry: LineEdit, feedback: Label, submit: Button) -> void:
	if not is_instance_valid(entry) or not entry.editable:
		return
	var expected: String = TYPING_WORDS[typing_index]
	if entry.text.strip_edges().to_lower() != expected.to_lower():
		feedback.text = "Check your spelling and try again."
		return
	entry.editable = false
	submit.disabled = true
	typing_index += 1
	if typing_index >= TYPING_WORDS.size():
		_complete_activity("typing")
	else:
		_show_typing()


func _show_matching() -> void:
	_show_window("Matching Activity")
	_add_text("Match each computer item with its category.", 20, Color("#203b5a"))
	for item: Dictionary in MATCH_DATA:
		var name_text: String = str(item["term"])
		var line: HBoxContainer = HBoxContainer.new()
		line.add_theme_constant_override("separation", 12)
		window_content.add_child(line)
		var term: Label = _label(name_text, 17, Color("#203b5a"))
		term.custom_minimum_size.x = 135
		line.add_child(term)
		var selector: OptionButton = OptionButton.new()
		selector.add_item("Choose a category", 0)
		for index: int in range(MATCH_CHOICES.size()):
			selector.add_item(MATCH_CHOICES[index], index + 1)
		selector.size_flags_horizontal = Control.SIZE_EXPAND_FILL
		line.add_child(selector)
		match_selects[name_text] = selector
	var feedback: Label = _label("", 16, Color("#a34c40"))
	window_content.add_child(feedback)
	var verify: Button = _button("Check Matches", Color("#277d65"), 45)
	verify.pressed.connect(_matching_submit.bind(feedback))
	window_content.add_child(verify)


func _matching_submit(feedback: Label) -> void:
	for item: Dictionary in MATCH_DATA:
		var selector: OptionButton = match_selects.get(str(item["term"])) as OptionButton
		if selector == null or selector.selected <= 0:
			feedback.text = "Select an answer for every item."
			return
		if selector.get_item_text(selector.selected) != str(item["answer"]):
			feedback.text = "Some matches are incorrect. Try again."
			return
	_complete_activity("matching")


func _show_number() -> void:
	_show_window("Number Guess")
	_add_text("Guess the hidden number between 1 and 10.", 22, Color("#203b5a"))
	number_tries_label = _label("Tries left: %d" % (5 - number_tries), 17, Color("#4c627c"))
	window_content.add_child(number_tries_label)
	var picker: SpinBox = SpinBox.new()
	picker.min_value = 1
	picker.max_value = 10
	picker.step = 1
	picker.value = 1
	picker.custom_minimum_size.y = 45
	window_content.add_child(picker)
	number_feedback = _label("", 16, Color("#9b4b44"))
	window_content.add_child(number_feedback)
	var guess: Button = _button("Guess", Color("#277d65"), 46)
	guess.pressed.connect(_number_submit.bind(picker))
	window_content.add_child(guess)


func _number_submit(picker: SpinBox) -> void:
	if number_tries >= 5:
		return
	var value: int = int(picker.value)
	number_tries += 1
	if value == number_secret:
		_complete_activity("number")
		return
	if number_tries >= 5:
		number_secret = randi_range(1, 10)
		number_tries = 0
		number_feedback.text = "Out of tries. New number generated."
	else:
		number_feedback.text = "Too low!" if value < number_secret else "Too high!"
	number_tries_label.text = "Tries left: %d" % (5 - number_tries)


func _show_mini_game() -> void:
	_mines_new_board()
	var catch_delay: float = maxf(8.0, teacher_catch_seconds)
	warning_timer.start(catch_delay - 3.0)
	teacher_timer.start(catch_delay)


func _mines_new_board() -> void:
	mine_values.clear()
	mine_revealed.clear()
	mine_flagged.clear()
	mine_buttons.clear()
	mine_generated = false
	mine_flag_mode = false
	mine_finished = false
	mine_reveal_count = 0
	mine_flag_count = 0

	for index: int in range(MINE_ROWS * MINE_COLS):
		mine_values.append(0)
		mine_revealed.append(false)
		mine_flagged.append(false)

	_show_window("Minesweeper - Distraction")
	_add_text("MINESWEEPER", 22, Color("#263950"))
	_add_text("Click to reveal. Right-click or use Flag Mode to mark mines. Clear every safe tile to win.", 14, Color("#40526b"))

	warning_label = _label("Teacher is monitoring the computers...", 15, Color("#a34a45"))
	window_content.add_child(warning_label)

	mine_status = _label("Mines: %d   Flags: 0   Wins: %d" % [MINE_COUNT, mine_wins], 16, Color("#35566f"))
	window_content.add_child(mine_status)

	var grid: GridContainer = GridContainer.new()
	grid.columns = MINE_COLS
	grid.add_theme_constant_override("h_separation", 3)
	grid.add_theme_constant_override("v_separation", 3)
	window_content.add_child(grid)

	for index: int in range(MINE_ROWS * MINE_COLS):
		var cell: Button = _button("", Color("#3d6f9c"), 34)
		cell.custom_minimum_size.x = 36
		cell.add_theme_font_size_override("font_size", 18)
		cell.add_theme_color_override("font_disabled_color", Color("#173b57"))
		cell.pressed.connect(_mines_reveal.bind(index))
		cell.gui_input.connect(_mines_on_cell_input.bind(index))
		grid.add_child(cell)
		mine_buttons.append(cell)

	var actions: HBoxContainer = HBoxContainer.new()
	actions.add_theme_constant_override("separation", 8)
	window_content.add_child(actions)

	var new_board: Button = _button("New Board", Color("#356b9a"), 39)
	new_board.pressed.connect(_mines_new_board)
	actions.add_child(new_board)

	mine_flag_button = _button("Flag Mode: OFF", Color("#795c73"), 39)
	mine_flag_button.pressed.connect(_mines_toggle_flag_mode)
	actions.add_child(mine_flag_button)

	var hide_game: Button = _button("Hide Game", Color("#277d65"), 39)
	hide_game.pressed.connect(_close_app)
	actions.add_child(hide_game)


func _mines_toggle_flag_mode() -> void:
	mine_flag_mode = not mine_flag_mode
	if is_instance_valid(mine_flag_button):
		mine_flag_button.text = "Flag Mode: ON" if mine_flag_mode else "Flag Mode: OFF"


func _mines_on_cell_input(event: InputEvent, index: int) -> void:
	if event is InputEventMouseButton:
		var mouse: InputEventMouseButton = event as InputEventMouseButton
		if mouse.pressed and mouse.button_index == MOUSE_BUTTON_RIGHT:
			get_viewport().set_input_as_handled()
			_mines_toggle_flag(index)


func _mines_toggle_flag(index: int) -> void:
	if current_app != "mini" or mine_finished or mine_revealed[index]:
		return

	mine_flagged[index] = not mine_flagged[index]
	mine_flag_count += 1 if mine_flagged[index] else -1
	_mines_draw_cell(index)
	_mines_update_status()


func _mines_neighbors(index: int) -> Array[int]:
	var neighbors: Array[int] = []
	var row: int = floori(float(index) / float(MINE_COLS))
	var col: int = index % MINE_COLS

	for dy: int in range(-1, 2):
		for dx: int in range(-1, 2):
			if dx == 0 and dy == 0:
				continue
			var nr: int = row + dy
			var nc: int = col + dx
			if nr >= 0 and nr < MINE_ROWS and nc >= 0 and nc < MINE_COLS:
				neighbors.append(nr * MINE_COLS + nc)

	return neighbors


func _mines_place_bombs(safe_index: int) -> void:
	var safe_cells: Dictionary = {}
	safe_cells[safe_index] = true
	for neighbor: int in _mines_neighbors(safe_index):
		safe_cells[neighbor] = true

	var choices: Array[int] = []
	for index: int in range(MINE_ROWS * MINE_COLS):
		if not safe_cells.has(index):
			choices.append(index)

	choices.shuffle()
	for i: int in range(MINE_COUNT):
		mine_values[choices[i]] = -1

	for index: int in range(mine_values.size()):
		if mine_values[index] == -1:
			continue
		var count: int = 0
		for neighbor: int in _mines_neighbors(index):
			if mine_values[neighbor] == -1:
				count += 1
		mine_values[index] = count

	mine_generated = true


func _mines_reveal(index: int) -> void:
	if current_app != "mini" or mine_finished:
		return
	if mine_flag_mode:
		_mines_toggle_flag(index)
		return
	if mine_revealed[index] or mine_flagged[index]:
		return

	if not mine_generated:
		_mines_place_bombs(index)

	if mine_values[index] == -1:
		mine_revealed[index] = true
		_mines_finish(false)
		return

	var stack: Array[int] = [index]
	while not stack.is_empty():
		var cell_index: int = int(stack.pop_back())
		if mine_revealed[cell_index] or mine_flagged[cell_index]:
			continue
		if mine_values[cell_index] == -1:
			continue
		mine_revealed[cell_index] = true
		mine_reveal_count += 1
		_mines_draw_cell(cell_index)

		if mine_values[cell_index] == 0:
			for neighbor: int in _mines_neighbors(cell_index):
				if not mine_revealed[neighbor] and not mine_flagged[neighbor]:
					stack.append(neighbor)

	_mines_update_status()
	if mine_reveal_count == MINE_ROWS * MINE_COLS - MINE_COUNT:
		_mines_finish(true)


func _mines_draw_cell(index: int) -> void:
	var cell: Button = mine_buttons[index]
	if mine_revealed[index]:
		cell.disabled = true
		if mine_values[index] == -1:
			cell.text = "*"
			cell.add_theme_color_override("font_disabled_color", Color("#b8313c"))
			cell.add_theme_stylebox_override("disabled", _style(Color("#f6c4c7"), Color("#ba656b"), 4, 1))
		else:
			var adjacent: int = mine_values[index]
			cell.text = "" if adjacent == 0 else str(adjacent)
			var number_color: Color = Color("#294977")
			if adjacent == 2:
				number_color = Color("#287651")
			elif adjacent >= 3:
				number_color = Color("#a34347")
			cell.add_theme_color_override("font_disabled_color", number_color)
			cell.add_theme_stylebox_override("disabled", _style(Color("#e4edf2"), Color("#b6c8d7"), 4, 1))
	elif mine_flagged[index]:
		cell.text = "F"
		cell.add_theme_color_override("font_color", Color("#ffe09d"))
		cell.add_theme_stylebox_override("normal", _style(Color("#795c73"), Color("#a28b9d"), 5, 1))
	else:
		cell.text = ""
		cell.add_theme_stylebox_override("normal", _style(Color("#3d6f9c"), Color("#6f9ec7"), 5, 1))


func _mines_update_status() -> void:
	if is_instance_valid(mine_status):
		mine_status.text = "Mines: %d   Flags: %d   Wins: %d" % [MINE_COUNT, mine_flag_count, mine_wins]


func _mines_finish(won: bool) -> void:
	mine_finished = true
	if won:
		mine_wins += 1
		mine_status.text = "YOU CLEARED THE BOARD!   Wins: %d" % mine_wins
		mine_status.add_theme_color_override("font_color", Color("#1c825e"))
	else:
		for index: int in range(mine_values.size()):
			if mine_values[index] == -1:
				mine_revealed[index] = true
				_mines_draw_cell(index)
		mine_status.text = "BOOM! You hit a mine. Try a New Board."
		mine_status.add_theme_color_override("font_color", Color("#b8313c"))

	for index: int in range(mine_buttons.size()):
		mine_buttons[index].disabled = true

func _teacher_warning() -> void:
	if current_app == "mini" and is_instance_valid(warning_label):
		warning_label.text = "WARNING: The teacher is approaching! Close the game!"
		warning_label.add_theme_color_override("font_color", Color("#c12d35"))


func _teacher_caught() -> void:
	if current_app != "mini" or caught or transitioning:
		return

	_stop_teacher()

	if not ResourceLoader.exists(guidance_office_scene_path, "PackedScene"):
		push_error("Guidance Office scene not found: " + guidance_office_scene_path)
		_show_window("Guidance Office Not Found")
		_add_text("Set the correct Guidance Office Scene Path in the Inspector.", 19, Color("#ab4145"))
		_add_text(guidance_office_scene_path, 16, Color("#334d65"))
		var exit_button: Button = _button("Return to Computer Room", Color("#346b94"), 44)
		exit_button.pressed.connect(_return_to_room)
		window_content.add_child(exit_button)
		current_app = "guidance_missing"
		return

	caught = true
	transitioning = true
	current_app = "game_over"

	if is_instance_valid(school_hud):
		if school_hud.has_method("hide_tasks"):
			school_hud.call("hide_tasks")
		else:
			var tasks: Node = school_hud.get_node_or_null("TaskUI")
			if tasks is CanvasLayer:
				(tasks as CanvasLayer).hide()
			elif tasks is CanvasItem:
				(tasks as CanvasItem).hide()
		school_hud.hide()

	var overlay: CanvasLayer = CanvasLayer.new()
	overlay.name = "TeacherCaughtOverlay"
	overlay.layer = 1000
	overlay.process_mode = Node.PROCESS_MODE_ALWAYS
	get_tree().root.add_child(overlay)

	var full_screen: Control = Control.new()
	full_screen.name = "FullScreen"
	full_screen.mouse_filter = Control.MOUSE_FILTER_STOP
	overlay.add_child(full_screen)
	full_screen.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var black: ColorRect = ColorRect.new()
	black.name = "BlackBackground"
	black.color = Color.BLACK
	black.modulate.a = 0.0
	black.mouse_filter = Control.MOUSE_FILTER_STOP
	full_screen.add_child(black)
	black.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var message: Label = Label.new()
	message.name = "CaughtText"
	message.text = "You Caught!"
	message.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	message.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	message.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	message.add_theme_font_size_override("font_size", 52)
	message.add_theme_color_override("font_color", Color.WHITE)
	message.add_theme_color_override("font_shadow_color", Color(0.0, 0.0, 0.0, 0.7))
	message.add_theme_constant_override("shadow_offset_x", 2)
	message.add_theme_constant_override("shadow_offset_y", 3)
	message.modulate.a = 0.0
	message.mouse_filter = Control.MOUSE_FILTER_IGNORE
	full_screen.add_child(message)
	message.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var animation: Tween = overlay.create_tween()
	animation.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
	animation.set_ignore_time_scale(true)
	animation.tween_property(black, "modulate:a", 1.0, 0.65)
	animation.tween_property(message, "modulate:a", 1.0, 0.25)
	animation.tween_interval(2.5)
	animation.tween_property(message, "modulate:a", 0.0, 0.2)
	animation.tween_callback(Callable(get_tree(), "change_scene_to_file").bind(guidance_office_scene_path))
	animation.tween_interval(0.25)
	animation.tween_property(black, "modulate:a", 0.0, 0.8)
	animation.tween_callback(Callable(overlay, "queue_free"))


func _stop_teacher() -> void:
	if teacher_timer != null:
		teacher_timer.stop()
	if warning_timer != null:
		warning_timer.stop()
	warning_label = null


func _complete_activity(app_id: String) -> void:
	completed_apps[app_id] = true
	_refresh_progress()
	_show_window("Activity Complete")
	_add_text("ACTIVITY COMPLETE!", 27, Color("#20825c"))
	_add_text("You completed %s." % app_id.capitalize(), 19, Color("#344e69"))
	if _completed_count() >= 4:
		finished = true
		var recorded: bool = _record_task()
		_add_text("All four activities are done!", 20, Color("#20825c"))
		if recorded:
			_add_text("Computer Activity is now checked. Returning to the computer room...", 17, Color("#344e69"))
			transitioning = true
			_return_after_success()
		else:
			_add_text("Task not recorded. Check the school timer.", 17, Color("#9b4b44"))
			var exit_button: Button = _button("Return to Computer Room", Color("#346b94"), 44)
			exit_button.pressed.connect(_return_to_room)
			window_content.add_child(exit_button)
	else:
		var back: Button = _button("Back to Desktop", Color("#346b94"), 45)
		back.pressed.connect(_close_app)
		window_content.add_child(back)


func _completed_count() -> int:
	var count: int = 0
	for app_id: String in REQUIRED_APPS:
		if bool(completed_apps.get(app_id, false)):
			count += 1
	return count


func _refresh_progress() -> void:
	if progress_label != null:
		progress_label.text = "Activities completed: %d / 4" % _completed_count()
		progress_label.autowrap_mode = TextServer.AUTOWRAP_OFF


func _record_task() -> bool:
	if game_state == null:
		return false
	if game_state.has_method("is_school_task_complete") and bool(game_state.call("is_school_task_complete", TASK_ID)):
		return true
	var hud: Node = get_node_or_null("/root/SchoolHUD")
	if hud != null and hud.has_method("can_accept_task_completion") and not bool(hud.call("can_accept_task_completion")):
		return false
	if not game_state.has_method("complete_school_task"):
		return false
	return bool(game_state.call("complete_school_task", TASK_ID))


func _return_after_success() -> void:
	await get_tree().create_timer(2.0).timeout
	if is_inside_tree():
		_return_to_room()


func _return_to_room() -> void:
	if caught:
		return
	if not ResourceLoader.exists(return_scene_path, "PackedScene"):
		push_error("Computer room not found: " + return_scene_path)
		return
	if not transitioning:
		transitioning = true
	var transition: Node = get_node_or_null("/root/SceneTransition")
	if transition != null and transition.has_method("transition_to"):
		if bool(transition.call("transition_to", return_scene_path)):
			return
	get_tree().change_scene_to_file.call_deferred(return_scene_path)


func _add_text(value: String, font_size: int, color: Color) -> void:
	window_content.add_child(_label(value, font_size, color))


func _label(value: String, font_size: int, color: Color) -> Label:
	var label: Label = Label.new()
	label.text = value
	label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	label.add_theme_font_size_override("font_size", font_size)
	label.add_theme_color_override("font_color", color)
	return label


func _button(value: String, color: Color, height: float) -> Button:
	var button: Button = Button.new()
	button.text = value
	button.custom_minimum_size.y = height
	button.add_theme_font_size_override("font_size", 16)
	button.add_theme_color_override("font_color", Color.WHITE)
	button.add_theme_color_override("font_hover_color", Color.WHITE)
	button.add_theme_color_override("font_pressed_color", Color.WHITE)
	button.add_theme_stylebox_override("normal", _style(color, color.lightened(0.18), 8, 1))
	button.add_theme_stylebox_override("hover", _style(color.lightened(0.12), Color("#aecbe0"), 8, 1))
	button.add_theme_stylebox_override("pressed", _style(color.darkened(0.10), Color("#aecbe0"), 8, 1))
	return button


func _style(background: Color, border: Color, radius: int, border_width: int) -> StyleBoxFlat:
	var style: StyleBoxFlat = StyleBoxFlat.new()
	style.bg_color = background
	style.border_color = border
	style.set_border_width_all(border_width)
	style.set_corner_radius_all(radius)
	style.content_margin_left = 9
	style.content_margin_right = 9
	style.content_margin_top = 7
	style.content_margin_bottom = 7
	return style
