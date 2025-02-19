<?php

use App\controllers\TodoController;

// Set response type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Create a PDO instance
$dbConfig = require __DIR__ . '/../config/database.php';
try {
    $pdo = new PDO(
        "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['dbname'] . ";charset=" . $dbConfig['charset'],
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}

// Initialize the controller
$todoController = new TodoController($pdo);

// Get the request method and URI
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Debug information
error_log("Original Request URI: " . $requestUri);

// Remove the base path from the URI
$basePath = '/todo-backend-php/public';
$requestUri = str_replace($basePath, '', $requestUri);
$requestUri = trim($requestUri, '/');

error_log("Processed Request URI: " . $requestUri);
error_log("Request Method: " . $requestMethod);

// Handle the request
if (empty($requestUri)) {
    // Root endpoint - show API documentation
    echo json_encode([
        'message' => 'Todo Backend API',
        'endpoints' => [
            'GET /todos' => 'List all todos',
            'GET /todos/{id}' => 'Get a specific todo',
            'POST /todos' => 'Create a new todo',
            'PUT /todos/{id}' => 'Update a todo',
            'DELETE /todos/{id}' => 'Delete a todo'
        ]
    ]);
} elseif ($requestUri === 'todos') {
    // /todos endpoint
    if ($requestMethod === 'GET') {
        $todoController->getAllTodos();
    } elseif ($requestMethod === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $todoController->createTodo($data);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} elseif (preg_match('/^todos\/(\d+)$/', $requestUri, $matches)) {
    // /todos/{id} endpoint
    $todoId = $matches[1];
    
    if ($requestMethod === 'GET') {
        $todoController->getTodoById($todoId);
    } elseif ($requestMethod === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $todoController->updateTodo($todoId, $data);
    } elseif ($requestMethod === 'DELETE') {
        $todoController->deleteTodo($todoId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} else {
    // Invalid route
    http_response_code(404);
    echo json_encode(['error' => 'Invalid route']);
}
