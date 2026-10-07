extends Area3D

@onready var prompt: Label3D = $FPrompt

var player_inside: bool = false
var changing_scene: bool = false
var current_player: CharacterBody3D = null

var target_scene_path: String = ""
var interaction_key: Key = KEY_F


func _ready() -> void:
	prompt.hide()

	configure_interaction()

	body_entered.connect(_on_body_entered)
	body_exited.connect(_on_body_exited)

	print("AREA: ", name)
	print("TARGET: ", target_scene_path)


func configure_interaction() -> void:
	var current_scene := get_tree().current_scene

	if current_scene == null:
		return

	var current_path: String = current_scene.scene_file_path.to_lower()


	if current_path.contains("school_grounds"):
		interaction_key = KEY_F
		target_scene_path = "res://Scene/school_hallway2.tscn"
		return


	if current_path.contains("computer_room"):
		if name == "Exit_computer_ro" or name == "Exit_computer_room":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway2.tscn"
			return


	match name:

		"SchoolEntrance":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway.tscn"

		"Enter_Classroom":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/classroom.tscn"

		"Exit_Hallway":
			interaction_key = KEY_G
			target_scene_path = "res://Scene/school_exterior.tscn"

		"Middle_hallway":
			interaction_key = KEY_R
			target_scene_path = "res://Scene/school_hallway2.tscn"

		"ClassroomExit":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway.tscn"

		"Library":
			interaction_key = KEY_H
			target_scene_path = "res://Scene/school_library.tscn"

		"Clinic":
			interaction_key = KEY_G
			target_scene_path = "res://Scene/school_clinic.tscn"

		"Cafeteria":
			interaction_key = KEY_C
			target_scene_path = "res://Scene/school_cafeteria.tscn"

		"Hallway_1":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway.tscn"

		"Computer_room":
			interaction_key = KEY_R
			target_scene_path = "res://Scene/computer_room.tscn"

		"School_grounds":
			interaction_key = KEY_T

			if ResourceLoader.exists("res://Scene/school_grounds.tscn"):
				target_scene_path = "res://Scene/school_grounds.tscn"

			elif ResourceLoader.exists("res://Scene/school_grounds.tscn.tscn"):
				target_scene_path = "res://Scene/school_grounds.tscn.tscn"

		"Exit_library":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway2.tscn"

		"Exit_clinic":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway2.tscn"

		"Exit_cafeteria":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway2.tscn"

		"Exit_computer_ro":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway2.tscn"

		"Exit_computer_room":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway2.tscn"

		"Enter_Hallway2":
			interaction_key = KEY_F
			target_scene_path = "res://Scene/school_hallway2.tscn"

		_:
			print("NO CONNECTION FOR: ", name)


func _input(event: InputEvent) -> void:
	if changing_scene:
		return

	if !player_inside:
		return

	if target_scene_path.is_empty():
		return

	if event is InputEventKey:
		if event.pressed and !event.echo:
			if event.keycode == interaction_key:

				print("KEY PRESSED")
				print("AREA: ", name)
				print("TARGET: ", target_scene_path)

				_save_current_position()

				changing_scene = true
				player_inside = false

				if is_instance_valid(prompt):
					prompt.hide()

				call_deferred("_change_scene")


func _on_body_entered(body: Node3D) -> void:
	if changing_scene:
		return

	if body.is_in_group("player"):
		player_inside = true

		if body is CharacterBody3D:
			current_player = body

		if is_instance_valid(prompt):
			prompt.show()

		print("PLAYER ENTERED: ", name)


func _on_body_exited(body: Node3D) -> void:
	if body.is_in_group("player"):
		player_inside = false

		if body == current_player:
			current_player = null

		if is_instance_valid(prompt):
			prompt.hide()


func _save_current_position() -> void:
	if current_player == null:
		return

	var current_scene := get_tree().current_scene

	if current_scene == null:
		return

	var scene_path: String = current_scene.scene_file_path

	if scene_path.is_empty():
		return

	var game_state := get_tree().root.get_node_or_null("GameState")

	if game_state == null:
		print("GameState not found")
		return

	if game_state.has_method("save_position"):
		game_state.call(
			"save_position",
			scene_path,
			current_player.global_position
		)


func _change_scene() -> void:
	if target_scene_path.is_empty():
		push_error("NO TARGET SCENE FOR: " + name)
		changing_scene = false
		return

	if !ResourceLoader.exists(target_scene_path):
		push_error(
			"SCENE DOES NOT EXIST: " +
			target_scene_path
		)

		changing_scene = false
		return

	print("============================")
	print("CHANGING SCENE")
	print("FROM: ", get_tree().current_scene.scene_file_path)
	print("TO: ", target_scene_path)
	print("============================")

	var error: Error = get_tree().change_scene_to_file(
		target_scene_path
	)

	if error != OK:
		push_error(
			"SCENE CHANGE ERROR: " +
			str(error)
		)

		changing_scene = false
