extends Area3D

@export_file("*.tscn") var target_scene: String

@onready var enter_prompt: Label = $"../CanvasLayer/FPrompt"

var player_inside: bool = false
var changing_scene: bool = false


func _ready() -> void:
	enter_prompt.hide()

	body_entered.connect(_on_body_entered)
	body_exited.connect(_on_body_exited)


func _process(_delta: float) -> void:
	if changing_scene:
		return

	if player_inside and Input.is_action_just_pressed("interact"):
		print("F PRESSED")
		print("Going to: ", target_scene)
		change_stage()


func _on_body_entered(body: Node3D) -> void:
	print("BODY ENTERED: ", body.name)

	if body.is_in_group("player"):
		print("PLAYER DETECTED")
		player_inside = true
		enter_prompt.show()


func _on_body_exited(body: Node3D) -> void:
	print("BODY EXITED: ", body.name)

	if body.is_in_group("player"):
		player_inside = false
		enter_prompt.hide()


func change_stage() -> void:
	if target_scene.is_empty():
		push_warning("No target scene assigned.")
		return

	if !ResourceLoader.exists(target_scene):
		push_warning("Scene does not exist: " + target_scene)
		return

	changing_scene = true
	enter_prompt.hide()

	var error := get_tree().change_scene_to_file(target_scene)

	if error != OK:
		push_error("Failed to change scene. Error code: " + str(error))
		changing_scene = false
