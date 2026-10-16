extends "res://Script/school_task.gd"

const BASKETBALL_SCENE: String = "res://Scene/basketball_minigame.tscn"


func _configure_interaction() -> void:
	task_id = ""
	target_scene_path = BASKETBALL_SCENE
	action_only = false
	if prompt != null:
		prompt.text = "[F] Play"
