extends "res://Script/SceneInteraction.gd"

signal waiting_for_transport

@export_group("House Destination")
@export_file("*.tscn") var bedroom_scene_path: String = ""
@export var debug_house: bool = true

@export_group("Bedroom Ending")
@export_range(0.3, 4.0, 0.1) var ending_fade_seconds: float = 1.2
@export_file("*.tscn") var play_again_scene: String = "res://Scene/school_exterior.tscn"
@export var require_all_tasks_to_sleep: bool = false

@export_group("Existing Ending Video")
@export_file("*.tscn") var ending_video_scene_path: String = ""
@export_range(0.2, 3.0, 0.1) var video_fade_seconds: float = 0.9
@export_range(10.0, 600.0, 1.0) var video_max_playback_seconds: float = 300.0

@export_group("Bus Stop")
@export_enum("Jeep", "Tricycle") var selected_transport: String = "Jeep"

var _waiting_for_transport: bool = false
var _waiting_player: CharacterBody3D = null
var _resolved_bedroom_path: String = ""
var _ending_started: bool = false
var _ending_layer: CanvasLayer = null
var _paused_huds: Array[Node] = []
var _ending_video_finished: bool = false


func _is_neighborhood_house() -> bool:
	return (
		_source_scene_path().get_file().to_lower() == "neighborhood_street.tscn"
		and String(name).strip_edges().to_lower() == "house"
	)


func _is_bedroom_sleep() -> bool:
	var scene_path: String = _source_scene_path().to_lower()
	var interaction_name: String = String(name).strip_edges().to_lower()
	var prompt_text: String = prompt.text.to_lower() if prompt != null else ""
	return (
		"bedroom" in scene_path
		and ("sleep" in interaction_name or "sleep" in prompt_text)
	)


func _is_bus_wait_area() -> bool:
	return (
		_source_scene_path().get_file().to_lower() == "bus_stop.tscn"
		and String(name).strip_edges().to_lower() == "bus"
	)


func _configure_interaction() -> void:
	if _is_bedroom_sleep():
		task_id = ""
		target_scene_path = ""
		action_only = true
		action_used = false
		proximity_fallback = false
		if prompt != null:
			prompt.text = "[F] Sleep"
			prompt.no_depth_test = true
		return

	if _is_neighborhood_house():
		task_id = ""
		action_only = false
		action_used = false
		proximity_fallback = false
		arrival_marker_name = ""
		_resolved_bedroom_path = _resolve_bedroom_scene()
		target_scene_path = (
			_resolved_bedroom_path if not _resolved_bedroom_path.is_empty()
			else bedroom_scene_path.strip_edges()
		)
		if prompt != null:
			prompt.text = "[F] Enter House"
			prompt.no_depth_test = true
		if debug_house:
			print("HOUSE DESTINATION: ", target_scene_path if not target_scene_path.is_empty() else "NOT FOUND")
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

	for root: String in ["res://Scene", "res://"]:
		var found: String = _scan_bedroom_scenes(root, 0, true)
		if not found.is_empty():
			return found

	for root: String in ["res://Scene", "res://"]:
		var found: String = _scan_bedroom_scenes(root, 0, false)
		if not found.is_empty():
			return found

	return ""


func _is_valid_scene(scene_path: String) -> bool:
	return (
		not scene_path.is_empty()
		and ResourceLoader.exists(scene_path, "PackedScene")
	)


func _scan_bedroom_scenes(directory: String, depth: int, exact_name: bool) -> String:
	if depth > 6 or not DirAccess.dir_exists_absolute(directory):
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
			name_matches = name_matches or "bedroom" in lower_name

		if name_matches:
			var full_path: String = directory.path_join(filename)
			if _is_valid_scene(full_path):
				return full_path

	for raw_subdir in DirAccess.get_directories_at(directory):
		var subdir: String = String(raw_subdir)
		if subdir.begins_with(".") or subdir == "godot_game":
			continue
		var found: String = _scan_bedroom_scenes(
			directory.path_join(subdir), depth + 1, exact_name
		)
		if not found.is_empty():
			return found

	return ""


func _can_interact() -> bool:
	if _is_bedroom_sleep():
		if _ending_started:
			return false
		if require_all_tasks_to_sleep and game_state != null:
			if game_state.has_method("school_tasks_complete"):
				if not bool(game_state.call("school_tasks_complete")):
					return false
	if _is_bus_wait_area() and _waiting_for_transport:
		return false
	return super._can_interact()


