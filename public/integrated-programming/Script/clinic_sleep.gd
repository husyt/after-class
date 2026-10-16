extends Control

@export_file("*.tscn") var guidance_office_scene_path: String = "res://Scene/guidance_office.tscn"
@export_range(2.0, 8.0, 0.5) var sleep_loading_seconds: float = 3.0
@export_range(1.0, 5.0, 0.5) var caught_message_seconds: float = 2.5

var progress_bar: ProgressBar
var main_label: Label
var detail_label: Label
var school_hud: CanvasLayer = null
var hud_was_visible: bool = false
var sending_to_guidance: bool = false


func _ready() -> void:
	set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	school_hud = get_node_or_null("/root/SchoolHUD") as CanvasLayer
	if school_hud != null:
		hud_was_visible = school_hud.visible
		school_hud.hide()
	_build_sleep_screen()
	_run_sleep_sequence()


func _exit_tree() -> void:
	if is_instance_valid(school_hud) and hud_was_visible:
		school_hud.show()


func _build_sleep_screen() -> void:
	var black: ColorRect = ColorRect.new()
	black.color = Color.BLACK
	add_child(black)
	black.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var center: CenterContainer = CenterContainer.new()
	add_child(center)
	center.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var block: VBoxContainer = VBoxContainer.new()
	block.custom_minimum_size.x = 340
	block.add_theme_constant_override("separation", 20)
	center.add_child(block)

	main_label = _label("Resting on the clinic bed...", 28, Color.WHITE)
	main_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	block.add_child(main_label)

	progress_bar = ProgressBar.new()
	progress_bar.min_value = 0
	progress_bar.max_value = 100
	progress_bar.value = 0
	progress_bar.custom_minimum_size.y = 24
	block.add_child(progress_bar)

	detail_label = _label("Please wait...", 18, Color("#bdc8ca"))
	detail_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	block.add_child(detail_label)


func _run_sleep_sequence() -> void:
	await get_tree().process_frame
	if not is_inside_tree():
		return

	var loading: Tween = create_tween()
	loading.tween_property(progress_bar, "value", 100.0, sleep_loading_seconds)
	await loading.finished
	if not is_inside_tree():
		return

	main_label.text = "You Caught!"
	main_label.add_theme_color_override("font_color", Color("#ffbdad"))
	detail_label.text = "The clinic staff saw you sleeping without permission.\nYou must report to the Guidance Office."
	progress_bar.hide()

	await get_tree().create_timer(caught_message_seconds).timeout
	if is_inside_tree():
		_go_to_guidance()


func _go_to_guidance() -> void:
	if sending_to_guidance:
		return
	if not ResourceLoader.exists(guidance_office_scene_path, "PackedScene"):
		push_error("Missing Guidance Office scene: " + guidance_office_scene_path)
		detail_label.text = "Guidance Office scene not found. Check its scene path."
		return

	sending_to_guidance = true

	var persistent_layer: CanvasLayer = CanvasLayer.new()
	persistent_layer.name = "ClinicCaughtTransition"
	persistent_layer.layer = 250
	persistent_layer.process_mode = Node.PROCESS_MODE_ALWAYS
	get_tree().root.add_child(persistent_layer)

	var black: ColorRect = ColorRect.new()
	black.color = Color.BLACK
	black.mouse_filter = Control.MOUSE_FILTER_STOP
	persistent_layer.add_child(black)
	black.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)

	var sequence: Tween = persistent_layer.create_tween()
	sequence.set_pause_mode(Tween.TWEEN_PAUSE_PROCESS)
	sequence.tween_callback(Callable(get_tree(), "change_scene_to_file").bind(guidance_office_scene_path))
	sequence.tween_interval(0.2)
	sequence.tween_property(black, "modulate:a", 0.0, 0.8)
	sequence.tween_callback(persistent_layer.queue_free)


func _label(value: String, font_size: int, text_color: Color) -> Label:
	var result: Label = Label.new()
	result.text = value
	result.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	result.add_theme_font_size_override("font_size", font_size)
	result.add_theme_color_override("font_color", text_color)
	return result
