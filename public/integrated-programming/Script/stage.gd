extends "res://Script/SceneInteraction.gd"
## Compatibility entry point for your existing Area3D nodes using stage.gd.
## Includes all inherited door/task input, hover/click, saved-position,
## scene-changing, and black-transition functionality.
##
## Attach to each old door node that already uses stage.gd.
## For activity-only Area3Ds use school_task.gd directly instead.
## Set Target Scene Path on unrecognized doors in Inspector.


# Compatibility helpers for older scripts that call these method names.
func configure_interaction() -> void:
	_configure_interaction()


func configure_key() -> void:
	_configure_key()