func _activate_interaction() -> void:
	if _is_bedroom_sleep():
		if not _can_interact():
			return
		if game_state == null:
			push_error("Sleep ending needs the GameState Autoload.")
			return
		if not bool(game_state.call("try_begin_interaction", self)):
			return
		_ending_started = true
		action_used = true
		busy = true
		_update_visibility()

		var student: CharacterBody3D = current_player
		if not is_instance_valid(student):
			student = get_tree().get_first_node_in_group(&"player") as CharacterBody3D
		if is_instance_valid(student):
			student.velocity = Vector3.ZERO
			student.set_physics_process(false)
			var animated: AnimatedSprite3D = student.get_node_or_null("AnimatedSprite3D") as AnimatedSprite3D
			if animated != null and animated.sprite_frames != null:
				for anim: StringName in animated.sprite_frames.get_animation_names():
					if String(anim).to_lower() == "idle":
						animated.play(anim)
						break

		_suspend_school_hud()
		_build_ending_overlay()
		_run_ending_fade.call_deferred()
		return

	if _is_neighborhood_house():
		if not _can_interact():
			return
		_resolved_bedroom_path = _resolve_bedroom_scene()
		if _resolved_bedroom_path.is_empty():
			push_error(
				"Bedroom scene not found. In the House Inspector, "
				+ "assign House Destination > Bedroom Scene Path using the file picker "
				+ "or right-click your scene and choose Copy Path."
			)
			return
		target_scene_path = _resolved_bedroom_path
		print("ENTER HOUSE -> ", target_scene_path)
		super._activate_interaction()
		return

	if not _is_bus_wait_area():
		super._activate_interaction()
		return

	if not _can_interact() or not is_instance_valid(current_player):
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


func _change_scene() -> void:
	if _is_neighborhood_house():
		target_scene_path = _resolve_bedroom_scene()
		if not _is_valid_scene(target_scene_path):
			push_error("Cannot enter Student Bedroom: select its real .tscn path in House Inspector.")
			return
	super._change_scene()


func is_waiting_for_transport() -> bool:
	return _waiting_for_transport and is_instance_valid(_waiting_player)


func get_waiting_player() -> CharacterBody3D:
	return _waiting_player if is_waiting_for_transport() else null


func get_selected_transport() -> String:
	return selected_transport



func _suspend_school_hud() -> void:
	_paused_huds.clear()
	var huds: Array[Node] = get_tree().get_nodes_in_group(&"school_hud")
	var main_hud: Node = get_node_or_null("/root/SchoolHUD")
	if main_hud != null and not huds.has(main_hud):
		huds.append(main_hud)
	for hud: Node in huds:
		if not is_instance_valid(hud):
			continue
		_paused_huds.append(hud)
		hud.set_process(false)
		if hud is CanvasLayer:
			(hud as CanvasLayer).visible = false
		elif hud is CanvasItem:
			(hud as CanvasItem).hide()


func _build_ending_overlay() -> void:
	var room_scene: Node = get_tree().current_scene
	if room_scene == null:
		return

	_ending_layer = CanvasLayer.new()
	_ending_layer.name = "AfterClassEnding"
	_ending_layer.layer = 120
	_ending_layer.process_mode = Node.PROCESS_MODE_ALWAYS
	room_scene.add_child(_ending_layer)

	var black: ColorRect = ColorRect.new()
	black.name = "BlackFade"
	black.color = Color.BLACK
	black.modulate.a = 0.0
	black.mouse_filter = Control.MOUSE_FILTER_STOP
	_ending_layer.add_child(black)
	black.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var end_screen: Control = _make_builtin_end_screen()

	end_screen.name = "EndingScreen"
	_ending_layer.add_child(end_screen)
	end_screen.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	end_screen.visible = false
	end_screen.modulate.a = 0.0

	var replay_button: Button = end_screen.find_child("PlayAgainButton", true, false) as Button
	if replay_button == null:
		push_error("Ending screen is missing a button named PlayAgainButton.")
	else:
		replay_button.pressed.connect(_on_play_again_pressed)



