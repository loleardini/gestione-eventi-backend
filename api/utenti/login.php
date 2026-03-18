<?php
require_once dirname(__DIR__) . '/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Metodo non consentito. Usa POST.'], 405);
}

$inputJSON = file_get_contents('php:
$input = json_decode($inputJSON, true);

if (!isset($input['Email']) || !isset($input['Password'])) {
    jsonResponse(['error' => 'Dati obbligatori mancanti: Email e Password'], 400);
}

$query = 'Email=eq.' . urlencode($input['Email']);
$res = supabaseRequest('GET', 'Utenti', $query);

if ($res['code'] !== 200 || count($res['data']) === 0) {
    jsonResponse(['error' => 'Credenziali non valide (Email non trovata)'], 401);
}

$user = $res['data'][0];

if (password_verify($input['Password'], $user['Password'])) {

    $payload = [
        'UtenteID' => $user['UtenteID'],
        'Email' => $user['Email'],
        'Ruolo' => $user['Ruolo'],
        'Nome' => $user['Nome'],
        'Cognome' => $user['Cognome']
    ];

    $token = generateJWT($payload);

    jsonResponse([
        'success' => true,
        'token' => $token,
        'user' => $payload
    ], 200);
} else {
    jsonResponse(['error' => 'Credenziali non valide (Password errata)'], 401);
}
?>
