<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
require_once 'config.php';
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
function supabaseRequest($method, $table, $query = '', $data = null) {
    $url = SUPABASE_URL . '/rest/v1/' . $table . ($query ? '?' . $query : '');
    $ch = curl_init($url);
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($method === 'POST' || $method === 'PATCH' || $method === 'PUT') {
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}
function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['exp'] = time() + (86400 * 7);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
function verifyJWT($jwt) {
    $tokenParts = explode('.', $jwt);
    if(count($tokenParts) != 3) return false;
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
    $signatureProvided = $tokenParts[2];
    $base64UrlHeader = $tokenParts[0];
    $base64UrlPayload = $tokenParts[1];
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    if($base64UrlSignature === $signatureProvided) {
        $decodedPayload = json_decode($payload, true);
        if(isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false;
        }
        return $decodedPayload;
    }
    return false;
}
function authMiddleware() {
    $headers = getallheaders();
    if(!isset($headers['Authorization']) && !isset($headers['authorization'])) {
        jsonResponse(['error' => 'Token mancante'], 401);
    }
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
    $parts = explode(' ', $authHeader);
    if(count($parts) != 2 || strtolower($parts[0]) !== 'bearer') {
        jsonResponse(['error' => 'Formato token Bearer non valido'], 401);
    }
    $payload = verifyJWT($parts[1]);
    if(!$payload) {
        jsonResponse(['error' => 'Token non valido o scaduto'], 401);
    }
    return $payload;
}
function checkRole($payload, $role) {
    if(!isset($payload['Ruolo']) || $payload['Ruolo'] !== $role) {
        jsonResponse(['error' => 'Accesso negato. E richiesto il ruolo: ' . $role], 403);
    }
}
?>