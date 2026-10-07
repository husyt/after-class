extends CanvasLayer

@onready var progress: Label = $PanelContainer/MarginContainer/VBoxContainer/Progress
@onready var task1: Label = $PanelContainer/MarginContainer/VBoxContainer/Task1
@onready var task2: Label = $PanelContainer/MarginContainer/VBoxContainer/Task2
@onready var task3: Label = $PanelContainer/MarginContainer/VBoxContainer/Task3
@onready var task4: Label = $PanelContainer/MarginContainer/VBoxContainer/Task4
@onready var task5: Label = $PanelContainer/MarginContainer/VBoxContainer/Task5


func _ready() -> void:
	update_tasks()

	if !GameState.school_task_updated.is_connected(update_tasks):
		GameState.school_task_updated.connect(update_tasks)


func update_tasks() -> void:
	var completed: int = GameState.get_school_task_count()

	progress.text = str(completed) + " / 5 Completed"

	task1.text = get_task_text(
		GameState.school_tasks["attend_class"],
		"Attend Class"
	)

	task2.text = get_task_text(
		GameState.school_tasks["borrow_book"],
		"Borrow a Book"
	)

	task3.text = get_task_text(
		GameState.school_tasks["health_form"],
		"Get Health Form"
	)

	task4.text = get_task_text(
		GameState.school_tasks["eat_lunch"],
		"Eat Lunch"
	)

	task5.text = get_task_text(
		GameState.school_tasks["computer_activity"],
		"Finish Computer Activity"
	)


func get_task_text(completed: bool, task_name: String) -> String:
	if completed:
		return "✓ " + task_name

	return "□ " + task_name