func _make_builtin_end_screen() -> Control:
	var screen: Control = Control.new()
	screen.name = "EndingScreen"
	screen.mouse_filter = Control.MOUSE_FILTER_STOP

	var center: CenterContainer = CenterContainer.new()
	center.name = "Center"
	screen.add_child(center)
	center.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var content: VBoxContainer = VBoxContainer.new()
	content.name = "Content"
	content.custom_minimum_size = Vector2(500.0, 0.0)
	content.add_theme_constant_override("separation", 19)
	center.add_child(content)

	var eyebrow: Label = _make_end_label("AFTER CLASS", 23, Color(0.85, 0.68, 0.42))
	content.add_child(eyebrow)

	var title: Label = _make_end_label("THE END", 60, Color(1.0, 1.0, 1.0))
	content.add_child(title)

	var message: Label = _make_end_label("Another day, another chance to learn.", 19, Color(0.77, 0.79, 0.86))
	content.add_child(message)

	var count: int = 0
	var total: int = 5
	if game_state != null:
		if game_state.has_method("get_school_task_count"):
			count = int(game_state.call("get_school_task_count"))
		if game_state.has_method("get_school_task_total"):
			total = int(game_state.call("get_school_task_total"))

	var seconds_played: float = -1.0
	if game_state != null and game_state.has_meta("equalpath_school_completion_seconds"):
		seconds_played = float(game_state.get_meta("equalpath_school_completion_seconds"))
	else:
		var hud: Node = get_node_or_null("/root/SchoolHUD")
		if hud != null:
			var hud_seconds: Variant = hud.get("elapsed_seconds")
			if hud_seconds is float or hud_seconds is int:
				seconds_played = float(hud_seconds)

	var stats: VBoxContainer = VBoxContainer.new()
	stats.add_theme_constant_override("separation", 10)
	content.add_child(stats)
	stats.add_child(_make_end_label("School tasks completed: %d / %d" % [count, total], 18, Color.WHITE))
	stats.add_child(_make_end_label("Time: %s" % (_end_time_text(seconds_played) if seconds_played >= 0.0 else "--:--"), 18, Color.WHITE))

	content.add_child(_make_end_label("Thank you for playing!", 17, Color(0.68, 0.70, 0.77)))

	var replay_row: HBoxContainer = HBoxContainer.new()
	replay_row.alignment = BoxContainer.ALIGNMENT_CENTER
	content.add_child(replay_row)

	var replay_button: Button = Button.new()
	replay_button.name = "PlayAgainButton"
	replay_button.text = "PLAY AGAIN"
	replay_button.custom_minimum_size = Vector2(290.0, 52.0)
	replay_button.add_theme_font_size_override("font_size", 21)
	replay_row.add_child(replay_button)

	return screen

func _make_end_label(message: String, size: int, tint: Color) -> Label:
	var result: Label = Label.new()
	result.text = message
	result.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	result.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	result.add_theme_font_size_override("font_size", size)
	result.add_theme_color_override("font_color", tint)
	return result


func _end_time_text(seconds_played: float) -> String:
	var whole: int = maxi(0, int(floor(seconds_played)))
	return "%02d:%02d" % [int(float(whole) / 60.0), whole % 60]


func _run_ending_fade() -> void:
	if not is_instance_valid(_ending_layer):
		return

	var black: ColorRect = _ending_layer.get_node_or_null("BlackFade") as ColorRect
	var end_screen: Control = _ending_layer.get_node_or_null("EndingScreen") as Control
	if black == null or end_screen == null:
		push_error("Ending overlay was not created.")
		return

	black.modulate.a = 0.0
	end_screen.visible = false
	end_screen.modulate.a = 0.0

	await _tween_alpha(black, 1.0, ending_fade_seconds)
	if not is_inside_tree():
		return

	var hero: CharacterBody3D = get_tree().get_first_node_in_group(&"player") as CharacterBody3D
	if is_instance_valid(hero):
		hero.hide()

	var video_played: bool = await _show_existing_ending_video()
	if not is_inside_tree():
		return

	if not video_played:
		push_error("Ending video did not play. Check Ending Video Scene Path and VideoStreamPlayer Stream. THE END is hidden until the video plays.")
		return

	await get_tree().create_timer(0.25, true).timeout
	if not is_inside_tree():
		return

	end_screen.visible = true
	await _tween_alpha(end_screen, 1.0, 1.1)
	if not is_inside_tree():
		return
	var replay_button: Button = end_screen.find_child("PlayAgainButton", true, false) as Button
	if replay_button != null:
		replay_button.grab_focus()


func _resolve_ending_video_scene() -> String:
	var selected: String = ending_video_scene_path.strip_edges()
	if _is_valid_scene(selected):
		return selected

	for candidate: String in [
		"res://Video/Ending_video.tscn",
		"res://Scene/Ending_video.tscn",
		"res://Ending_video.tscn",
		"res://Video/After_Class/Ending_video.tscn"
	]:
		if _is_valid_scene(candidate):
			return candidate

	for root: String in ["res://Video", "res://Scene", "res://"]:
		var found: String = _find_ending_video_scene(root, 0)
		if not found.is_empty():
			return found
	return ""


func _find_ending_video_scene(directory: String, depth: int) -> String:
	if depth > 7 or not DirAccess.dir_exists_absolute(directory):
		return ""
	for item: String in DirAccess.get_files_at(directory):
		var lower: String = item.to_lower()
		if lower.ends_with(".tscn") and ("ending_video" in lower or "ending-video" in lower):
			var path: String = directory.path_join(item)
			if _is_valid_scene(path):
				return path
	for item: String in DirAccess.get_directories_at(directory):
		if item.begins_with(".") or item == "godot_game":
			continue
		var found: String = _find_ending_video_scene(directory.path_join(item), depth + 1)
		if not found.is_empty():
			return found
	return ""


