<?php

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set response type to JSON
header('Content-Type: application/json');

// Include Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Define the path to the .env file
$dotenvPath = __DIR__ . '/../.env';

// Check if the .env file exists and load it only if it does
if (file_exists($dotenvPath)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

//Initialize database connection
$dbConfig = require __DIR__ . '/../src/config/database.php';
try {
    // First connect without database selection
    $pdo = new PDO(
        "mysql:host=" . $dbConfig['host'] . ";charset=" . $dbConfig['charset'],
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $dbConfig['dbname']);
    
    // Select the database
    $pdo->exec("USE " . $dbConfig['dbname']);
    
    // Create the todos table if it doesn't exist
    $schema = "CREATE TABLE IF NOT EXISTS todos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        priority VARCHAR(50),
        category VARCHAR(100),
        date DATE,
        active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($schema);
    
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