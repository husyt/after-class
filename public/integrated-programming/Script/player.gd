extends CharacterBody3D

@export var move_speed: float = 1.0
@export var locked_z: float = 1.0

@onready var sprite: AnimatedSprite3D = $AnimatedSprite3D


func _ready() -> void:
	position.z = locked_z
	sprite.play("Idle")


func _physics_process(_delta: float) -> void:
	var direction: float = Input.get_axis("move_left", "move_right")

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
