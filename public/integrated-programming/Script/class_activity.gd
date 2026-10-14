
extends Control

# ============================================
# EQUALPATH - CLASSROOM NOTEBOOK
# 5 LESSONS | 15 QUESTIONS
# AUTOMATIC RETURN AFTER COMPLETION
# ============================================

const CLASSROOM_SCENE: String = "res://Scene/classroom.tscn"
const TASK_ID: String = "attend_class"

const LESSONS = [
	{
		"name": "Kindness and Respect",
		"notes": "Kindness means treating classmates with care. Respect means listening politely and valuing other people.",
		"tip": "Small acts of kindness make school a better place.",
		"questions": [
			{
				"q": "A new student sits alone. What should you do?",
				"a": [
					"Ignore them.",
					"Invite them to join your group.",
                    "Laugh at them."
				],
				"correct": 1,
				"why": "An invitation helps classmates feel welcome."
			},
			{
				"q": "A classmate drops their books. What should you do?",
				"a": [
					"Help pick up the books.",
					"Walk away.",
                    "Make fun of them."
				],
				"correct": 0,
				"why": "Helping others is an act of kindness."
			},
			{
				"q": "Someone gives a wrong answer. What should you do?",
				"a": [
					"Laugh at the mistake.",
					"Tell them to stop.",
                    "Encourage them to try again."
				],
				"correct": 2,
				"why": "Encouragement helps build confidence."
			}
		]
	},
	{
		"name": "Teamwork",
		"notes": "Teamwork means sharing ideas, listening to others, and working toward a common goal.",
		"tip": "A good team includes everyone's contribution.",
		"questions": [
			{
				"q": "Your group has different ideas. What is best?",
				"a": [
					"Discuss everyone's ideas.",
					"Only accept your own idea.",
                    "Stop participating."
				],
				"correct": 0,
				"why": "Listening makes teamwork stronger."
			},
			{
				"q": "One group member needs help. What do you do?",
				"a": [
					"Ignore them.",
					"Offer help.",
                    "Remove them from the group."
				],
				"correct": 1,
				"why": "A strong team supports its members."
			},
			{
				"q": "Your group finishes a project. What do you say?",
				"a": [
					"I did everything.",
					"Nobody helped me.",
                    "Great work, everyone!"
				],
				"correct": 2,
				"why": "Appreciating everyone's work builds trust."
			}
		]
	},
	{
		"name": "Inclusion and Empathy",
		"notes": "Inclusion means giving everyone a chance to participate. Empathy means understanding how others feel.",
		"tip": "Everyone deserves to feel included.",
		"questions": [
			{
				"q": "A classmate has difficulty joining an activity. What should you do?",
				"a": [
					"Ask how you can help.",
					"Leave them out.",
                    "Assume they cannot join."
				],
				"correct": 0,
				"why": "Offering respectful help creates inclusion."
			},
			{
				"q": "A classmate speaks differently. What should you do?",
				"a": [
					"Make fun of them.",
					"Communicate patiently.",
                    "Avoid them."
				],
				"correct": 1,
				"why": "Patience and respect help everyone belong."
			},
			{
				"q": "Someone is excluded from a game. What should you do?",
				"a": [
					"Ignore them.",
					"Join in excluding them.",
                    "Invite them to participate."
				],
				"correct": 2,
				"why": "Inviting others encourages friendship."
			}
		]
	},
	{
		"name": "Classroom Safety",
		"notes": "A safe classroom follows rules, respects personal space, and asks trusted adults for help when necessary.",
		"tip": "Safety is everyone's responsibility.",
		"questions": [
			{
				"q": "True or False: Tell a trusted adult about bullying.",
				"a": [
					"True",
                    "False"
				],
				"correct": 0,
				"why": "Reporting bullying helps protect students."
			},
			{
				"q": "True or False: Pushing classmates is okay when late.",
				"a": [
					"True",
                    "False"
				],
				"correct": 1,
				"why": "Respect everyone's personal space."
			},
			{
				"q": "True or False: Classroom rules protect everyone.",
				"a": [
					"True",
                    "False"
				],
				"correct": 0,
				"why": "Rules help keep the classroom safe."
			}
		]
	},
	{
		"name": "Digital Responsibility",
		"notes": "Responsible online behavior means protecting privacy, checking facts, and treating others respectfully.",
		"tip": "Think before posting or sharing online.",
		"questions": [
			{
				"q": "Before posting a classmate's photo, what should you do?",
				"a": [
					"Post immediately.",
					"Ask for permission.",
                    "Send it to strangers."
				],
				"correct": 1,
				"why": "Permission helps protect privacy."
			},
			{
				"q": "Someone sends a hurtful group-chat message. What is best?",
				"a": [
					"Forward it.",
					"Add another insult.",
                    "Avoid spreading it and report it."
				],
				"correct": 2,
				"why": "Do not participate in cyberbullying."
			},
			{
				"q": "You find suspicious information online. What do you do?",
				"a": [
					"Check reliable sources.",
					"Share it immediately.",
                    "Believe everything."
				],
				"correct": 0,
				"why": "Checking facts helps prevent misinformation."
			}
		]
	}
]


