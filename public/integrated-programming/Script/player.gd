extends CharacterBody3D

@export var move_speed: float = 3.0
@export var locked_z: float = 1.0

@onready var sprite: AnimatedSprite3D = $AnimatedSprite3D


func _ready() -> void:
	position.z = locked_z

	if sprite.sprite_frames != null:
		if sprite.sprite_frames.has_animation("Idle"):
			sprite.play("Idle")

	call_deferred("_restore_previous_position")


func _physics_process(_delta: float) -> void:
	var direction: float = Input.get_axis(
		"move_left",
		"move_right"
	)

	velocity = Vector3.ZERO
	velocity.x = direction * move_speed

	move_and_slide()

	position.z = locked_z

	if direction != 0.0:
		sprite.flip_h = direction < 0.0

		if sprite.animation != "Walk" or !sprite.is_playing():
			sprite.play("Walk")

	else:
		if sprite.animation != "Idle" or !sprite.is_playing():
			sprite.play("Idle")


func _restore_previous_position() -> void:
	await get_tree().process_frame

	var current_scene := get_tree().current_scene

	if current_scene == null:
		return

	var scene_path: String = current_scene.scene_file_path

	if scene_path.is_empty():
		return

	if !GameState.has_position(scene_path):
		print("FIRST VISIT: ", scene_path)
		return

	var saved_position: Vector3 = GameState.get_position(scene_path)

	global_position = saved_position
	global_position.z = locked_z
	velocity = Vector3.ZERO

	print(
		"RESTORED POSITION: ",
		scene_path,
		" = ",
		global_position
	)
