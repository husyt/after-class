extends Node
## Optional mini-game completion relay. Attach to the ORIGINAL relay child,
## not the TaskUI and not the SchoolHUD root. No new Autoload required.

@export_enum("attend_class", "borrow_book", "health_form", "eat_lunch", "computer_activity", "home_assignment")
var task_id: String = "attend_class"


func complete() -> bool:
	var state: Node = get_node_or_null("/root/GameState")
	if state == null:
		push_warning("TaskCompletionRelay: GameState Autoload is missing.")
		return false

	# Homework occurs after school, outside the SchoolHUD time-limit.
	if task_id == "home_assignment":
		if state.has_method("complete_home_assignment"):
			return bool(state.call("complete_home_assignment"))
		if state.has_method("complete_school_task"):
			return bool(state.call("complete_school_task", "home_assignment"))
		return false

	var hud: Node = get_node_or_null("/root/SchoolHUD")
	if hud != null and hud.has_method("complete_task"):
		return bool(hud.call("complete_task", StringName(task_id)))
	return bool(state.call("complete_school_task", task_id))