# ============================================
# VARIABLES
# ============================================

var game_state: Node = null

var lesson_index: int = 0
var question_index: int = 0

var correct_count: int = 0
var total_count: int = 0

var answered: bool = false
var finished: bool = false
var turning_page: bool = false

var left_scroll: ScrollContainer
var right_scroll: ScrollContainer

var left_content: VBoxContainer
var right_content: VBoxContainer

var lesson_number: Label
var lesson_title: Label
var lesson_notes: Label
var lesson_tip: Label

var progress_text: Label
var progress_bar: ProgressBar

var question_number: Label
var question_text: Label
var feedback_text: Label

var options: Array[Button] = []

var next_button: Button
var return_button: Button


# ============================================
# READY
# ============================================

func _ready() -> void:

	game_state = get_node_or_null("/root/GameState")

	if game_state == null:
		push_error("GameState Autoload is missing.")

	for lesson: Dictionary in LESSONS:

		var questions: Array = lesson["questions"]
		total_count += questions.size()

	set_anchors_and_offsets_preset(
		Control.PRESET_FULL_RECT
	)

	_build_notebook()

	resized.connect(_layout_notebook)

	_layout_notebook()
	_restore_progress()

	if finished:
		_show_finished_page()
	else:
		_show_question()


# ============================================
# DRAW NOTEBOOK
# ============================================

func _draw() -> void:

	draw_rect(
		Rect2(Vector2.ZERO, size),
		Color("#263748")
	)

	var book: Rect2 = _book_rect()
	var half: float = book.size.x * 0.5

	var left: Rect2 = Rect2(
		book.position,
		Vector2(half - 10.0, book.size.y)
	)

	var right: Rect2 = Rect2(
		book.position + Vector2(half + 10.0, 0.0),
		Vector2(half - 10.0, book.size.y)
	)

	# Book shadow

	draw_rect(
		Rect2(
			book.position + Vector2(8, 10),
			book.size
		),
		Color(0, 0, 0, 0.30)
	)

	for page: Rect2 in [left, right]:

		# Cream paper

		draw_rect(
			page,
			Color("#fff9eb")
		)

		# Paper border

		draw_rect(
			page,
			Color("#d7c6ac"),
			false,
			2.0
		)

		# Blue notebook lines

		for i: int in range(22):

			var line_y: float = (
				page.position.y
				+ 96.0
				+ float(i) * 26.0
			)

			if line_y > page.end.y - 15.0:
				break

			draw_line(
				Vector2(page.position.x + 15, line_y),
				Vector2(page.end.x - 15, line_y),
				Color("#c5deec"),
				1.0
			)

		# Red margin

		draw_line(
			Vector2(
				page.position.x + 39,
				page.position.y + 12
			),
			Vector2(
				page.position.x + 39,
				page.end.y - 12
			),
			Color("#e8aaa1"),
			1.5
		)

	# Notebook center binding

	var center_x: float = (
		book.position.x + half
	)

	draw_rect(
		Rect2(
			Vector2(center_x - 9, book.position.y),
			Vector2(18, book.size.y)
		),
		Color("#756e6a")
	)

	for i: int in range(20):

		var ring_y: float = (
			book.position.y
			+ 25.0
			+ float(i) * 32.0
		)

		if ring_y >= book.end.y - 13:
			break

		draw_arc(
			Vector2(center_x, ring_y),
			10.0,
			0.0,
			TAU,
			24,
			Color("#d8e0e4"),
			3.0,
			true
		)


