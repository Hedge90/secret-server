<?php
require_once __DIR__ . '/../src/database/Database.php';
require_once __DIR__ . '/../src/controllers/SecretController.php';

header("Content-Type: application/json");

// Load environment variables
$dotenvPath = __DIR__ . '/../.env';
if (file_exists($dotenvPath)) {
    $dotenv = parse_ini_file($dotenvPath);
    foreach ($dotenv as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Database configuration
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_NAME'] ?? 'db';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';

$db = new Database($dbHost, $dbName, $dbUser, $dbPassword);
$secretController = new SecretController($db->getConnection());

// Route handling
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));

if ($requestMethod === "POST" && $requestUri[0] === "secret") {
    $secretText = $_POST['secret'] ?? null;
    $expireAfterViews = $_POST['expireAfterViews'] ?? null;
    $expireAfter = $_POST['expireAfter'] ?? null;

    $secretController->addSecret($secretText, (int)$expireAfterViews, (int)$expireAfter);
} elseif ($requestMethod === "GET" && $requestUri[0] === "secret" && isset($requestUri[1])) {
    $secretController->getSecretByHash($requestUri[1]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found"]);
}

?>