func _show_existing_ending_video() -> bool:
	var scene_path: String = _resolve_ending_video_scene()
	if scene_path.is_empty() or not ResourceLoader.exists(scene_path, "PackedScene"):
		push_warning("Ending video scene not found: " + scene_path)
		return false

	var packed: PackedScene = load(scene_path) as PackedScene
	if packed == null:
		return false

	var movie_scene: Node = packed.instantiate()
	var video_player: VideoStreamPlayer = movie_scene.find_child("VideoStreamPlayer", true, false) as VideoStreamPlayer
	if video_player == null:
		push_warning("Ending_video.tscn needs a VideoStreamPlayer child.")
		movie_scene.free()
		return false

	if video_player.stream == null:
		push_warning("Assign an .ogv file to Ending_video.tscn > VideoStreamPlayer > Stream.")
		movie_scene.free()
		return false

	video_player.autoplay = false
	video_player.loop = false
	video_player.paused = false
	if is_zero_approx(video_player.speed_scale):
		video_player.speed_scale = 1.0
	_ending_video_finished = false
	video_player.finished.connect(_on_ending_video_finished, CONNECT_ONE_SHOT)

	var movie_host: Control = Control.new()
	movie_host.name = "EndingVideoHost"
	movie_host.mouse_filter = Control.MOUSE_FILTER_STOP
	movie_host.clip_contents = true
	_ending_layer.add_child(movie_host)
	movie_host.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var original_video_parent: Node = video_player.get_parent()
	video_player.owner = null
	original_video_parent.remove_child(video_player)
	movie_host.add_child(video_player)
	movie_scene.free()
	video_player.visible = true

	var cover: ColorRect = ColorRect.new()
	cover.name = "VideoFade"
	cover.color = Color.BLACK
	cover.modulate.a = 1.0
	cover.mouse_filter = Control.MOUSE_FILTER_STOP
	movie_host.add_child(cover)
	cover.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	await get_tree().process_frame
	if not is_inside_tree() or not is_instance_valid(video_player):
		if is_instance_valid(movie_host):
			movie_host.queue_free()
		return false

	video_player.play()
	var video_started_at: int = Time.get_ticks_msec()
	await _tween_alpha(cover, 0.0, video_fade_seconds)

	while is_inside_tree() and is_instance_valid(video_player):
		if _ending_video_finished:
			break
		var elapsed: float = float(Time.get_ticks_msec() - video_started_at) / 1000.0
		if elapsed > 0.25 and not video_player.is_playing():
			break
		if elapsed >= video_max_playback_seconds:
			push_warning("Ending video exceeded Max Playback Seconds. Proceeding to THE END.")
			break
		await get_tree().process_frame

	if not is_inside_tree() or not is_instance_valid(movie_host):
		return false

	await _tween_alpha(cover, 1.0, video_fade_seconds)
	if is_instance_valid(video_player):
		video_player.stop()
	movie_host.queue_free()
	await get_tree().process_frame
	return true


func _on_ending_video_finished() -> void:
	_ending_video_finished = true

func _tween_alpha(target: CanvasItem, alpha: float, duration: float) -> void:
	if not is_instance_valid(target):
		return
	var tween: Tween = create_tween()
	tween.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
	tween.tween_property(target, "modulate:a", alpha, maxf(duration, 0.05))
	await tween.finished


func _on_play_again_pressed() -> void:
	if not ResourceLoader.exists(play_again_scene, "PackedScene"):
		push_error("Play Again scene is missing: " + play_again_scene)
		return

	if game_state != null:
		if game_state.has_method("clear_positions"):
			game_state.call("clear_positions")
		if game_state.has_meta("equalpath_school_completion_seconds"):
			game_state.remove_meta("equalpath_school_completion_seconds")
		if game_state.has_method("reset_school_tasks"):
			game_state.call("reset_school_tasks")

	for hud: Node in _paused_huds:
		if not is_instance_valid(hud):
			continue
		hud.set_process(true)
		if hud is CanvasLayer:
			(hud as CanvasLayer).visible = true
		elif hud is CanvasItem:
			(hud as CanvasItem).show()
		if hud.has_method("start_challenge"):
			hud.call("start_challenge", false)

	get_tree().paused = false
	var error: Error = get_tree().change_scene_to_file(play_again_scene)
	if error != OK:
		push_error("Cannot restart scene: %s (error %d)" % [play_again_scene, error])
