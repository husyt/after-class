extends "res://Script/school_task.gd"
## Full door / scene-interaction implementation built on school_task.gd.
## Attach to Area3D doors with FPrompt (Label3D) and CollisionShape3D.
## Set Target Scene Path in the Inspector for any door not in the map below.
## This script inherits prompt hover, click/F/G/H support, saved positions,
## and SceneTransition's 2-second fade from school_task.gd.


func _configure_interaction() -> void:
	# Explicit destination/task inspector settings always win.
	if not target_scene_path.is_empty() or not task_id.is_empty():
		super._configure_interaction()
		return

	var scene: Node = get_tree().current_scene
	var current_path: String = scene.scene_file_path.to_lower() if scene != null else ""
	var door: String = String(name).to_lower().replace(" ", "_")

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
		"school_grounds", "enter_school_grounds":
			target_scene_path = "res://Scene/school_grounds.tscn"
			if not ResourceLoader.exists(target_scene_path, "PackedScene"):
				var alternate: String = "res://Scene/school_grounds.tscn.tscn"
				if ResourceLoader.exists(alternate, "PackedScene"):
					target_scene_path = alternate
		"exit_library", "exit_clinic", "exit_cafeteria", "exit_computer_ro", "exit_computer_room":
			target_scene_path = "res://Scene/school_hallway2.tscn"

	# If unrecognized, try task/action text, rather than silently forcing
	# the player into a hallway.
	if target_scene_path.is_empty():
		super._configure_interaction()
