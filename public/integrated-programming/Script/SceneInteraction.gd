extends "res://Script/school_task.gd"
const HALLWAY_SCENE: String = "res://Scene/school_hallway.tscn"
const HALLWAY2_SCENE: String = "res://Scene/school_hallway2.tscn"
const EXTERIOR_SCENE: String = "res://Scene/school_exterior.tscn"
const GROUNDS_SCENE: String = "res://Scene/school_grounds.tscn"
const TRANSPORT_SCENE: String = "res://Scene/bus_stop.tscn"
@export var arrival_marker_name: String = ""
@export var debug_door: bool = false
var _is_exterior_entrance: bool = false
var _is_grounds_entrance: bool = false
var _is_final_exit: bool = false

func _ready() -> void:
	proximity_fallback = false
	monitoring = true
	show_prompt_through_background = true
	_include_player_layer()
	super._ready()

func _main_player() -> CharacterBody3D:
	var scene: Node = get_tree().current_scene
	if scene != null:
		var main_character: Node = scene.get_node_or_null("Character")
		if main_character is CharacterBody3D:
			return main_character as CharacterBody3D
		if main_character != null:
			for child: Node in main_character.get_children():
				if child is CharacterBody3D:
					return child as CharacterBody3D
	return get_tree().get_first_node_in_group(&"player") as CharacterBody3D

func _include_player_layer() -> void:
	var hero: CharacterBody3D = _main_player()
	if not is_instance_valid(hero):
		return
	var player_layer: int = hero.collision_layer
	if player_layer != 0 and (collision_mask & player_layer) == 0:
		collision_mask |= player_layer

func _is_player(body: Node3D) -> bool:
	if _is_exterior_entrance or _is_grounds_entrance:
		return body is CharacterBody3D and body == _main_player()
	return super._is_player(body)

func _physics_process(delta: float) -> void:
	if _is_exterior_entrance or _is_grounds_entrance:
		_include_player_layer()
	super._physics_process(delta)

func _configure_interaction() -> void:
	var source: String = _source_scene_path()
	var scene_file: String = source.get_file()
	var door: String = String(name).strip_edges().to_lower().replace(" ", "_")
	var label_text: String = ""
	if prompt != null:
		label_text = prompt.text.to_lower()
	var is_exterior: bool = scene_file == "school_exterior.tscn"
	var is_hallway1: bool = scene_file == "school_hallway.tscn"
	var is_hallway2: bool = scene_file == "school_hallway2.tscn"
	var is_grounds: bool = scene_file.begins_with("school_grounds.tscn")
	var exterior_entrance: bool = is_exterior and (
		door in [
			"schoolentrance",
			"school_entrance",
			"enter_school",
			"enter_hallway",
			"enter_hallway2",
			"middle_hallway"
		]
		or "enter school" in label_text
		or "enter hallway" in label_text
	)
	_is_exterior_entrance = exterior_entrance
	if _is_exterior_entrance:
		task_id = ""
		action_only = false
		target_scene_path = HALLWAY_SCENE
		if prompt != null:
			prompt.text = "[F] Enter School"
		_debug_route(source)
		return
	_is_grounds_entrance = is_grounds and (
		door in [
			"enter_grounds",
			"enter_school_grounds",
			"enter_school",
			"schoolentrance",
			"school_entrance",
			"enter_hallway",
			"enter_hallway2"
		]
		or "enter hallway" in label_text
		or "enter school" in label_text
	)
	if _is_grounds_entrance:
		task_id = ""
		action_only = false
		target_scene_path = HALLWAY2_SCENE
		if prompt != null:
			prompt.text = "[F] Enter School"
		_debug_route(source)
		return
	_is_final_exit = is_hallway1 and (
		door in [
			"exit_hallway",
			"exit_school",
			"school_exit"
		]
		or "exit school" in label_text
	)
	if _is_final_exit:
		task_id = ""
		action_only = false
		target_scene_path = TRANSPORT_SCENE
		if prompt != null:
			prompt.text = "[G] Exit School"
		_debug_route(source)
		return
	var hallway2_return: bool = is_hallway2 and (
		door in [
			"exit_hallway",
			"exit_hallway2",
			"return_hallway",
			"back_to_hallway",
			"hallway_1"
		]
		or "return to hallway" in label_text
		or "exit hallway" in label_text
	)
	if hallway2_return:
		task_id = ""
		action_only = false
		target_scene_path = HALLWAY_SCENE
		_debug_route(source)
		return
	if door == "exit_clinic" and "guidance_office" in source:
		if arrival_marker_name.is_empty():
			arrival_marker_name = "GuidanceOfficeSpawn"
	if is_grounds and door in [
		"exit_grounds",
		"exit_school_grounds"
	]:
		task_id = ""
		action_only = false
		target_scene_path = HALLWAY2_SCENE
		_debug_route(source)
		return
	if not target_scene_path.is_empty() or not task_id.is_empty():
		super._configure_interaction()
		_debug_route(source)
		return
	match door:
		"schoolentrance", "school_entrance", "enter_school":
			if is_grounds:
				target_scene_path = HALLWAY2_SCENE
			else:
				target_scene_path = HALLWAY_SCENE
		"middle_hallway", "enter_hallway2", "enter_hallway":
			if is_hallway2:
				target_scene_path = HALLWAY_SCENE
			else:
				target_scene_path = HALLWAY2_SCENE
		"exit_hallway", "exit_hallway2", "return_hallway", "back_to_hallway":
			if is_hallway2:
				target_scene_path = HALLWAY_SCENE
			else:
				target_scene_path = EXTERIOR_SCENE
		"enter_classroom":
			target_scene_path = "res://Scene/classroom.tscn"
		"classroomexit", "exit_classroom", "hallway_1":
			target_scene_path = HALLWAY_SCENE
		"library", "enter_library":
			target_scene_path = "res://Scene/school_library.tscn"
		"clinic", "enter_clinic":
			target_scene_path = "res://Scene/school_clinic.tscn"
		"cafeteria", "enter_cafeteria":
			target_scene_path = "res://Scene/school_cafeteria.tscn"
		"computer_room", "enter_computer_room":
			target_scene_path = "res://Scene/computer_room.tscn"
		"school_grounds", "enter_school_grounds", "enter_grounds", "schoolgrounds":
			if is_grounds:
				target_scene_path = HALLWAY2_SCENE
			else:
				target_scene_path = GROUNDS_SCENE
				if not ResourceLoader.exists(target_scene_path, "PackedScene"):
					var alternate: String = "res://Scene/school_grounds.tscn.tscn"
					if ResourceLoader.exists(alternate, "PackedScene"):
						target_scene_path = alternate
		"exit_library", "exit_clinic", "exit_cafeteria", "exit_computer_ro", "exit_computer_room", "exit_grounds", "exit_school_grounds":
			target_scene_path = HALLWAY2_SCENE
	if target_scene_path.is_empty():
		super._configure_interaction()
	_debug_route(source)

