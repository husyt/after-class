extends CanvasLayer
## Attach to your existing TaskUI child scene, NOT the SchoolHUD root.
## Does not reposition any controls: your custom task panel design stays intact.

const TASK_IDS: Array[String] = [
	"attend_class", "borrow_book", "health_form", "eat_lunch", "computer_activity"
]
const TASK_NAMES: Array[String] = [
	"Attend Class", "Borrow a Book", "Get Health Form", "Eat Lunch", "Finish Computer Activity"
]

var game_state: Node = null


func _ready() -> void:
	process_mode = Node.PROCESS_MODE_ALWAYS
	game_state = get_node_or_null("/root/GameState")
	if game_state == null:
		push_error("TaskUI: GameState Autoload not found.")
		return
	if game_state.has_signal("school_task_updated") and not game_state.is_connected("school_task_updated", _refresh):
		game_state.connect("school_task_updated", _refresh)
	_refresh()


func _refresh() -> void:
	if game_state == null:
		return
	var progress: Label = find_child("Progress", true, false) as Label
	var count: int = int(game_state.call("get_school_task_count"))
	var total: int = int(game_state.call("get_school_task_total"))
	if progress != null:
		progress.text = "%d / %d Completed" % [count, total]
	for i: int in range(TASK_IDS.size()):
		var task_label: Label = find_child("Task" + str(i + 1), true, false) as Label
		if task_label == null:
			continue
		var done: bool = bool(game_state.call("is_school_task_complete", TASK_IDS[i]))
		task_label.text = ("✓ " if done else "□ ") + TASK_NAMES[i]
		task_label.add_theme_color_override("font_color", Color("#9bcca5") if done else Color("#fff1d2"))
