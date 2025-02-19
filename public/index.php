<?php

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set response type to JSON
header('Content-Type: application/json');

// Include Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize database connection
$dbConfig = require __DIR__ . '/../src/config/database.php';
try {
    $pdo = new PDO(
        "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['dbname'] . ";charset=" . $dbConfig['charset'],
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Initialize the controller
$todoController = new \App\controllers\TodoController($pdo);

// Get the request path
$fullUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$requestPath = $fullUri;

// Remove the script name from the request path if it exists
if (strpos($requestPath, $scriptName) === 0) {
    $requestPath = substr($requestPath, strlen($scriptName));
}

// Clean up the path
$requestPath = trim($requestPath, '/');

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle CORS preflight requests
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit();
}

// Set CORS headers for all other requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Handle the request
    if (empty($requestPath)) {
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
    } elseif ($requestPath === 'todos') {
        // /todos endpoint
        switch ($method) {
            case 'GET':
                $todoController->getAllTodos();
                break;
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $todoController->createTodo($data);
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    } elseif (preg_match('/^todos\/(\d+)$/', $requestPath, $matches)) {
        // /todos/{id} endpoint
        $todoId = $matches[1];
        
        switch ($method) {
            case 'GET':
                $todoController->getTodoById($todoId);
                break;
            case 'PUT':
                $data = json_decode(file_get_contents('php://input'), true);
                $todoController->updateTodo($todoId, $data);
                break;
            case 'DELETE':
                $todoController->deleteTodo($todoId);
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}