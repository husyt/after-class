extends Node

signal school_task_updated

var saved_positions: Dictionary = {}

var school_tasks: Dictionary = {
	"attend_class": false,
	"borrow_book": false,
	"health_form": false,
	"eat_lunch": false,
	"computer_activity": false
}


func save_position(scene_path: String, player_position: Vector3) -> void:
	if scene_path.is_empty():
		return

	saved_positions[scene_path] = player_position


func has_position(scene_path: String) -> bool:
	return saved_positions.has(scene_path)


func get_position(scene_path: String) -> Vector3:
	return saved_positions.get(scene_path, Vector3.ZERO)


func clear_position(scene_path: String) -> void:
	if saved_positions.has(scene_path):
		saved_positions.erase(scene_path)


func clear_positions() -> void:
	saved_positions.clear()


func complete_school_task(task_id: String) -> void:
	if !school_tasks.has(task_id):
		return

	if school_tasks[task_id]:
		return

	school_tasks[task_id] = true

	school_task_updated.emit()

	print(
		"TASK COMPLETE: ",
		task_id,
		" | ",
		get_school_task_count(),
		"/5"
	)


func get_school_task_count() -> int:
	var total: int = 0

	for task_complete in school_tasks.values():
		if task_complete:
			total += 1

	return total


func school_tasks_complete() -> bool:
	return get_school_task_count() == 5


func reset_school_tasks() -> void:
	for task_id in school_tasks.keys():
		school_tasks[task_id] = false

	school_task_updated.emit()