func _source_scene_path() -> String:
	var scene: Node = get_tree().current_scene
	if scene != null and not scene.scene_file_path.is_empty():
		return scene.scene_file_path.to_lower()
	if owner != null and not owner.scene_file_path.is_empty():
		return owner.scene_file_path.to_lower()
	var parent_node: Node = get_parent()
	while parent_node != null:
		if not parent_node.scene_file_path.is_empty():
			return parent_node.scene_file_path.to_lower()
		parent_node = parent_node.get_parent()
	return ""

func _can_interact() -> bool:
	if _is_final_exit:
		var state: Node = get_node_or_null("/root/GameState")
		if state == null:
			return false
		if not state.has_method("school_tasks_complete"):
			return false
		if not bool(state.call("school_tasks_complete")):
			return false
	if target_scene_path.get_file().to_lower() == "guidance_office.tscn":
		var state: Node = get_node_or_null("/root/GameState")
		if state == null:
			return false
		if not bool(state.get_meta("equalpath_guidance_access_granted", false)):
			return false
	return super._can_interact()

func _input(event: InputEvent) -> void:
	if not _is_exterior_entrance and not _is_grounds_entrance:
		return
	if not (event is InputEventKey):
		return
	var key_event: InputEventKey = event as InputEventKey
	if not key_event.pressed or key_event.echo:
		return
	if key_event.keycode != KEY_F and key_event.physical_keycode != KEY_F:
		return
	if not _can_interact():
		return
	get_viewport().set_input_as_handled()
	_activate_interaction()

func _change_scene() -> void:
	if _is_exterior_entrance:
		target_scene_path = HALLWAY_SCENE
	elif _is_grounds_entrance:
		target_scene_path = HALLWAY2_SCENE
	elif _is_final_exit:
		target_scene_path = TRANSPORT_SCENE
	if not arrival_marker_name.is_empty() and not target_scene_path.is_empty():
		var state: Node = get_node_or_null("/root/GameState")
		if state != null and state.has_method("queue_spawn_marker"):
			state.call(
				"queue_spawn_marker",
				target_scene_path,
				arrival_marker_name
			)
	_debug_route(_source_scene_path())
	super._change_scene()

func _debug_route(source: String) -> void:
	if debug_door:
		print(
			"DOOR: ",
			source,
			" / ",
			name,
			" -> ",
			target_scene_path
		)
