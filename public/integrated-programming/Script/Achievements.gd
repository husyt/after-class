extends CanvasLayer
## EqualPath achievements UI. Hide button/notifications when any mini-game or form is shown.
## This replaces the existing Achievements script; do not add a second Autoload.

signal achievement_unlocked(task_id: String)

class MedalBadge:
	extends Control

	var medal_color: Color = Color("#E8B75C")
	var ribbon_color: Color = Color("#BE7044")
	var unlocked: bool = false

	func configure(metal: Color, ribbon: Color, is_unlocked: bool) -> void:
		medal_color = metal
		ribbon_color = ribbon
		unlocked = is_unlocked
		queue_redraw()

	func _draw() -> void:
		var w: float = size.x
		var h: float = size.y
		var center: Vector2 = Vector2(w * 0.5, h * 0.62)
		var radius: float = minf(w * 0.26, h * 0.24)
		var ribbon: Color = ribbon_color if unlocked else Color("#454852")
		var metal: Color = medal_color if unlocked else Color("#535761")
		var bright: Color = Color("#FFF2CC") if unlocked else Color("#737782")

		var left_ribbon: PackedVector2Array = PackedVector2Array([
			Vector2(w * 0.33, h * 0.10), Vector2(w * 0.49, h * 0.10),
			Vector2(w * 0.54, h * 0.51), Vector2(w * 0.42, h * 0.72),
			Vector2(w * 0.34, h * 0.51)
		])
		var right_ribbon: PackedVector2Array = PackedVector2Array([
			Vector2(w * 0.51, h * 0.10), Vector2(w * 0.67, h * 0.10),
			Vector2(w * 0.66, h * 0.51), Vector2(w * 0.58, h * 0.72),
			Vector2(w * 0.46, h * 0.51)
		])
		draw_colored_polygon(left_ribbon, ribbon)
		draw_colored_polygon(right_ribbon, ribbon.darkened(0.25))
		draw_line(Vector2(w * 0.40, h * 0.11), Vector2(w * 0.45, h * 0.46), ribbon.lightened(0.25), 2.0)
		draw_line(Vector2(w * 0.60, h * 0.11), Vector2(w * 0.55, h * 0.46), ribbon.lightened(0.20), 2.0)
		draw_circle(center + Vector2(1.4, 2.4), radius + 4.0, Color(0.02, 0.025, 0.04, 0.5))
		draw_circle(center, radius + 3.0, metal.darkened(0.40))
		draw_circle(center, radius, metal)
		draw_circle(center + Vector2(-radius * 0.20, -radius * 0.25), radius * 0.58, metal.lightened(0.22))
		draw_arc(center, radius - 4.0, 0.0, TAU, 40, metal.darkened(0.27), 2.0, true)

		var star_points: PackedVector2Array = PackedVector2Array()
		for i: int in range(10):
			var angle: float = -PI * 0.5 + float(i) * PI / 5.0
			var star_radius: float = radius * (0.58 if i % 2 == 0 else 0.26)
			star_points.append(center + Vector2(cos(angle), sin(angle)) * star_radius)
		draw_colored_polygon(star_points, bright)


const TASK_ORDER: Array[String] = [
	"attend_class", "borrow_book", "health_form", "eat_lunch",
	"computer_activity", "basketball"
]

const TASK_DATA: Dictionary = {
	"attend_class": {
		"title": "Class Champion", "description": "You attended your class!",
		"medal": "BRONZE MEDAL", "metal": "#DB9966", "ribbon": "#B15E45"
	},
	"borrow_book": {
		"title": "Book Explorer", "description": "You borrowed a book from the library!",
		"medal": "SILVER MEDAL", "metal": "#CDD8E4", "ribbon": "#4277B3"
	},
	"health_form": {
		"title": "Healthy Learner", "description": "You completed your health form!",
		"medal": "EMERALD MEDAL", "metal": "#65D5AC", "ribbon": "#28876F"
	},
	"eat_lunch": {
		"title": "Well-Fueled Student", "description": "You finished your lunch!",
		"medal": "GOLD MEDAL", "metal": "#F2C663", "ribbon": "#C87735"
	},
	"computer_activity": {
		"title": "Digital Explorer", "description": "You completed the computer activity!",
		"medal": "SAPPHIRE MEDAL", "metal": "#77BAEF", "ribbon": "#5D59BD"
	},
	"basketball": {
		"title": "Hoop Champion", "description": "You made 10 baskets in the basketball mini-game!",
		"medal": "CHAMPION MEDAL", "metal": "#F5AC46", "ribbon": "#C94C34",
		"locked": "LOCKED - MAKE 10 BASKETS"
	}
}

