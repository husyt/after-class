extends Control

@export_file("*.tscn") var return_scene_path: String = "res://Scene/computer_room.tscn"

const TASK_ID: String = "computer_activity"
const DESKTOP_SIZE: Vector2 = Vector2(800.0, 600.0)

const QUIZ_QUESTIONS = [
	{"q": "Which part is known as the brain of the computer?", "options": ["Monitor", "CPU", "Keyboard", "Mouse"], "answer": 1},
	{"q": "Which device displays pictures and text?", "options": ["Speaker", "Monitor", "Printer", "Microphone"], "answer": 1},
	{"q": "Which shortcut copies selected text?", "options": ["Ctrl + V", "Ctrl + Z", "Ctrl + C", "Ctrl + X"], "answer": 2},
	{"q": "What should you do with a suspicious online link?", "options": ["Open it", "Share it", "Ignore warnings", "Avoid it and report it"], "answer": 3},
	{"q": "Which password is the safest choice?", "options": ["123456", "Your name", "A long unique password", "password"], "answer": 2}
]

const TYPING_WORDS: Array[String] = [
	"computer", "keyboard", "internet", "digital citizenship", "online safety"
]

var completed_apps: Dictionary = {"quiz": false, "typing": false, "matching": false, "game": false}
var game_state: Node = null
var hud: CanvasLayer = null
var hud_was_visible: bool = false
var desktop: Control
var desktop_progress: Label
var taskbar_progress: Label
var clock_label: Label
var start_menu: Panel
var window_shade: ColorRect
var app_window: Panel
var app_title: Label
var app_content: VBoxContainer
var icon_captions: Dictionary = {}
var active_app: String = ""
var app_generation: int = 0
var returning: bool = false
var quiz_index: int = 0
var quiz_answered: bool = false
var quiz_feedback: Label
var quiz_next: Button
var quiz_buttons: Array[Button] = []
var typing_index: int = 0
var matching_symbols: Array[String] = []
var matching_buttons: Array[Button] = []
var matching_matched: Dictionary = {}
var matching_first: int = -1
var matching_locked: bool = false
var matching_pairs: int = 0
var matching_progress: Label
var guess_number: int = 0
var guess_attempts: int = 0


func _ready() -> void:
	game_state = get_node_or_null("/root/GameState")
	hud = get_node_or_null("/root/SchoolHUD") as CanvasLayer
	if hud != null:
		hud_was_visible = hud.visible
		hud.hide()
	set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	mouse_filter = Control.MOUSE_FILTER_STOP
	_build_desktop()
	resized.connect(_resize_desktop)
	_resize_desktop()
	_update_status()


func _exit_tree() -> void:
	if is_instance_valid(hud):
		hud.visible = hud_was_visible


func _process(_delta: float) -> void:
	if clock_label != null:
		clock_label.text = Time.get_time_string_from_system().substr(0, 5)


func _resize_desktop() -> void:
	if desktop == null:
		return
	var viewport_size: Vector2 = get_viewport_rect().size
	var factor: float = minf(viewport_size.x / DESKTOP_SIZE.x, viewport_size.y / DESKTOP_SIZE.y)
	desktop.scale = Vector2(factor, factor)
	desktop.position = (viewport_size - DESKTOP_SIZE * factor) * 0.5


