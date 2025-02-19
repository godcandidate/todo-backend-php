<?php

namespace App\models;

use PDO;

class TodoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Create a new todo
    public function createTodo($title, $description, $priority, $category, $date) {
        $query = "INSERT INTO todos (title, description, priority, category, date, active) VALUES (:title, :description, :priority, :category, :date, 1)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':priority' => $priority,
            ':category' => $category,
            ':date' => $date,
        ]);
    }

    // Get all todos
    public function getAllTodos() {
        $query = "SELECT * FROM todos WHERE active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single todo by ID
    public function getTodoById($id) {
        $query = "SELECT * FROM todos WHERE id = :id AND active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update a todo
    public function updateTodo($id, $data) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $query = "UPDATE todos SET " . implode(', ', $fields) . " WHERE id = :id AND active = 1";
        $stmt = $this->db->prepare($query);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    // Delete a todo
    public function deleteTodo($id) {
        $query = "UPDATE todos SET active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
}