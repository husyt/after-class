extends Node
## Autoload as GameState. Shared task progress + saved scene positions.

signal school_task_updated
signal school_task_completed(task_id: String)
signal school_tasks_reset

const TASK_IDS: Array[String] = ["attend_class", "borrow_book", "health_form", "eat_lunch", "computer_activity"]

var saved_positions: Dictionary = {}
var school_tasks: Dictionary = {
	"attend_class": false,
	"borrow_book": false,
	"health_form": false,
	"eat_lunch": false,
	"computer_activity": false
}
var _interaction_owner: Node = null


func normalize_school_task_id(task_id: String) -> String:
	var normalized: String = task_id.strip_edges().to_lower()
	return "health_form" if normalized == "get_health_form" else normalized


func can_complete_school_task(task_id: String) -> bool:
	var id: String = normalize_school_task_id(task_id)
	if not school_tasks.has(id) or bool(school_tasks[id]):
		return false
	# Keep direct task completion compatible with older school_task.gd scripts
	# that mark success during a temporary black-screen pause.
	return true


func complete_school_task(task_id: String) -> bool:
	var id: String = normalize_school_task_id(task_id)
	if not can_complete_school_task(id):
		return false
	school_tasks[id] = true
	school_task_updated.emit()
	school_task_completed.emit(id)
	print("CHECKMARK ADDED: ", id, " (", get_school_task_count(), "/5)")
	return true


func is_school_task_complete(task_id: String) -> bool:
	return bool(school_tasks.get(normalize_school_task_id(task_id), false))


func get_school_task_count() -> int:
	var count: int = 0
	for id: String in TASK_IDS:
		if bool(school_tasks.get(id, false)):
			count += 1
	return count


func get_school_task_total() -> int:
	return TASK_IDS.size()


func school_tasks_complete() -> bool:
	return get_school_task_count() == get_school_task_total()


func reset_school_tasks() -> void:
	for id: String in TASK_IDS:
		school_tasks[id] = false
	school_tasks_reset.emit()
	school_task_updated.emit()


func get_school_progress_text() -> String:
	return "%d / %d Completed" % [get_school_task_count(), get_school_task_total()]


func try_begin_interaction(owner: Node) -> bool:
	if not is_instance_valid(owner) or is_interaction_busy():
		return false
	_interaction_owner = owner
	return true


func end_interaction(owner: Node) -> void:
	if _interaction_owner == owner:
		_interaction_owner = null


func is_interaction_busy() -> bool:
	if not is_instance_valid(_interaction_owner):
		_interaction_owner = null
	return _interaction_owner != null


func save_position(scene_path: String, player_position: Vector3) -> void:
	if not scene_path.is_empty():
		saved_positions[scene_path] = player_position


func has_position(scene_path: String) -> bool:
	return saved_positions.has(scene_path)


func get_position(scene_path: String) -> Vector3:
	return saved_positions.get(scene_path, Vector3.ZERO)


func has_saved_position(scene_path: String) -> bool:
	return has_position(scene_path)


func get_saved_position(scene_path: String) -> Vector3:
	return get_position(scene_path)


func clear_position(scene_path: String) -> void:
	saved_positions.erase(scene_path)


func clear_positions() -> void:
	saved_positions.clear()
