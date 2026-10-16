extends "res://Script/school_task.gd"

@export var arrival_marker_name: String = ""


func _configure_interaction() -> void:
	var current_path: String = _source_scene_path()
	var door: String = String(name).to_lower().replace(" ", "_")

	if door == "exit_clinic" and "guidance_office" in current_path and arrival_marker_name.is_empty():
		arrival_marker_name = "GuidanceOfficeSpawn"

	if not target_scene_path.is_empty() or not task_id.is_empty():
		super._configure_interaction()
		return

	match door:
		"schoolentrance", "school_entrance", "enter_school":
			target_scene_path = "res://Scene/school_hallway2.tscn" if "school_grounds" in current_path else "res://Scene/school_hallway.tscn"
		"enter_classroom":
			target_scene_path = "res://Scene/classroom.tscn"
		"classroomexit", "exit_classroom", "hallway_1":
			target_scene_path = "res://Scene/school_hallway.tscn"
		"exit_hallway":
			target_scene_path = "res://Scene/school_exterior.tscn"
		"middle_hallway", "enter_hallway2", "enter_hallway":
			target_scene_path = "res://Scene/school_hallway2.tscn"
		"library", "enter_library":
			target_scene_path = "res://Scene/school_library.tscn"
		"clinic", "enter_clinic":
			target_scene_path = "res://Scene/school_clinic.tscn"
		"cafeteria", "enter_cafeteria":
			target_scene_path = "res://Scene/school_cafeteria.tscn"
		"computer_room", "enter_computer_room":
			target_scene_path = "res://Scene/computer_room.tscn"
		"school_grounds", "enter_school_grounds", "enter_grounds", "schoolgrounds":
			target_scene_path = "res://Scene/school_grounds.tscn"
			if not ResourceLoader.exists(target_scene_path, "PackedScene"):
				var alternate: String = "res://Scene/school_grounds.tscn.tscn"
				if ResourceLoader.exists(alternate, "PackedScene"):
					target_scene_path = alternate
		"exit_library", "exit_clinic", "exit_cafeteria", "exit_computer_ro", "exit_computer_room", "exit_grounds", "exit_school_grounds":
			target_scene_path = "res://Scene/school_hallway2.tscn"

	if target_scene_path.is_empty():
		super._configure_interaction()


func _source_scene_path() -> String:
	if owner != null and not owner.scene_file_path.is_empty():
		return owner.scene_file_path.to_lower()
	var current_scene: Node = get_tree().current_scene
	if current_scene != null:
		return current_scene.scene_file_path.to_lower()
	return ""


func _change_scene() -> void:
	if not arrival_marker_name.is_empty() and not target_scene_path.is_empty():
		var state: Node = get_node_or_null("/root/GameState")
		if state != null and state.has_method("queue_spawn_marker"):
			state.call("queue_spawn_marker", target_scene_path, arrival_marker_name)
	super._change_scene()
