extends CanvasLayer
## Attach this script to your existing SchoolHUD/TaskUI CanvasLayer.
## It uses the SAME 5 school tasks plus the HOME assignment.
## Creates Task6 by copying the style of Task5; does not rebuild the UI.

const TASK_IDS: Array[String] = [
	"attend_class",
	"borrow_book",
	"health_form",
	"eat_lunch",
	"computer_activity",
	"home_assignment"
]

const TASK_NAMES: Array[String] = [
	"Attend Class",
	"Borrow a Book",
	"Get Health Form",
	"Eat Lunch",
	"Finish Computer Activity",
	"Complete Home Assignment"
]

const HOMEWORK_COMPLETE_META: StringName = &"equalpath_bedroom_homework_complete"

var game_state: Node = null


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_ALWAYS
	game_state = get_node_or_null("/root/GameState")
	if game_state == null:
		push_error("TaskUI: GameState Autoload not found.")
		return

	_ensure_task6_label()
	if game_state.has_signal("school_task_updated"):
		if not game_state.is_connected("school_task_updated", _refresh):
			game_state.connect("school_task_updated", _refresh)
	if game_state.has_signal("school_tasks_reset"):
		if not game_state.is_connected("school_tasks_reset", _refresh):
			game_state.connect("school_tasks_reset", _refresh)
	_refresh()


func _ensure_task6_label() -> void:
	if find_child("Task6", true, false) != null:
		return
	var task5: Label = find_child("Task5", true, false) as Label
	if task5 == null:
		push_warning("TaskUI: Can't find Task5. Create a Label named Task6 manually.")
		return
	var task6: Label = task5.duplicate() as Label
	if task6 == null:
		return
	task6.name = "Task6"
	task5.get_parent().add_child(task6)
	if not (task5.get_parent() is Container):
		# When Task5 is positioned manually, place Task6 directly underneath.
		var next_row_y: float = maxf(task5.size.y + 8.0, 34.0)
		task6.position = task5.position + Vector2(0.0, next_row_y)
	task6.show()


func _task_done(task_id: String) -> bool:
	if game_state == null:
		return false
	var done: bool = false
	if game_state.has_method("is_school_task_complete"):
		done = bool(game_state.call("is_school_task_complete", task_id))
	if task_id == "home_assignment":
		done = done or bool(game_state.get_meta(HOMEWORK_COMPLETE_META, false))
	return done


func _refresh() -> void:
	if game_state == null:
		return

	var completed_count: int = 0
	for task_id: String in TASK_IDS:
		if _task_done(task_id):
			completed_count += 1

	var progress: Label = find_child("Progress", true, false) as Label
	if progress != null:
		progress.text = "%d / %d Completed" % [completed_count, TASK_IDS.size()]

	# SchoolHUD owns its TasksButton and will refresh its school-only count.
	# Update the label AFTER that refresh, without replacing SchoolHUD.gd.
	call_deferred("_refresh_hud_tasks_button")

	for i: int in range(TASK_IDS.size()):
		var task_label: Label = find_child("Task" + str(i + 1), true, false) as Label
		if task_label == null:
			continue
		var done: bool = _task_done(TASK_IDS[i])
		task_label.text = ("✓ " if done else "□ ") + TASK_NAMES[i]
		task_label.add_theme_color_override(
			"font_color", Color("#9bcca5") if done else Color("#fff1d2")
		)


func _refresh_hud_tasks_button() -> void:
	var hud: Node = get_node_or_null("/root/SchoolHUD")
	if hud == null:
		return
	var tasks_button: Button = hud.get_node_or_null("HUDRoot/TasksButton") as Button
	if tasks_button == null:
		return
	var completed_count: int = 0
	for task_id: String in TASK_IDS:
		if _task_done(task_id):
			completed_count += 1
	tasks_button.text = "TASKS %d/%d [Q]" % [completed_count, TASK_IDS.size()]
