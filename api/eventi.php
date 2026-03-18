<?php
require_once __DIR__ . '/utils.php';
$method = $_SERVER['REQUEST_METHOD'];
$payload = authMiddleware();
if ($method === 'GET') {
    $query = 'order=Data.asc';
    $res = supabaseRequest('GET', 'Eventi', $query);
    if ($res['code'] === 200) {
        jsonResponse($res['data']);
    } else {
        jsonResponse(['error' => 'Errore nel recupero degli eventi'], 500);
    }
}
checkRole($payload, 'Organizzatore');
if ($method === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (!isset($input['Titolo']) || !isset($input['Data']) || !isset($input['Descrizione'])) {
        jsonResponse(['error' => 'Dati obbligatori mancanti: Titolo, Data, Descrizione'], 400);
    }
    $newEvent = [
        'Titolo' => $input['Titolo'],
        'Data' => $input['Data'],
        'Descrizione' => $input['Descrizione']
    ];
    $res = supabaseRequest('POST', 'Eventi', '', $newEvent);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        $evento_creato = isset($res['data'][0]) ? $res['data'][0] : null;
        jsonResponse(['success' => true, 'message' => 'Evento creato', 'evento' => $evento_creato], 201);
    } else {
        jsonResponse(['error' => 'Errore nella creazione dell\'evento'], 500);
    }
}
if ($method === 'PUT') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (!isset($input['EventoID'])) {
        jsonResponse(['error' => 'EventoID mancante'], 400);
    }
    $eventoId = $input['EventoID'];
    $updateData = [];
    if(isset($input['Titolo'])) $updateData['Titolo'] = $input['Titolo'];
    if(isset($input['Data'])) $updateData['Data'] = $input['Data'];
    if(isset($input['Descrizione'])) $updateData['Descrizione'] = $input['Descrizione'];
    $query = 'EventoID=eq.' . $eventoId;
    $res = supabaseRequest('PATCH', 'Eventi', $query, $updateData);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        jsonResponse(['success' => true, 'message' => 'Evento aggiornato'], 200);
    } else {
        jsonResponse(['error' => 'Errore nell\'aggiornamento'], 500);
    }
}
if ($method === 'DELETE') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (!isset($input['EventoID'])) {
        jsonResponse(['error' => 'EventoID mancante per la cancellazione'], 400);
    }
    $eventoId = $input['EventoID'];
    $query = 'EventoID=eq.' . $eventoId;
    $res = supabaseRequest('DELETE', 'Eventi', $query);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        jsonResponse(['success' => true, 'message' => 'Evento eliminato con successo'], 200);
    } else {
        jsonResponse(['error' => 'Errore nell\'eliminazione'], 500);
    }
}
jsonResponse(['error' => 'Metodo HTTP non consentito su questo endpoint'], 405);
?>