func _build_desktop() -> void:
	var outer_bg: ColorRect = _rect(Color("#090e18"), Vector2.ZERO, DESKTOP_SIZE)
	outer_bg.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	add_child(outer_bg)

	desktop = Control.new()
	desktop.name = "ComputerDesktop"
	desktop.position = Vector2.ZERO
	desktop.size = DESKTOP_SIZE
	desktop.mouse_filter = Control.MOUSE_FILTER_STOP
	add_child(desktop)

	var gradient: Gradient = Gradient.new()
	gradient.set_color(0, Color("#091834"))
	gradient.set_color(1, Color("#126da7"))
	var gradient_texture: GradientTexture2D = GradientTexture2D.new()
	gradient_texture.gradient = gradient
	gradient_texture.fill_from = Vector2(0.02, 0.35)
	gradient_texture.fill_to = Vector2(0.94, 0.52)
	var wallpaper: TextureRect = TextureRect.new()
	wallpaper.texture = gradient_texture
	wallpaper.expand_mode = TextureRect.EXPAND_IGNORE_SIZE
	wallpaper.stretch_mode = TextureRect.STRETCH_SCALE
	wallpaper.position = Vector2.ZERO
	wallpaper.size = DESKTOP_SIZE
	wallpaper.mouse_filter = Control.MOUSE_FILTER_IGNORE
	desktop.add_child(wallpaper)

	for poly_points: PackedVector2Array in [
		PackedVector2Array([Vector2(505, 278), Vector2(0, 60), Vector2(0, 370)]),
		PackedVector2Array([Vector2(505, 278), Vector2(0, 570), Vector2(0, 430)]),
		PackedVector2Array([Vector2(505, 278), Vector2(795, 62), Vector2(795, 190)])
	]:
		var ray: Polygon2D = Polygon2D.new()
		ray.polygon = poly_points
		ray.color = Color(0.40, 0.73, 1.0, 0.09)
		desktop.add_child(ray)

	for ix: int in range(2):
		for iy: int in range(2):
			var glass: ColorRect = _rect(
				Color(0.42, 0.82, 1.0, 0.37),
				Vector2(505.0 + ix * 72.0, 181.0 + iy * 85.0),
				Vector2(67.0, 78.0)
			)
			glass.mouse_filter = Control.MOUSE_FILTER_IGNORE
			desktop.add_child(glass)

	var system_name: Label = _label("EQUAL OS", 18, Color("#d7f0ff"))
	system_name.position = Vector2(22, 10)
	desktop.add_child(system_name)

	_make_desktop_icon("quiz", "Quiz Activity", "Q", Vector2(20, 73))
	_make_desktop_icon("typing", "Typing Activity", "T", Vector2(143, 73))
	_make_desktop_icon("matching", "Matching Activity", "M", Vector2(20, 218))
	_make_desktop_icon("game", "Number Guess", "G", Vector2(143, 218))

	desktop_progress = _label("", 17, Color("#e5f5ff"))
	desktop_progress.position = Vector2(21, 405)
	desktop_progress.size = Vector2(420, 32)
	desktop.add_child(desktop_progress)

	_build_taskbar()
	_build_start_menu()
	_build_application_window()


func _make_desktop_icon(app_key: String, caption: String, letter: String, pos: Vector2) -> void:
	var icon_column: VBoxContainer = VBoxContainer.new()
	icon_column.position = pos
	icon_column.size = Vector2(116, 125)
	icon_column.add_theme_constant_override("separation", 7)
	desktop.add_child(icon_column)

	var icon_button: Button = _button(letter, Color("#2678c7"), 68)
	icon_button.add_theme_font_size_override("font_size", 32)
	icon_button.pressed.connect(_open_app.bind(app_key))
	icon_column.add_child(icon_button)

	var caption_label: Label = _label(caption, 15, Color.WHITE)
	caption_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	caption_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	icon_column.add_child(caption_label)
	icon_captions[app_key] = caption_label


func _build_taskbar() -> void:
	var bar: Panel = _panel(Color(0.035, 0.055, 0.095, 0.96), Color("#37506c"))
	bar.position = Vector2(0, 554)
	bar.size = Vector2(800, 46)
	desktop.add_child(bar)

	var start: Button = _button("Start", Color("#174c80"), 35)
	start.position = Vector2(8, 5)
	start.size = Vector2(82, 35)
	start.pressed.connect(_toggle_start_menu)
	bar.add_child(start)

	var exit_button: Button = _button("Exit", Color("#8b354d"), 35)
	exit_button.position = Vector2(97, 5)
	exit_button.size = Vector2(73, 35)
	exit_button.pressed.connect(_return_to_room)
	bar.add_child(exit_button)

	taskbar_progress = _label("", 15, Color("#e0eeff"))
	taskbar_progress.position = Vector2(195, 11)
	taskbar_progress.size = Vector2(435, 24)
	bar.add_child(taskbar_progress)

	clock_label = _label("00:00", 16, Color.WHITE)
	clock_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_RIGHT
	clock_label.position = Vector2(701, 11)
	clock_label.size = Vector2(82, 24)
	bar.add_child(clock_label)