# ============================================
# NOTEBOOK LAYOUT
# ============================================

func _book_rect() -> Rect2:

	var width: float = minf(
		1160.0,
		maxf(320.0, size.x - 32.0)
	)

	var height: float = minf(
		680.0,
		maxf(300.0, size.y - 32.0)
	)

	var book_size: Vector2 = Vector2(
		width,
		height
	)

	return Rect2(
		(size - book_size) * 0.5,
		book_size
	)


func _layout_notebook() -> void:

	if left_scroll == null or right_scroll == null:
		return

	var book: Rect2 = _book_rect()
	var half: float = book.size.x * 0.5

	left_scroll.position = (
		book.position + Vector2(58, 28)
	)

	left_scroll.size = Vector2(
		maxf(120.0, half - 86.0),
		book.size.y - 56.0
	)

	right_scroll.position = (
		book.position + Vector2(half + 50.0, 28.0)
	)

	right_scroll.size = Vector2(
		maxf(120.0, half - 76.0),
		book.size.y - 56.0
	)

	left_content.custom_minimum_size.x = (
		left_scroll.size.x - 12.0
	)

	right_content.custom_minimum_size.x = (
		right_scroll.size.x - 12.0
	)

	queue_redraw()


# ============================================
# CREATE PAGE CONTAINER
# ============================================

func _create_page() -> Dictionary:

	var scroll: ScrollContainer = ScrollContainer.new()

	scroll.horizontal_scroll_mode = (
		ScrollContainer.SCROLL_MODE_DISABLED
	)

	add_child(scroll)

	var content: VBoxContainer = VBoxContainer.new()

	content.add_theme_constant_override(
		"separation",
		13
	)

	scroll.add_child(content)

	return {
		"scroll": scroll,
		"content": content
	}


# ============================================
# BUILD UI
# ============================================

