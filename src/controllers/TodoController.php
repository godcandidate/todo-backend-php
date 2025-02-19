<?php

namespace App\controllers;

use App\models\TodoModel;

class TodoController {
    private $model;

    public function __construct($db) {
        $this->model = new TodoModel($db);
    }

    // Create a new todo
    public function createTodo($data) {
        $result = $this->model->createTodo(
            $data['title'],
            $data['description'],
            $data['priority'],
            $data['category'],
            $data['date']
        );
        echo json_encode(['success' => $result]);
    }

    // Get all todos
    public function getAllTodos() {
        $todos = $this->model->getAllTodos();
        echo json_encode($todos);
    }

    // Get a single todo by ID
    public function getTodoById($id) {
        $todo = $this->model->getTodoById($id);
        echo json_encode($todo);
    }

    // Update a todo
    public function updateTodo($id, $data) {
        $result = $this->model->updateTodo($id, $data);
        echo json_encode(['success' => $result]);
    }

    // Delete a todo
    public function deleteTodo($id) {
        $result = $this->model->deleteTodo($id);
        echo json_encode(['success' => $result]);
    }
}