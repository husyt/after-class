extends "res://Script/school_task.gd"

@export_enum("Exterior to Hallway", "Hallway to Hallway 2", "Hallway 2 to Hallway") var route: int = 0
@export var arrival_marker_name: String = ""


func _configure_interaction() -> void:
	task_id = ""
	action_only = false
	match route:
		0:
			target_scene_path = "res://Scene/school_hallway.tscn"
			if prompt != null:
				prompt.text = "[F] Enter Hallway"
		1:
			target_scene_path = "res://Scene/school_hallway2.tscn"
			if prompt != null:
				prompt.text = "[F] Enter Hallway 2"
		2:
			target_scene_path = "res://Scene/school_hallway.tscn"
			if prompt != null:
				prompt.text = "[F] Return to Hallway"


func _change_scene() -> void:
	if not arrival_marker_name.is_empty() and not target_scene_path.is_empty():
		var state: Node = get_node_or_null("/root/GameState")
		if state != null and state.has_method("queue_spawn_marker"):
			state.call("queue_spawn_marker", target_scene_path, arrival_marker_name)
	super._change_scene()
