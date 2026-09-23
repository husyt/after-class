extends Area3D

@export_file("*.tscn") var target_scene: String
@export var use_interaction: bool = true

var player_inside: bool = false


func _ready() -> void:
	body_entered.connect(_on_body_entered)
	body_exited.connect(_on_body_exited)


func _process(_delta: float) -> void:
	if !use_interaction:
		return

	if player_inside and Input.is_action_just_pressed("interact"):
		change_stage()


func _on_body_entered(body: Node3D) -> void:
	if body.is_in_group("player"):
		player_inside = true

		if !use_interaction:
			change_stage()


func _on_body_exited(body: Node3D) -> void:
	if body.is_in_group("player"):
		player_inside = false


func change_stage() -> void:
	if target_scene.is_empty():
		push_warning("No target scene assigned to this StageExit.")
		return

	get_tree().change_scene_to_file(target_scene)