# Include all scenes that have a full-screen form/mini-game.
# The menu, medal popup, and medal collection should not cover these UIs.
const HIDDEN_SCENE_KEYWORDS: Array[String] = [
	"main_menu", "mainmenu", "ending_video",
	"clinic_health_checkup", "health_checkup", "student_health",
	"health_form", "class_activity", "computer_activity",
	"library_borrowing", "clinic_sleep", "guidance_warning",
	"guidance_logbook", "basketball_minigame", "basketball_game",
	"homework", "assignment", "wifi_challenge"
]

@export_range(1.0, 10.0, 0.5) var popup_duration: float = 3.5
@export var show_achievements_button: bool = true
@export var align_with_settings_button: bool = true
@export_range(0.0, 30.0, 1.0) var notification_gap: float = 8.0

var game_state: Node = null
var _unlocked: Dictionary = {}
var _pending: Array[String] = []
var _showing_notification: bool = false
var _active_notification_id: String = ""
var _notification_tween: Tween = null

var _screen: Control = null
var _menu_button: Button = null
var _notification: PanelContainer = null
var _notification_icon: MedalBadge = null
var _notification_medal: Label = null
var _notification_title: Label = null
var _notification_description: Label = null
var _notification_progress: Label = null
var _collection_layer: Control = null
var _collection_progress: Label = null
var _achievement_rows: Dictionary = {}
var _last_settings_button_id: int = 0


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_ALWAYS
	layer = 85
	_build_interface()
	game_state = get_node_or_null("/root/GameState")
	if game_state == null:
		push_error("Achievements: Add GameState as an Autoload before Achievements.")
		return
	if game_state.has_signal("school_task_completed"):
		game_state.connect("school_task_completed", _on_school_task_completed)
	else:
		push_error("Achievements: GameState needs school_task_completed(task_id).")
	if game_state.has_signal("school_tasks_reset"):
		game_state.connect("school_tasks_reset", _on_school_tasks_reset)
	for task_id: String in TASK_ORDER:
		if task_id == "basketball":
			if game_state.has_method("get_basketball_baskets") and int(game_state.call("get_basketball_baskets")) >= 10:
				_unlocked[task_id] = true
		elif bool(game_state.call("is_school_task_complete", task_id)):
			_unlocked[task_id] = true
	_update_display()


func _process(_delta: float) -> void:
	if _menu_button == null:
		return

	var hidden: bool = _should_hide_over_gameplay_ui()
	if hidden:
		_menu_button.hide()
		if _collection_layer.visible:
			_close_collection()
		# If a medal popup was playing when a form opened, save it for later.
		if _showing_notification:
			_suspend_notification()
		else:
			_notification.hide()
		return

	var settings_open: bool = false
	var hud: Node = get_node_or_null("/root/SchoolHUD")
	if hud != null:
		var overlay: Control = hud.get_node_or_null("SettingsOverlay") as Control
		if overlay != null:
			settings_open = overlay.visible
		if align_with_settings_button:
			_align_button_to_hud(hud)

	_menu_button.visible = show_achievements_button and not settings_open
	if settings_open:
		if _collection_layer.visible:
			_close_collection()
		if _showing_notification:
			_suspend_notification()
		return

	if not _pending.is_empty() and not _showing_notification:
		_show_next_notification()


func _should_hide_over_gameplay_ui() -> bool:
	var scene: Node = get_tree().current_scene
	if scene == null:
		return true
	var filename: String = scene.scene_file_path.get_file().to_lower()
	for keyword: String in HIDDEN_SCENE_KEYWORDS:
		if keyword in filename:
			return true
	# Fullscreen Control is a UI-only scene (e.g. an activity or health form).
	# Do not cover it with the persistent achievements button.
	if scene is Control:
		return true
	return false


func _suspend_notification() -> void:
	if _notification_tween != null and _notification_tween.is_running():
		_notification_tween.kill()
	if not _active_notification_id.is_empty():
		_pending.push_front(_active_notification_id)
	_active_notification_id = ""
	_showing_notification = false
	_notification.hide()


