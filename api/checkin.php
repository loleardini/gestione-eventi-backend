<?php
require_once __DIR__ . '/utils.php';

$method = $_SERVER['REQUEST_METHOD'];
$payload = authMiddleware(); 

// Solo gli organizzatori possono gestire il checkin
checkRole($payload, 'Organizzatore');

if ($method === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    if (!isset($input['IscrizioneID'])) {
        jsonResponse(['error' => 'IscrizioneID mancante per il check-in'], 400);
    }
    
    $iscrizioneId = $input['IscrizioneID'];
    $now = (new DateTime())->format('Y-m-d\TH:i:sP'); // Iso8601 utile x database su timestamptz

    $updateData = [
        'CheckinEffettuato' => true,
        'OraCheckin' => $now
    ];
    
    $query = 'IscrizioneID=eq.' . $iscrizioneId;
    $res = supabaseRequest('PATCH', 'Iscrizioni', $query, $updateData);
    
    if ($res['code'] >= 200 && $res['code'] < 300) {
        jsonResponse(['success' => true, 'message' => 'Check-in registrato con successo!'], 200);
    } else {
        jsonResponse(['error' => 'Errore nella registrazione del check-in'], 500);
    }
}

jsonResponse(['error' => 'Metodo non consentito'], 405);
?>