func _build_start_menu() -> void:
	start_menu = _panel(Color("#142a4c"), Color("#67a1d9"))
	start_menu.position = Vector2(8, 308)
	start_menu.size = Vector2(244, 245)
	desktop.add_child(start_menu)

	var heading: Label = _label("EqualOS - Applications", 18, Color("#eaf7ff"))
	heading.position = Vector2(12, 9)
	start_menu.add_child(heading)

	var app_ids: Array[String] = ["quiz", "typing", "matching", "game"]
	var names: Array[String] = ["Quiz Activity", "Typing Activity", "Matching Activity", "Number Guess"]
	for i: int in range(app_ids.size()):
		var app_button: Button = _button(names[i], Color("#275888"), 39)
		app_button.position = Vector2(12, 43 + i * 47)
		app_button.size = Vector2(220, 39)
		app_button.pressed.connect(_open_app.bind(app_ids[i]))
		start_menu.add_child(app_button)
	start_menu.hide()


func _toggle_start_menu() -> void:
	if returning:
		return
	start_menu.visible = not start_menu.visible


func _build_application_window() -> void:
	window_shade = _rect(Color(0, 0, 0, 0.30), Vector2.ZERO, Vector2(800, 554))
	window_shade.mouse_filter = Control.MOUSE_FILTER_STOP
	desktop.add_child(window_shade)

	app_window = _panel(Color("#f8f9fc"), Color("#9db0c9"))
	app_window.position = Vector2(115, 62)
	app_window.size = Vector2(570, 455)
	window_shade.add_child(app_window)

	var title_bar: Panel = _panel(Color("#1864a9"), Color("#1864a9"))
	title_bar.position = Vector2(2, 2)
	title_bar.size = Vector2(566, 43)
	app_window.add_child(title_bar)

	app_title = _label("Application", 19, Color.WHITE)
	app_title.position = Vector2(16, 9)
	app_title.size = Vector2(443, 30)
	title_bar.add_child(app_title)

	var minimize: Button = _button("-", Color("#315d88"), 32)
	minimize.position = Vector2(473, 6)
	minimize.size = Vector2(40, 31)
	minimize.pressed.connect(_close_app)
	title_bar.add_child(minimize)

	var close_button: Button = _button("X", Color("#a54153"), 32)
	close_button.position = Vector2(518, 6)
	close_button.size = Vector2(40, 31)
	close_button.pressed.connect(_close_app)
	title_bar.add_child(close_button)

	var scroll: ScrollContainer = ScrollContainer.new()
	scroll.position = Vector2(16, 57)
	scroll.size = Vector2(538, 381)
	scroll.horizontal_scroll_mode = ScrollContainer.SCROLL_MODE_DISABLED
	app_window.add_child(scroll)

	app_content = VBoxContainer.new()
	app_content.custom_minimum_size.x = 513
	app_content.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	app_content.add_theme_constant_override("separation", 12)
	scroll.add_child(app_content)
	window_shade.hide()


func _open_app(key: String) -> void:
	if returning:
		return
	active_app = key
	app_generation += 1
	start_menu.hide()
	window_shade.show()
	_clear_app()

	match key:
		"quiz":
			app_title.text = "Quiz Activity"
			quiz_index = 0
			_show_quiz()
		"typing":
			app_title.text = "Typing Activity"
			typing_index = 0
			_show_typing()
		"matching":
			app_title.text = "Matching Activity"
			_start_matching()
		"game":
			app_title.text = "Number Guess Game"
			_show_guess()


func _close_app() -> void:
	app_generation += 1
	active_app = ""
	window_shade.hide()


func _clear_app() -> void:
	for child: Node in app_content.get_children():
		app_content.remove_child(child)
		child.queue_free()
	quiz_buttons.clear()


func _add_text(content: String, font_size: int, color: Color) -> Label:
	var label: Label = _label(content, font_size, color)
	app_content.add_child(label)
	return label


