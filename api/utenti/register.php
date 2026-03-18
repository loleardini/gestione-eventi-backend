<?php
require_once dirname(__DIR__) . '/utils.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Metodo non consentito. Usa POST.'], 405);
}
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
if (!isset($input['Nome']) || !isset($input['Cognome']) || !isset($input['Email']) || !isset($input['Password'])) {
    jsonResponse(['error' => 'Dati obbligatori mancanti: Nome, Cognome, Email, Password'], 400);
}
$query = 'Email=eq.' . urlencode($input['Email']);
$res = supabaseRequest('GET', 'Utenti', $query);
if ($res['code'] === 200 && count($res['data']) > 0) {
    jsonResponse(['error' => 'Email gia registrata'], 400);
}
$passwordHash = password_hash($input['Password'], PASSWORD_BCRYPT);
$ruolo = isset($input['Ruolo']) && $input['Ruolo'] === 'Organizzatore' ? 'Organizzatore' : 'Dipendente';
$newUser = [
    'Nome' => $input['Nome'],
    'Cognome' => $input['Cognome'],
    'Email' => $input['Email'],
    'Password' => $passwordHash,
    'Ruolo' => $ruolo
];
$insertRes = supabaseRequest('POST', 'Utenti', '', $newUser);
if ($insertRes['code'] >= 200 && $insertRes['code'] < 300) {
    $savedUser = isset($insertRes['data'][0]) ? $insertRes['data'][0] : null;
    jsonResponse([
        'success' => true,
        'message' => 'Registrazione completata',
        'utente' => $savedUser
    ], 201);
} else {
    jsonResponse(['error' => 'Errore interno durante la registrazione sul database', 'dettagli' => $insertRes], 500);
}
?>