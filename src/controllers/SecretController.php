<?php

require_once __DIR__ . '/../handlers/ResponseHandler.php';

class SecretController {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function addSecret($secretText, $expireAfterViews, $expireAfter) {
        // Validate inputs
        if (empty($secretText) || $expireAfterViews <= 0 || $expireAfter < 0) {
            ResponseHandler::send(["error" => "Invalid input"], 400, $this->getResponseFormat());
            return;
        }

        // Generate hash and calculate expiration time
        $hash = bin2hex(random_bytes(16));
        $expiresAt = $expireAfter > 0 ? date("Y-m-d H:i:s", strtotime("+$expireAfter minutes")) : null;

        // Insert secret into database
        $stmt = $this->db->prepare("INSERT INTO secrets (hash, secret_text, expires_at, remaining_views) VALUES (?, ?, ?, ?)");
        $stmt->execute([$hash, $secretText, $expiresAt, $expireAfterViews]);

        // Prepare and send response
        $response = ["hash" => $hash];
        ResponseHandler::send($response, 200, $this->getResponseFormat());
    }

    public function getSecretByHash($hash) {
        // Fetch secret by hash
        $stmt = $this->db->prepare("SELECT * FROM secrets WHERE hash = ?");
        $stmt->execute([$hash]);
        $secret = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$secret) {
            ResponseHandler::send(["error" => "Secret not found"], 404, $this->getResponseFormat());
            return;
        }

        // Check expiration
        if ($secret['expires_at'] && strtotime($secret['expires_at']) < time()) {
            ResponseHandler::send(["error" => "Secret expired"], 404, $this->getResponseFormat());
            return;
        }

        // Check remaining views
        if ($secret['remaining_views'] <= 0) {
            ResponseHandler::send(["error" => "Secret no longer available"], 404, $this->getResponseFormat());
            return;
        }

        // Decrease remaining views
        $stmt = $this->db->prepare("UPDATE secrets SET remaining_views = remaining_views - 1 WHERE hash = ?");
        $stmt->execute([$hash]);

        // Prepare response
        unset($secret['id']); // Remove internal database ID
        unset($secret['secret_text']); // Prevent exposing the secret text directly
        ResponseHandler::send($secret, 200, $this->getResponseFormat());
    }

    private function getResponseFormat() {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? 'application/json';
        return strpos($acceptHeader, 'application/xml') !== false ? 'xml' : 'json';
    }
}

?>