func _show_quiz() -> void:
	_clear_app()
	quiz_answered = false
	var item: Dictionary = QUIZ_QUESTIONS[quiz_index]
	_add_text("QUESTION %d / %d" % [quiz_index + 1, QUIZ_QUESTIONS.size()], 15, Color("#63748a"))
	_add_text(str(item["q"]), 22, Color("#1d3652"))

	var choices: Array = item["options"]
	for i: int in range(choices.size()):
		var option: Button = _button("%s. %s" % [["A", "B", "C", "D"][i], str(choices[i])], Color("#346b9e"), 43)
		option.pressed.connect(_answer_quiz.bind(i))
		app_content.add_child(option)
		quiz_buttons.append(option)

	quiz_feedback = _add_text("Choose the correct answer.", 16, Color("#536b84"))
	quiz_next = _button("Next Question", Color("#258365"), 43)
	quiz_next.pressed.connect(_next_quiz)
	app_content.add_child(quiz_next)
	quiz_next.hide()


func _answer_quiz(index: int) -> void:
	if active_app != "quiz" or quiz_answered:
		return
	var question: Dictionary = QUIZ_QUESTIONS[quiz_index]
	if index != int(question["answer"]):
		quiz_feedback.text = "Try again."
		quiz_feedback.add_theme_color_override("font_color", Color("#af4b34"))
		quiz_buttons[index].disabled = true
		return
	quiz_answered = true
	quiz_feedback.text = "Correct!"
	quiz_feedback.add_theme_color_override("font_color", Color("#16724e"))
	for button: Button in quiz_buttons:
		button.disabled = true
	quiz_next.text = "Finish Quiz" if quiz_index == QUIZ_QUESTIONS.size() - 1 else "Next Question"
	quiz_next.show()


func _next_quiz() -> void:
	if not quiz_answered:
		return
	quiz_index += 1
	if quiz_index >= QUIZ_QUESTIONS.size():
		_finish_app("quiz")
	else:
		_show_quiz()


func _show_typing() -> void:
	_clear_app()
	if typing_index >= TYPING_WORDS.size():
		_finish_app("typing")
		return
	_add_text("TYPING CHALLENGE   %d / %d" % [typing_index + 1, TYPING_WORDS.size()], 16, Color("#62748a"))
	_add_text("Type the phrase exactly:", 19, Color("#243e5c"))
	_add_text(TYPING_WORDS[typing_index], 28, Color("#1760a0"))

	var entry: LineEdit = LineEdit.new()
	entry.placeholder_text = "Type here"
	entry.custom_minimum_size.y = 47
	entry.add_theme_font_size_override("font_size", 20)
	app_content.add_child(entry)
	var feedback: Label = _add_text("", 17, Color("#b54c34"))
	var submit: Button = _button("Submit", Color("#258365"), 44)
	submit.pressed.connect(_check_typing.bind(entry, feedback))
	entry.text_submitted.connect(_submit_typing_enter.bind(entry, feedback))
	app_content.add_child(submit)
	entry.grab_focus.call_deferred()


func _submit_typing_enter(_value: String, entry: LineEdit, feedback: Label) -> void:
	_check_typing(entry, feedback)


func _check_typing(entry: LineEdit, feedback: Label) -> void:
	if active_app != "typing" or not entry.editable:
		return
	if entry.text.strip_edges().to_lower() != TYPING_WORDS[typing_index].to_lower():
		feedback.text = "Try again. Check your spelling."
		return
	entry.editable = false
	feedback.text = "Correct!"
	feedback.add_theme_color_override("font_color", Color("#16724e"))
	typing_index += 1
	var generation: int = app_generation
	await get_tree().create_timer(0.45).timeout
	if not is_inside_tree() or active_app != "typing" or generation != app_generation:
		return
	_show_typing()


