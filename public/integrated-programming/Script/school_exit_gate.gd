extends "res://Script/school_task.gd"
## Attach ONLY to the '[G] Exit School' Area3D in school_hallway.tscn.
## Before 5/5 tasks, hide the prompt and do not permit travel to the station.

@export_file("*.tscn") var station_scene_path: String = "res://Scene/bus_stop.tscn"


func _configure_interaction() -> void:
	task_id = ""
	action_only = false
	target_scene_path = station_scene_path
	if prompt != null:
		prompt.text = "[G] Exit School"


func _can_interact() -> bool:
	if game_state == null or not game_state.has_method("school_tasks_complete"):
		return false
	if not bool(game_state.call("school_tasks_complete")):
		return false
	return super._can_interact()


func _activate_interaction() -> void:
	if not _can_interact():
		return
	if not ResourceLoader.exists(target_scene_path, "PackedScene"):
		push_error("School exit: Station scene not found: " + target_scene_path + ". Select your station .tscn in the Inspector.")
		return
	super._activate_interaction()