func _build_notebook() -> void:

	var left_page: Dictionary = _create_page()

	left_scroll = (
		left_page["scroll"] as ScrollContainer
	)

	left_content = (
		left_page["content"] as VBoxContainer
	)

	var right_page: Dictionary = _create_page()

	right_scroll = (
		right_page["scroll"] as ScrollContainer
	)

	right_content = (
		right_page["content"] as VBoxContainer
	)

	# LEFT PAGE

	left_content.add_child(
		_make_label(
			"EQUALPATH / MY CLASS NOTEBOOK",
			15,
			Color("#a06054")
		)
	)

	left_content.add_child(
		_make_label(
			"Today's Lesson",
			29,
			Color("#25394f")
		)
	)

	lesson_number = _make_label(
		"",
		15,
		Color("#a06054")
	)
	left_content.add_child(lesson_number)

	lesson_title = _make_label(
		"",
		23,
		Color("#2b4059")
	)
	left_content.add_child(lesson_title)

	left_content.add_child(
		_make_label(
			"LESSON NOTES",
			15,
			Color("#a06054")
		)
	)

	lesson_notes = _make_label(
		"",
		18,
		Color("#34465b")
	)
	left_content.add_child(lesson_notes)

	left_content.add_child(
		_make_label(
			"REMEMBER",
			15,
			Color("#a06054")
		)
	)

	lesson_tip = _make_label(
		"",
		17,
		Color("#41654f")
	)
	left_content.add_child(lesson_tip)

	var left_gap: Control = Control.new()
	left_gap.custom_minimum_size.y = 22
	left_content.add_child(left_gap)

	progress_text = _make_label(
		"",
		15,
		Color("#344b63")
	)
	left_content.add_child(progress_text)

	progress_bar = ProgressBar.new()
	progress_bar.custom_minimum_size.y = 15
	progress_bar.show_percentage = false
	progress_bar.max_value = total_count
	left_content.add_child(progress_bar)

	# RIGHT PAGE

	right_content.add_child(
		_make_label(
			"CHECK YOUR LEARNING",
			17,
			Color("#a06054")
		)
	)

	question_number = _make_label(
		"",
		15,
		Color("#566e85")
	)
	right_content.add_child(question_number)

	question_text = _make_label(
		"",
		22,
		Color("#293d54")
	)
	question_text.custom_minimum_size.y = 84
	right_content.add_child(question_text)

	for i: int in range(3):

		var option: Button = Button.new()

		option.custom_minimum_size.y = 53
		option.alignment = HORIZONTAL_ALIGNMENT_LEFT

		option.add_theme_font_size_override(
			"font_size",
			16
		)

		option.add_theme_color_override(
			"font_color",
			Color("#263a53")
		)

		option.add_theme_color_override(
			"font_hover_color",
			Color("#263a53")
		)

		option.add_theme_color_override(
			"font_disabled_color",
			Color("#263a53")
		)

		option.add_theme_stylebox_override(
			"normal",
			_button_style(Color("#f1e8d8"))
		)

		option.add_theme_stylebox_override(
			"hover",
			_button_style(Color("#e1edd5"))
		)

		option.add_theme_stylebox_override(
			"pressed",
			_button_style(Color("#c9e4c4"))
		)

		option.add_theme_stylebox_override(
			"disabled",
			_button_style(Color("#f1e8d8"))
		)

		option.pressed.connect(
			_choose_answer.bind(i)
		)

		right_content.add_child(option)
		options.append(option)

	feedback_text = _make_label(
		"",
		16,
		Color("#55657b")
	)
	feedback_text.custom_minimum_size.y = 62
	right_content.add_child(feedback_text)

	var right_gap: Control = Control.new()
	right_gap.custom_minimum_size.y = 8
	right_content.add_child(right_gap)

	var navigation: HBoxContainer = HBoxContainer.new()

	navigation.add_theme_constant_override(
		"separation",
		8
	)

	right_content.add_child(navigation)

	return_button = Button.new()
	return_button.text = "Back to Class"
	return_button.custom_minimum_size = Vector2(135, 46)

	return_button.pressed.connect(
		_return_to_classroom
	)

	navigation.add_child(return_button)

	var spacer: Control = Control.new()
	spacer.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	navigation.add_child(spacer)

	next_button = Button.new()
	next_button.text = "Turn Page >"
	next_button.custom_minimum_size = Vector2(150, 46)

	next_button.pressed.connect(
		_turn_page
	)

	navigation.add_child(next_button)
	next_button.hide()


# ============================================
# SHOW QUESTION
# ============================================

func _show_question() -> void:

	var lesson: Dictionary = LESSONS[lesson_index]
	var questions: Array = lesson["questions"]
	var question: Dictionary = questions[question_index]
	var answers: Array = question["a"]

	lesson_number.text = "LESSON %d OF %d" % [
		lesson_index + 1,
		LESSONS.size()
	]

	lesson_title.text = str(lesson["name"])
	lesson_notes.text = str(lesson["notes"])
	lesson_tip.text = str(lesson["tip"])

	progress_text.text = "Notebook Progress: %d / %d" % [
		correct_count,
		total_count
	]

	progress_bar.value = correct_count

	var current_number: int = (
		correct_count if answered
		else correct_count + 1
	)

	question_number.text = "QUESTION %d OF %d" % [
		mini(current_number, total_count),
		total_count
	]

	question_text.text = str(question["q"])

	feedback_text.add_theme_color_override(
		"font_color",
		Color("#55657b")
	)

	feedback_text.text = "Choose the best answer."

	for i: int in range(options.size()):

		var option: Button = options[i]

		option.disabled = answered
		option.modulate = Color.WHITE

		if i < answers.size():

			option.text = "%s. %s" % [
				["A", "B", "C"][i],
				str(answers[i])
			]

			option.show()

		else:
			option.hide()

	next_button.disabled = false
	next_button.hide()

	if answered:

		feedback_text.text = (
			"Correct! " + str(question["why"])
		)

		feedback_text.add_theme_color_override(
			"font_color",
			Color("#28764d")
		)

		var correct_index: int = int(
			question["correct"]
		)

		options[correct_index].modulate = (
			Color("#a2d9aa")
		)

		next_button.show()

	if (
		lesson_index == LESSONS.size() - 1
		and question_index == questions.size() - 1
	):

		next_button.text = "Finish Notebook"

	else:

		next_button.text = "Turn Page >"

	left_scroll.scroll_vertical = 0
	right_scroll.scroll_vertical = 0


