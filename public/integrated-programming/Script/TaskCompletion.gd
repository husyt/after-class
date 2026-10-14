extends Node
## Optional. Attach to a mini-game's relay child; call complete() when won.

@export_enum("attend_class", "borrow_book", "health_form", "eat_lunch", "computer_activity")
var task_id: String = "attend_class"


func complete() -> bool:
	var hud: Node = get_node_or_null("/root/SchoolHUD")
	if hud != null and hud.has_method("complete_task"):
		return bool(hud.call("complete_task", StringName(task_id)))
	var state: Node = get_node_or_null("/root/GameState")
	if state != null:
		return bool(state.call("complete_school_task", task_id))
	push_warning("TaskCompletionRelay: GameState Autoload is missing.")
	return false