func _align_button_to_hud(hud: Node) -> void:
	var settings_button: Button = hud.get_node_or_null("HUDRoot/SettingsButton") as Button
	var tasks_button: Button = hud.get_node_or_null("HUDRoot/TasksButton") as Button
	var reference_button: Button = tasks_button if tasks_button != null else settings_button
	if reference_button == null or not is_instance_valid(reference_button):
		return

	var screen_rect: Rect2 = _screen.get_global_rect()
	var reference_rect: Rect2 = reference_button.get_global_rect()
	var gap: float = 12.0
	if settings_button != null and tasks_button != null:
		var settings_rect: Rect2 = settings_button.get_global_rect()
		gap = maxf(6.0, reference_rect.position.y - settings_rect.end.y)
	var left_x: float = maxf(0.0, reference_rect.position.x - screen_rect.position.x)
	var top_y: float = maxf(0.0, reference_rect.end.y - screen_rect.position.y + gap)
	var button_width: float = reference_rect.size.x
	var button_height: float = reference_rect.size.y
	if button_width <= 0.0 or button_height <= 0.0:
		return

	_menu_button.set_anchors_preset(Control.PRESET_TOP_LEFT)
	_menu_button.position = Vector2(left_x, top_y)
	_menu_button.size = Vector2(button_width, button_height)

	var hud_id: int = reference_button.get_instance_id()
	if _last_settings_button_id != hud_id:
		_last_settings_button_id = hud_id
		for style_name: String in ["normal", "hover", "pressed", "focus", "disabled"]:
			var source_style: StyleBox = reference_button.get_theme_stylebox(style_name)
			if source_style != null:
				_menu_button.add_theme_stylebox_override(style_name, source_style.duplicate() as StyleBox)
		_menu_button.add_theme_font_size_override(
			"font_size", reference_button.get_theme_font_size("font_size")
		)
		for color_name: String in ["font_color", "font_hover_color", "font_pressed_color", "font_disabled_color"]:
			_menu_button.add_theme_color_override(
				color_name, reference_button.get_theme_color(color_name)
			)

	var notification_width: float = minf(425.0, maxf(220.0, _screen.size.x - left_x - 8.0))
	_notification.set_anchors_preset(Control.PRESET_TOP_LEFT)
	_notification.position = Vector2(left_x, top_y + button_height + notification_gap)
	_notification.size = Vector2(notification_width, 140.0)


func _unhandled_key_input(event: InputEvent) -> void:
	if not event is InputEventKey:
		return
	var key: InputEventKey = event as InputEventKey
	if not key.pressed or key.echo or key.keycode != KEY_H:
		return
	if get_tree().paused or _menu_button == null or not _menu_button.visible:
		return
	if _should_hide_over_gameplay_ui():
		return
	_toggle_collection()
	get_viewport().set_input_as_handled()


func unlock_basketball_medal() -> void:
	# Protect against old mini-game scripts calling this prematurely.
	if game_state != null and game_state.has_method("get_basketball_baskets"):
		if int(game_state.call("get_basketball_baskets")) >= 10:
			_on_school_task_completed("basketball")


func _on_school_task_completed(task_id: String) -> void:
	if task_id == "basketball":
		if game_state == null or not game_state.has_method("get_basketball_baskets"):
			return
		if int(game_state.call("get_basketball_baskets")) < 10:
			return
	if not TASK_DATA.has(task_id) or _unlocked.has(task_id):
		return
	_unlocked[task_id] = true
	achievement_unlocked.emit(task_id)
	_pending.append(task_id)
	_update_display()
	# Keep the earned medal, but do not show its popup on top of a form.
	if not _should_hide_over_gameplay_ui():
		_show_next_notification()


func _on_school_tasks_reset() -> void:
	_unlocked.clear()
	_pending.clear()
	_showing_notification = false
	_active_notification_id = ""
	if _notification_tween != null and _notification_tween.is_running():
		_notification_tween.kill()
	_notification.hide()
	_update_display()


func _show_next_notification() -> void:
	if _should_hide_over_gameplay_ui() or _showing_notification or _pending.is_empty():
		return
	var task_id: String = _pending.pop_front()
	_active_notification_id = task_id
	var details: Dictionary = TASK_DATA.get(task_id, {})
	_notification_medal.text = String(details.get("medal", "MEDAL UNLOCKED"))
	_notification_title.text = String(details.get("title", "Task Completed"))
	_notification_description.text = String(details.get("description", "Achievement unlocked!"))
	_notification_progress.text = "%d OF %d MEDALS EARNED" % [_unlocked.size(), TASK_ORDER.size()]
	_notification_icon.configure(_metal(task_id), _ribbon(task_id), true)
	_showing_notification = true
	_notification.modulate.a = 0.0
	_notification.show()
	_notification_tween = create_tween()
	_notification_tween.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
	_notification_tween.tween_property(_notification, "modulate:a", 1.0, 0.30).set_trans(Tween.TRANS_SINE)
	_notification_tween.tween_interval(popup_duration)
	_notification_tween.tween_property(_notification, "modulate:a", 0.0, 0.35).set_trans(Tween.TRANS_SINE)
	_notification_tween.tween_callback(_finish_notification)