# ============================================
# CHECK ANSWERS
# ============================================

func _choose_answer(index: int) -> void:

	if answered or finished or turning_page:
		return

	var lesson: Dictionary = LESSONS[lesson_index]
	var questions: Array = lesson["questions"]
	var question: Dictionary = questions[question_index]

	if index == int(question["correct"]):

		answered = true
		correct_count += 1

		feedback_text.text = (
			"Correct! " + str(question["why"])
		)

		feedback_text.add_theme_color_override(
			"font_color",
			Color("#28764d")
		)

		options[index].modulate = Color("#a2d9aa")

		for option: Button in options:
			option.disabled = true

		progress_text.text = (
			"Notebook Progress: %d / %d" % [
				correct_count,
				total_count
			]
		)

		progress_bar.value = correct_count

		next_button.show()
		next_button.grab_focus()

		_save_progress()

	else:

		feedback_text.text = (
            "Not quite. Read your lesson notes "
			+ "and try again."
		)

		feedback_text.add_theme_color_override(
			"font_color",
			Color("#aa5b2e")
		)

		options[index].disabled = true


# ============================================
# TURN PAGE
# ============================================

func _turn_page() -> void:

	if not answered or finished or turning_page:
		return

	turning_page = true
	next_button.disabled = true

	var tween: Tween = create_tween()

	tween.tween_property(
		right_scroll,
		"modulate:a",
		0.0,
		0.16
	)

	tween.tween_callback(_advance_page)

	tween.tween_property(
		right_scroll,
		"modulate:a",
		1.0,
		0.18
	)

	tween.tween_callback(_end_page_turn)


func _end_page_turn() -> void:

	turning_page = false


func _advance_page() -> void:

	var lesson: Dictionary = LESSONS[lesson_index]
	var questions: Array = lesson["questions"]

	if question_index + 1 < questions.size():

		question_index += 1
		answered = false

		_save_progress()
		_show_question()

		return

	if lesson_index + 1 < LESSONS.size():

		lesson_index += 1
		question_index = 0
		answered = false

		_save_progress()
		_show_question()

		return

	_finish_notebook()


# ============================================
# FINISH NOTEBOOK
# ============================================

func _finish_notebook() -> void:

	if finished:
		return

	finished = true

	# Save completed notebook progress.
	_save_progress()

	# Mark Attend Class complete, if allowed.
	var recorded: bool = _record_attend_class()

	# Display the completion message.
	_show_finished_page(recorded)

	# Wait two seconds so the player can
	# read the result, then return automatically.
	await get_tree().create_timer(
		2.0,
		true,
		false,
		true
	).timeout

	if not is_inside_tree():
		return

	_return_to_classroom()


# ============================================
# COMPLETE ATTEND CLASS TASK
# ============================================

func _record_attend_class() -> bool:

	if game_state == null:
		return false

	if bool(
		game_state.call(
			"is_school_task_complete",
			TASK_ID
		)
	):
		return true

	var hud: Node = get_node_or_null(
        "/root/SchoolHUD"
	)

	if (
		hud != null
		and hud.has_method("can_accept_task_completion")
	):

		if not bool(
			hud.call("can_accept_task_completion")
		):
			return false

	return bool(
		game_state.call(
			"complete_school_task",
			TASK_ID
		)
	)


