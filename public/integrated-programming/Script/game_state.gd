extends Node

signal school_task_updated
signal school_task_completed(task_id: String)
signal school_tasks_reset

# Only these FIVE tasks are required to exit school.
# Homework is the sixth OVERALL task, completed later at home.
const HOME_ASSIGNMENT_ID: String = "home_assignment"
const HOMEWORK_COMPLETE_META: StringName = &"equalpath_bedroom_homework_complete"

const TASK_IDS: Array[String] = [
	"attend_class",
	"borrow_book",
	"health_form",
	"eat_lunch",
	"computer_activity"
]

# Final results: 100 points per completed task and 10 per made basket.
# Attempts count valid released basketball shots, not mouse drags.
const POINTS_PER_TASK: int = 100
const POINTS_PER_BASKET: int = 10
const BASKETS_FOR_HOOP_MEDAL: int = 10

var basketball_attempts: int = 0
var basketball_baskets: int = 0

var saved_positions: Dictionary = {}

var school_tasks: Dictionary = {
	"attend_class": false,
	"borrow_book": false,
	"health_form": false,
	"eat_lunch": false,
	"computer_activity": false,
	"home_assignment": false
}

var notebook_progress: Dictionary = {}
var _interaction_owner: Node = null
var _pending_spawn_scene: String = ""
var _pending_spawn_marker: String = ""


func normalize_school_task_id(task_id: String) -> String:
	var normalized: String = task_id.strip_edges().to_lower()
	return "health_form" if normalized == "get_health_form" else normalized


func can_complete_school_task(task_id: String) -> bool:
	var id: String = normalize_school_task_id(task_id)
	return school_tasks.has(id) and not is_school_task_complete(id)


func complete_school_task(task_id: String) -> bool:
	var id: String = normalize_school_task_id(task_id)
	if not can_complete_school_task(id):
		return false

	school_tasks[id] = true
	if id == HOME_ASSIGNMENT_ID:
		set_meta(HOMEWORK_COMPLETE_META, true)
	school_task_updated.emit()
	school_task_completed.emit(id)
	print("CHECKMARK ADDED: ", id, " (", get_school_task_count(), "/", get_school_task_total(), ")")
	return true


func is_school_task_complete(task_id: String) -> bool:
	var id: String = normalize_school_task_id(task_id)
	if id == HOME_ASSIGNMENT_ID:
		return bool(school_tasks.get(id, false)) or bool(get_meta(HOMEWORK_COMPLETE_META, false))
	return bool(school_tasks.get(id, false))


func get_school_task_count() -> int:
	var count: int = 0
	for id: String in TASK_IDS:
		if bool(school_tasks.get(id, false)):
			count += 1
	return count


func get_school_task_total() -> int:
	return TASK_IDS.size()


func school_tasks_complete() -> bool:
	# Do not include homework here: the student must be able to leave school first.
	return get_school_task_count() == get_school_task_total()


func complete_home_assignment() -> bool:
	return complete_school_task(HOME_ASSIGNMENT_ID)


func get_all_task_count() -> int:
	return get_school_task_count() + (1 if is_school_task_complete(HOME_ASSIGNMENT_ID) else 0)


func get_all_task_total() -> int:
	return get_school_task_total() + 1


func all_tasks_complete() -> bool:
	return school_tasks_complete() and is_school_task_complete(HOME_ASSIGNMENT_ID)


func record_basketball_attempt() -> void:
	basketball_attempts += 1


func record_basketball_basket() -> void:
	# Never record more made baskets than attempted shots.
	var previous_baskets: int = basketball_baskets
	basketball_baskets = mini(basketball_baskets + 1, basketball_attempts)

	# Unlock only on the TENTH successful basket, not on an attempt.
	# Achievements listens for this existing signal.
	if previous_baskets < BASKETS_FOR_HOOP_MEDAL and basketball_baskets >= BASKETS_FOR_HOOP_MEDAL:
		school_task_completed.emit("basketball")
		print("MEDAL UNLOCKED: Hoop Champion (10 made baskets)")


func get_basketball_attempts() -> int:
	return basketball_attempts


func get_basketball_baskets() -> int:
	return basketball_baskets


func get_basketball_misses() -> int:
	return maxi(0, basketball_attempts - basketball_baskets)


func get_basketball_accuracy() -> float:
	if basketball_attempts <= 0:
		return 0.0
	return 100.0 * float(basketball_baskets) / float(basketball_attempts)


func get_task_points(task_id: String) -> int:
	return POINTS_PER_TASK if is_school_task_complete(task_id) else 0


func get_school_score() -> int:
	return get_school_task_count() * POINTS_PER_TASK


func get_homework_score() -> int:
	return get_task_points(HOME_ASSIGNMENT_ID)


func get_basketball_score() -> int:
	return basketball_baskets * POINTS_PER_BASKET


func get_final_score() -> int:
	return get_school_score() + get_homework_score() + get_basketball_score()


func reset_school_tasks() -> void:
	for id: String in TASK_IDS:
		school_tasks[id] = false
	school_tasks[HOME_ASSIGNMENT_ID] = false
	if has_meta(HOMEWORK_COMPLETE_META):
		remove_meta(HOMEWORK_COMPLETE_META)
	clear_notebook_progress()
	basketball_attempts = 0
	basketball_baskets = 0
	school_tasks_reset.emit()
	school_task_updated.emit()


func get_school_progress_text() -> String:
	return "%d / %d Completed" % [get_school_task_count(), get_school_task_total()]


func set_notebook_progress(progress: Dictionary) -> void:
	notebook_progress = progress.duplicate(true)


func get_notebook_progress() -> Dictionary:
	return notebook_progress.duplicate(true)


func clear_notebook_progress() -> void:
	notebook_progress.clear()


func try_begin_interaction(interaction_node: Node) -> bool:
	if not is_instance_valid(interaction_node) or is_interaction_busy():
		return false
	_interaction_owner = interaction_node
	return true


func end_interaction(interaction_node: Node) -> void:
	if _interaction_owner == interaction_node:
		_interaction_owner = null


func is_interaction_busy() -> bool:
	if not is_instance_valid(_interaction_owner):
		_interaction_owner = null
	elif not _interaction_owner.is_inside_tree():
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


func queue_spawn_marker(destination_scene_path: String, marker_name: String) -> void:
	if destination_scene_path.is_empty() or marker_name.is_empty():
		return
	_pending_spawn_scene = destination_scene_path
	_pending_spawn_marker = marker_name


func take_spawn_marker(current_scene_path: String) -> String:
	if current_scene_path != _pending_spawn_scene:
		return ""
	var marker_name: String = _pending_spawn_marker
	_pending_spawn_scene = ""
	_pending_spawn_marker = ""
	return marker_name


func clear_pending_spawn() -> void:
	_pending_spawn_scene = ""
	_pending_spawn_marker = ""


func clear_positions() -> void:
	saved_positions.clear()
	clear_pending_spawn()