func _finish_notification() -> void:
	_notification.hide()
	_showing_notification = false
	_active_notification_id = ""
	_show_next_notification()


func _toggle_collection() -> void:
	if _should_hide_over_gameplay_ui():
		return
	if _collection_layer.visible:
		_close_collection()
	else:
		_update_display()
		_collection_layer.show()


func _close_collection() -> void:
	_collection_layer.hide()


func _metal(task_id: String) -> Color:
	var details: Dictionary = TASK_DATA.get(task_id, {})
	return Color(String(details.get("metal", "#F2C663")))


func _ribbon(task_id: String) -> Color:
	var details: Dictionary = TASK_DATA.get(task_id, {})
	return Color(String(details.get("ribbon", "#C87735")))


func _update_display() -> void:
	if _menu_button == null:
		return
	_menu_button.text = "MEDALS %d/%d [H]" % [_unlocked.size(), TASK_ORDER.size()]
	_collection_progress.text = "%d of %d medals earned" % [_unlocked.size(), TASK_ORDER.size()]
	for task_id: String in TASK_ORDER:
		var row: Dictionary = _achievement_rows.get(task_id, {})
		if row.is_empty():
			continue
		var icon: MedalBadge = row.get("icon") as MedalBadge
		var name_label: Label = row.get("name") as Label
		var status: Label = row.get("status") as Label
		var details: Dictionary = TASK_DATA.get(task_id, {})
		var earned: bool = _unlocked.has(task_id)
		if icon != null:
			icon.configure(_metal(task_id), _ribbon(task_id), earned)
		if name_label != null:
			name_label.text = String(details.get("title", task_id))
			name_label.add_theme_color_override("font_color", Color("#FFF2D7") if earned else Color("#858A94"))
		if status != null:
			status.text = String(details.get("medal", "MEDAL")) if earned else String(details.get("locked", "LOCKED - COMPLETE THE TASK"))
			status.add_theme_color_override("font_color", _metal(task_id) if earned else Color("#717780"))