# ============================================
# NOTEBOOK COMPLETION PAGE
# ============================================

func _show_finished_page(
	recorded: bool = false
) -> void:

	lesson_number.text = "NOTEBOOK COMPLETE"
	lesson_title.text = "Excellent Work!"

	lesson_notes.text = (
        "You finished all five classroom lessons."
	)

	lesson_tip.text = (
        "You learned about kindness, teamwork, "
		+ "inclusion, safety and digital responsibility."
	)

	progress_text.text = "15 / 15 Questions Completed"
	progress_bar.value = total_count

	question_number.text = "CLASSROOM ACTIVITY FINISHED"

	question_text.text = (
        "You have successfully completed "
		+ "your classroom notebook!"
	)

	if not recorded and game_state != null:

		recorded = bool(
			game_state.call(
				"is_school_task_complete",
				TASK_ID
			)
		)

	if recorded:

		feedback_text.text = (
            "Attend Class completed! "
			+ "Returning to classroom..."
		)

		feedback_text.add_theme_color_override(
			"font_color",
			Color("#28764d")
		)

	else:

		feedback_text.text = (
            "Notebook finished, but the task was not "
			+ "recorded. Check whether time expired."
		)

		feedback_text.add_theme_color_override(
			"font_color",
			Color("#aa5b2e")
		)

	for option: Button in options:
		option.hide()

	next_button.hide()
	return_button.text = "Return to Classroom"


# ============================================
# SAVE PROGRESS
# ============================================

func _save_progress() -> void:

	if game_state == null:
		return

	if game_state.has_method(
        "set_notebook_progress"
	):

		game_state.call(
			"set_notebook_progress",
			{
				"lesson_index": lesson_index,
				"question_index": question_index,
				"correct_count": correct_count,
				"answered": answered,
				"finished": finished
			}
		)


# ============================================
# RESTORE PROGRESS
# ============================================

func _restore_progress() -> void:

	if game_state == null:
		return

	if not game_state.has_method(
        "get_notebook_progress"
	):
		return

	var data: Dictionary = game_state.call(
        "get_notebook_progress"
	)

	if data.is_empty():
		return

	lesson_index = clampi(
		int(data.get("lesson_index", 0)),
		0,
		LESSONS.size() - 1
	)

	var questions: Array = (
		LESSONS[lesson_index]["questions"]
	)

	question_index = clampi(
		int(data.get("question_index", 0)),
		0,
		questions.size() - 1
	)

	correct_count = clampi(
		int(data.get("correct_count", 0)),
		0,
		total_count
	)

	answered = bool(
		data.get("answered", false)
	)

	finished = bool(
		data.get("finished", false)
	)


# ============================================
# RETURN TO CLASSROOM
# ============================================

func _return_to_classroom() -> void:

	if not ResourceLoader.exists(
		CLASSROOM_SCENE,
        "PackedScene"
	):

		push_error(
            "Classroom scene not found: "
			+ CLASSROOM_SCENE
		)

		return

	get_tree().change_scene_to_file.call_deferred(
		CLASSROOM_SCENE
	)


# ============================================
# LABEL HELPER
# ============================================

func _make_label(
	value: String,
	font_size: int,
	font_color: Color
) -> Label:

	var result: Label = Label.new()

	result.text = value

	result.autowrap_mode = (
		TextServer.AUTOWRAP_WORD_SMART
	)

	result.add_theme_font_size_override(
		"font_size",
		font_size
	)

	result.add_theme_color_override(
		"font_color",
		font_color
	)

	return result


# ============================================
# BUTTON HELPER
# ============================================

func _button_style(
	color: Color
) -> StyleBoxFlat:

	var style: StyleBoxFlat = StyleBoxFlat.new()

	style.bg_color = color
	style.border_color = Color("#c7baa3")

	style.set_border_width_all(1)
	style.set_corner_radius_all(8)

	style.content_margin_left = 12
	style.content_margin_right = 12
	style.content_margin_top = 8
	style.content_margin_bottom = 8

	return style
