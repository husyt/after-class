
extends "res://Script/SceneInteraction.gd"

# AFTER CLASS - HOUSE AND BUS STOP
# Godot 4.x

signal waiting_for_transport

@export_group("House Destination")
@export_file("*.tscn") var bedroom_scene_path: String = ""
@export var debug_house: bool = true

@export_group("Bus Stop")
@export_enum("Jeep", "Tricycle") var selected_transport: String = "Jeep"

var _waiting_for_transport: bool = false
var _waiting_player: CharacterBody3D = null
var _resolved_bedroom_path: String = ""


# ============================================
# IDENTIFY INTERACTIONS
# ============================================

func _is_neighborhood_house() -> bool:
	return (
		_source_scene_path().get_file().to_lower() == "neighborhood_street.tscn"
		and String(name).strip_edges().to_lower() == "house"
	)


func _is_bus_wait_area() -> bool:
	return (
		_source_scene_path().get_file().to_lower() == "bus_stop.tscn"
		and String(name).strip_edges().to_lower() == "bus"
	)


# ============================================
# CONFIGURE INTERACTION
# ============================================

func _configure_interaction() -> void:

	if _is_neighborhood_house():
		task_id = ""
		action_only = false
		action_used = false
		proximity_fallback = false
		arrival_marker_name = ""

		_resolved_bedroom_path = _resolve_bedroom_scene()

		target_scene_path = (
			_resolved_bedroom_path
			if not _resolved_bedroom_path.is_empty()
			else bedroom_scene_path.strip_edges()
		)

		if prompt != null:
			prompt.text = "[F] Enter House"
			prompt.no_depth_test = true

		if debug_house:
			print(
				"HOUSE DESTINATION: ",
				target_scene_path
				if not target_scene_path.is_empty()
				else "NOT FOUND"
			)

		return

	if _is_bus_wait_area():
		task_id = ""
		target_scene_path = ""
		action_only = true
		action_used = false
		proximity_fallback = false

		if prompt != null:
			prompt.text = "[F] Wait"
			prompt.no_depth_test = true

		return

	super._configure_interaction()


# ============================================
# AUTOMATICALLY FIND BEDROOM SCENE
# ============================================

func _resolve_bedroom_scene() -> String:

	var selected: String = bedroom_scene_path.strip_edges()

	if _is_valid_scene(selected):
		return selected

	var usual_paths: Array[String] = [
		"res://Scene/student_bedroom.tscn",
		"res://Scene/student_bedroom.tscn.tscn",
		"res://student_bedroom.tscn",
		"res://Scene/Student_Bedroom.tscn"
	]

	for candidate: String in usual_paths:
		if _is_valid_scene(candidate):
			return candidate

	# Search folders for student_bedroom.
	for root: String in ["res://Scene", "res://"]:
		var found: String = _scan_bedroom_scenes(
			root, 0, true
		)

		if not found.is_empty():
			return found

	# Also accept other bedroom scene names.
	for root: String in ["res://Scene", "res://"]:
		var found: String = _scan_bedroom_scenes(
			root, 0, false
		)

		if not found.is_empty():
			return found

	return ""


# ============================================
# VALIDATE DESTINATION
# ============================================

func _is_valid_scene(scene_path: String) -> bool:
	return (
		not scene_path.is_empty()
		and ResourceLoader.exists(scene_path, "PackedScene")
	)


# ============================================
# SEARCH SCENE FOLDERS
# ============================================

func _scan_bedroom_scenes(
	directory: String,
	depth: int,
	exact_name: bool
) -> String:

	if depth > 6:
		return ""

	if not DirAccess.dir_exists_absolute(directory):
		return ""

	for raw_file in DirAccess.get_files_at(directory):

		var filename: String = String(raw_file)
		var lower_name: String = filename.to_lower()

		if not lower_name.ends_with(".tscn"):
			continue

		var name_matches: bool = (
			"student_bedroom" in lower_name
			or "student-bedroom" in lower_name
		)

		if not exact_name:
			name_matches = (
				name_matches
				or "bedroom" in lower_name
			)

		if name_matches:
			var full_path: String = directory.path_join(
				filename
			)

			if _is_valid_scene(full_path):
				return full_path

	for raw_subdir in DirAccess.get_directories_at(directory):

		var subdir: String = String(raw_subdir)

		if subdir.begins_with(".") or subdir == "godot_game":
			continue

		var found: String = _scan_bedroom_scenes(
			directory.path_join(subdir),
			depth + 1,
			exact_name
		)

		if not found.is_empty():
			return found

	return ""


# ============================================
# COLLISION-BASED INTERACTION
# ============================================

func _can_interact() -> bool:

	if _is_bus_wait_area() and _waiting_for_transport:
		return false

	return super._can_interact()


# ============================================
# PRESS F
# ============================================

func _activate_interaction() -> void:

	# ENTER STUDENT BEDROOM
	if _is_neighborhood_house():

		if not _can_interact():
			return

		_resolved_bedroom_path = _resolve_bedroom_scene()

		if _resolved_bedroom_path.is_empty():
			push_error(
				"Bedroom scene not found. In the House Inspector, "
				+ "assign House Destination > Bedroom Scene Path "
				+ "using the file picker or Copy Path."
			)
			return

		target_scene_path = _resolved_bedroom_path

		print("ENTER HOUSE -> ", target_scene_path)

		super._activate_interaction()
		return

	# OTHER SCHOOL INTERACTIONS
	if not _is_bus_wait_area():
		super._activate_interaction()
		return

	# BUS STOP F WAIT
	if not _can_interact():
		return

	if not is_instance_valid(current_player):
		return

	_waiting_player = current_player
	_waiting_for_transport = true
	action_used = true

	_waiting_player.velocity = Vector3.ZERO
	_waiting_player.set_physics_process(false)

	var visual: AnimatedSprite3D = _waiting_player.get_node_or_null(
		"AnimatedSprite3D"
	) as AnimatedSprite3D

	if visual != null and visual.sprite_frames != null:

		for animation: StringName in visual.sprite_frames.get_animation_names():

			if String(animation).to_lower() == "idle":
				visual.play(animation)
				break

	_update_visibility()
	waiting_for_transport.emit()

	print("PLAYER WAITING FOR ", selected_transport)


# ============================================
# CHANGE SCENE
# ============================================

func _change_scene() -> void:

	if _is_neighborhood_house():

		target_scene_path = _resolve_bedroom_scene()

		if not _is_valid_scene(target_scene_path):
			push_error(
				"Cannot enter Student Bedroom: "
				+ "select its real .tscn path in House Inspector."
			)
			return

	super._change_scene()


# ============================================
# BUS WAITING METHODS
# ============================================

func is_waiting_for_transport() -> bool:
	return (
		_waiting_for_transport
		and is_instance_valid(_waiting_player)
	)


func get_waiting_player() -> CharacterBody3D:
	return _waiting_player if is_waiting_for_transport() else null


func get_selected_transport() -> String:
	return selected_transport