func _build_interface() -> void:
	_screen = Control.new()
	_screen.name = "AchievementUI"
	_screen.mouse_filter = Control.MOUSE_FILTER_IGNORE
	add_child(_screen)
	_screen.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	_menu_button = Button.new()
	_menu_button.name = "MedalsButton"
	_menu_button.focus_mode = Control.FOCUS_NONE
	_menu_button.add_theme_font_size_override("font_size", 15)
	_menu_button.add_theme_color_override("font_color", Color("#F7D99F"))
	_menu_button.add_theme_stylebox_override("normal", _style(Color("#1C1D28"), Color("#D5A550"), 8, 6))
	_menu_button.add_theme_stylebox_override("hover", _style(Color("#30313D"), Color("#F5C675"), 8, 6))
	_menu_button.pressed.connect(_toggle_collection)
	_screen.add_child(_menu_button)
	_menu_button.set_anchors_preset(Control.PRESET_TOP_LEFT)
	_menu_button.position = Vector2(30.0, 170.0)
	_menu_button.size = Vector2(220.0, 56.0)

	_notification = PanelContainer.new()
	_notification.name = "MedalNotification"
	_notification.mouse_filter = Control.MOUSE_FILTER_IGNORE
	_notification.add_theme_stylebox_override("panel", _style(Color("#141824"), Color("#E7B75D"), 12, 12))
	_screen.add_child(_notification)
	_notification.set_anchors_preset(Control.PRESET_TOP_LEFT)
	_notification.position = Vector2(30.0, 234.0)
	_notification.size = Vector2(425.0, 140.0)

	var notice_row: HBoxContainer = HBoxContainer.new()
	notice_row.add_theme_constant_override("separation", 13)
	_notification.add_child(notice_row)
	_notification_icon = MedalBadge.new()
	_notification_icon.custom_minimum_size = Vector2(82, 94)
	_notification_icon.mouse_filter = Control.MOUSE_FILTER_IGNORE
	notice_row.add_child(_notification_icon)

	var text_stack: VBoxContainer = VBoxContainer.new()
	text_stack.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	text_stack.add_theme_constant_override("separation", 5)
	notice_row.add_child(text_stack)
	text_stack.add_child(_label("MEDAL UNLOCKED!", 15, Color("#F5CC7D")))
	_notification_medal = _label("", 12, Color("#F5CC7D"))
	text_stack.add_child(_notification_medal)
	_notification_title = _label("", 20, Color.WHITE)
	text_stack.add_child(_notification_title)
	_notification_description = _label("", 13, Color("#CED3DD"))
	text_stack.add_child(_notification_description)
	_notification_progress = _label("", 12, Color("#F5CC7D"))
	text_stack.add_child(_notification_progress)
	_notification.hide()

	_collection_layer = Control.new()
	_collection_layer.name = "MedalCollection"
	_collection_layer.mouse_filter = Control.MOUSE_FILTER_IGNORE
	_screen.add_child(_collection_layer)
	_collection_layer.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var backdrop: ColorRect = ColorRect.new()
	backdrop.color = Color(0.0, 0.0, 0.0, 0.72)
	backdrop.mouse_filter = Control.MOUSE_FILTER_STOP
	_collection_layer.add_child(backdrop)
	backdrop.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var center: CenterContainer = CenterContainer.new()
	center.mouse_filter = Control.MOUSE_FILTER_IGNORE
	_collection_layer.add_child(center)
	center.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var panel: PanelContainer = PanelContainer.new()
	panel.name = "MedalPanel"
	panel.custom_minimum_size = Vector2(565, 475)
	panel.add_theme_stylebox_override("panel", _style(Color("#131824"), Color("#CF9D48"), 14, 14))
	center.add_child(panel)

	var stack: VBoxContainer = VBoxContainer.new()
	stack.add_theme_constant_override("separation", 8)
	panel.add_child(stack)
	var heading: Label = _label("MEDAL COLLECTION", 28, Color("#F5CC7D"))
	heading.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	stack.add_child(heading)
	_collection_progress = _label("0 of 6 medals earned", 16, Color("#D1D6DD"))
	_collection_progress.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	stack.add_child(_collection_progress)
	stack.add_child(HSeparator.new())

	var medal_scroller: ScrollContainer = ScrollContainer.new()
	medal_scroller.name = "MedalScroller"
	medal_scroller.custom_minimum_size.y = 315.0
	medal_scroller.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	stack.add_child(medal_scroller)

	var medal_list: VBoxContainer = VBoxContainer.new()
	medal_list.size_flags_horizontal = Control.SIZE_EXPAND_FILL
	medal_list.add_theme_constant_override("separation", 4)
	medal_scroller.add_child(medal_list)

	for task_id: String in TASK_ORDER:
		var details: Dictionary = TASK_DATA.get(task_id, {})
		var row: HBoxContainer = HBoxContainer.new()
		row.custom_minimum_size.y = 59.0
		row.add_theme_constant_override("separation", 14)
		medal_list.add_child(row)
		var medal: MedalBadge = MedalBadge.new()
		medal.custom_minimum_size = Vector2(58, 57)
		medal.mouse_filter = Control.MOUSE_FILTER_IGNORE
		row.add_child(medal)
		var line_text: VBoxContainer = VBoxContainer.new()
		line_text.size_flags_horizontal = Control.SIZE_EXPAND_FILL
		line_text.add_theme_constant_override("separation", 2)
		row.add_child(line_text)
		var medal_name: Label = _label(String(details.get("title", task_id)), 18, Color.WHITE)
		line_text.add_child(medal_name)
		var medal_status: Label = _label("LOCKED", 13, Color("#858A94"))
		line_text.add_child(medal_status)
		_achievement_rows[task_id] = {"icon": medal, "name": medal_name, "status": medal_status}

	var close_button: Button = Button.new()
	close_button.text = "CLOSE [H]"
	close_button.custom_minimum_size = Vector2(0, 42)
	close_button.add_theme_font_size_override("font_size", 17)
	close_button.add_theme_stylebox_override("normal", _style(Color("#2A2C36"), Color("#CBA255"), 7, 8))
	close_button.pressed.connect(_close_collection)
	stack.add_child(close_button)
	_collection_layer.hide()


func _label(message: String, font_size: int, tint: Color) -> Label:
	var result: Label = Label.new()
	result.text = message
	result.add_theme_font_size_override("font_size", font_size)
	result.add_theme_color_override("font_color", tint)
	result.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	return result


func _style(fill: Color, outline: Color, radius: int, pad_y: int = 12) -> StyleBoxFlat:
	var style: StyleBoxFlat = StyleBoxFlat.new()
	style.bg_color = fill
	style.border_color = outline
	style.set_border_width_all(1)
	style.set_corner_radius_all(radius)
	style.content_margin_left = 15.0
	style.content_margin_right = 15.0
	style.content_margin_top = float(pad_y)
	style.content_margin_bottom = float(pad_y)
	return style