func _start_matching() -> void:
	matching_symbols = ["CPU", "CPU", "MOUSE", "MOUSE", "SCREEN", "SCREEN", "KEYS", "KEYS"]
	matching_symbols.shuffle()
	matching_matched.clear()
	matching_first = -1
	matching_locked = false
	matching_pairs = 0
	matching_buttons.clear()
	_clear_app()
	_add_text("MATCHING ACTIVITY", 23, Color("#1d3652"))
	_add_text("Find the four matching pairs.", 17, Color("#526880"))
	matching_progress = _add_text("Pairs: 0 / 4", 17, Color("#197353"))
	var grid: GridContainer = GridContainer.new()
	grid.columns = 4
	grid.add_theme_constant_override("h_separation", 9)
	grid.add_theme_constant_override("v_separation", 9)
	app_content.add_child(grid)
	for i: int in range(matching_symbols.size()):
		var card: Button = _button("?", Color("#416eaa"), 72)
		card.custom_minimum_size.x = 110
		card.add_theme_font_size_override("font_size", 18)
		card.pressed.connect(_flip_matching.bind(i))
		grid.add_child(card)
		matching_buttons.append(card)


func _flip_matching(index: int) -> void:
	if active_app != "matching" or matching_locked or matching_matched.has(index) or matching_first == index:
		return
	matching_buttons[index].text = matching_symbols[index]
	matching_buttons[index].disabled = true
	if matching_first == -1:
		matching_first = index
		return
	var first: int = matching_first
	matching_first = -1
	matching_locked = true
	var generation: int = app_generation
	await get_tree().create_timer(0.65).timeout
	if not is_inside_tree() or active_app != "matching" or generation != app_generation:
		return
	if matching_symbols[first] == matching_symbols[index]:
		matching_matched[first] = true
		matching_matched[index] = true
		matching_pairs += 1
		matching_progress.text = "Pairs: %d / 4" % matching_pairs
		matching_buttons[first].modulate = Color("#95ddb6")
		matching_buttons[index].modulate = Color("#95ddb6")
	else:
		matching_buttons[first].text = "?"
		matching_buttons[index].text = "?"
		matching_buttons[first].disabled = false
		matching_buttons[index].disabled = false
	matching_locked = false
	if matching_pairs == 4:
		_finish_app("matching")


func _show_guess() -> void:
	_clear_app()
	guess_number = randi_range(1, 10)
	guess_attempts = 0
	_add_text("NUMBER GUESS GAME", 24, Color("#1d3652"))
	_add_text("I'm thinking of a number from 1 to 10.", 18, Color("#526880"))
	_add_text("Try to guess it!", 19, Color("#1d3652"))
	var spin: SpinBox = SpinBox.new()
	spin.min_value = 1
	spin.max_value = 10
	spin.step = 1
	spin.value = 1
	spin.custom_minimum_size.y = 45
	app_content.add_child(spin)
	var feedback: Label = _add_text("", 17, Color("#a84b31"))
	var guess_button: Button = _button("Guess", Color("#258365"), 44)
	guess_button.pressed.connect(_check_guess.bind(spin, feedback, guess_button))
	app_content.add_child(guess_button)


func _check_guess(spin: SpinBox, feedback: Label, guess_button: Button) -> void:
	if active_app != "game" or guess_button.disabled:
		return
	guess_attempts += 1
	var attempt: int = int(spin.value)
	if attempt == guess_number:
		guess_button.disabled = true
		feedback.text = "Correct in %d tries!" % guess_attempts
		_finish_app("game")
	elif attempt < guess_number:
		feedback.text = "Too low. Try a larger number."
	else:
		feedback.text = "Too high. Try a smaller number."


func _finish_app(key: String) -> void:
	completed_apps[key] = true
	_update_status()
	_clear_app()
	app_title.text = "Activity Completed"
	_add_text("COMPLETED!", 29, Color("#16724e"))
	_add_text("You finished %s." % _app_name(key), 19, Color("#223c59"))
	if _all_completed():
		var recorded: bool = _award_task()
		_add_text("All three activities and the game are complete.", 18, Color("#16724e"))
		_add_text("Returning to the computer room..." if recorded else "The school task could not be recorded. Check the timer.", 17, Color("#405b72"))
		returning = true
		_return_after_delay()
	else:
		_add_text("Close this window to start another application.", 17, Color("#405b72"))
		var back: Button = _button("Back to Desktop", Color("#246a9e"), 45)
		back.pressed.connect(_close_app)
		app_content.add_child(back)


