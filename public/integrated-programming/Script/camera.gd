extends Camera3D

@export var target: Node3D
@export var follow_speed: float = 5.0
@export var dead_zone: float = 2.0

var fixed_y: float
var fixed_z: float


func _ready() -> void:
	current = true

	fixed_y = global_position.y
	fixed_z = global_position.z


func _process(delta: float) -> void:
	if target == null:
		return

	var difference: float = target.global_position.x - global_position.x

	if abs(difference) > dead_zone:
		var target_x: float

		if difference > 0.0:
			target_x = target.global_position.x - dead_zone
		else:
			target_x = target.global_position.x + dead_zone

		global_position.x = lerp(
			global_position.x,
			target_x,
			1.0 - exp(-follow_speed * delta)
		)

	# Keep camera from moving vertically or in depth.
	global_position.y = fixed_y
	global_position.z = fixed_z