func _app_name(key: String) -> String:
	match key:
		"quiz": return "Quiz Activity"
		"typing": return "Typing Activity"
		"matching": return "Matching Activity"
		"game": return "Number Guess"
	return "Application"


func _all_completed() -> bool:
	return bool(completed_apps["quiz"]) and bool(completed_apps["typing"]) and bool(completed_apps["matching"]) and bool(completed_apps["game"])


func _award_task() -> bool:
	if game_state == null:
		return false
	if bool(game_state.call("is_school_task_complete", TASK_ID)):
		return true
	var school_hud: Node = get_node_or_null("/root/SchoolHUD")
	if school_hud != null and school_hud.has_method("can_accept_task_completion"):
		if not bool(school_hud.call("can_accept_task_completion")):
			return false
	return bool(game_state.call("complete_school_task", TASK_ID))


func _update_status() -> void:
	var count: int = 0
	for key: String in ["quiz", "typing", "matching", "game"]:
		if bool(completed_apps[key]):
			count += 1
			var lbl: Label = icon_captions.get(key) as Label
			if lbl != null:
				lbl.text = _app_name(key) + "  DONE"
	if desktop_progress != null:
		desktop_progress.text = "Completed: %d / 4" % count
	if taskbar_progress != null:
		taskbar_progress.text = "Activities %d/4   |   Computer Task: %s" % [count, "DONE" if _all_completed() else "IN PROGRESS"]


func _return_after_delay() -> void:
	await get_tree().create_timer(2.0).timeout
	if is_inside_tree() and returning:
		_return_to_room()


func _return_to_room() -> void:
	if returning and active_app == "returning":
		return
	returning = true
	active_app = "returning"
	if not ResourceLoader.exists(return_scene_path, "PackedScene"):
		push_error("Computer room scene not found: " + return_scene_path)
		returning = false
		active_app = ""
		return
	if is_instance_valid(hud):
		hud.visible = hud_was_visible
	var transition: Node = get_node_or_null("/root/SceneTransition")
	if transition != null and transition.has_method("transition_to"):
		if bool(transition.call("transition_to", return_scene_path)):
			return
	get_tree().change_scene_to_file.call_deferred(return_scene_path)


func _rect(color: Color, pos: Vector2, rect_size: Vector2) -> ColorRect:
	var result: ColorRect = ColorRect.new()
	result.color = color
	result.position = pos
	result.size = rect_size
	return result


func _label(content: String, font_size: int, color: Color) -> Label:
	var result: Label = Label.new()
	result.text = content
	result.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	result.add_theme_font_size_override("font_size", font_size)
	result.add_theme_color_override("font_color", color)
	return result


func _button(content: String, color: Color, height: float) -> Button:
	var result: Button = Button.new()
	result.text = content
	result.custom_minimum_size.y = height
	result.add_theme_font_size_override("font_size", 16)
	for property_name: String in ["font_color", "font_hover_color", "font_pressed_color", "font_disabled_color"]:
		result.add_theme_color_override(property_name, Color.WHITE)
	result.add_theme_stylebox_override("normal", _style(color, color.lightened(0.25)))
	result.add_theme_stylebox_override("hover", _style(color.lightened(0.14), Color("#b8ddff")))
	result.add_theme_stylebox_override("pressed", _style(color.darkened(0.13), Color("#b8ddff")))
	result.add_theme_stylebox_override("disabled", _style(color.darkened(0.20), Color("#6786a8")))
	return result


func _panel(color: Color, border: Color) -> Panel:
	var result: Panel = Panel.new()
	result.add_theme_stylebox_override("panel", _style(color, border))
	return result


func _style(color: Color, border: Color) -> StyleBoxFlat:
	var result: StyleBoxFlat = StyleBoxFlat.new()
	result.bg_color = color
	result.border_color = border
	result.set_border_width_all(1)
	result.set_corner_radius_all(5)
	result.content_margin_left = 7
	result.content_margin_right = 7
	result.content_margin_top = 5
	result.content_margin_bottom = 5
	return result
