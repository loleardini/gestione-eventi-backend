<?php
require_once __DIR__ . '/utils.php';

$method = $_SERVER['REQUEST_METHOD'];
$payload = authMiddleware(); // Tutti gli endpoint dell'iscrizione richiedono il token

if ($method === 'GET') {
    $utenteId = $payload['UtenteID'];
    
    // Se e un organizzatore e richiede gli iscritti di un evento specifico
    if ($payload['Ruolo'] === 'Organizzatore' && isset($_GET['EventoID'])) {
         $query = 'EventoID=eq.' . $_GET['EventoID'] . '&select=*,Utenti(Nome,Cognome,Email)';
    } else {
         // Il dipendente recupera i propri eventi iscritti (o per l'organizzatore le proprie iscrizioni personali se per strano caso ne avesse)
         $query = 'UtenteID=eq.' . $utenteId . '&select=*,Eventi(*)';
    }
    
    $res = supabaseRequest('GET', 'Iscrizioni', $query);
    if ($res['code'] === 200) {
        jsonResponse($res['data']);
    } else {
        jsonResponse(['error' => 'Errore nel recupero delle iscrizioni'], 500);
    }
}

if ($method === 'POST') {
    // Iscriviti ad un evento
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    if (!isset($input['EventoID'])) {
        jsonResponse(['error' => 'EventoID mancante'], 400);
    }
    $eventoId = $input['EventoID'];
    $utenteId = $payload['UtenteID'];
    
    // Verifica evento date (fino al giorno prima)
    $resEvento = supabaseRequest('GET', 'Eventi', 'EventoID=eq.' . $eventoId);
    if ($resEvento['code'] !== 200 || count($resEvento['data']) === 0) {
        jsonResponse(['error' => 'Evento non trovato'], 404);
    }
    $evento = $resEvento['data'][0];
    
    // Controllo data iscrizione: "può iscriversi fino al giorno prima"
    $eventDate = new DateTime($evento['Data']);
    $today = new DateTime();
    $today->setTime(0, 0, 0); // Consideriamo solo il giorno (mezzanotte)
    $eventDate->setTime(0, 0, 0);
    
    // Se oggi e uguale o maggiore della data evento, blocco
    if ($eventDate <= $today) {
        jsonResponse(['error' => 'Le iscrizioni sono chiuse. Puoi iscriverti solo fino al giorno prima dell\'evento'], 400);
    }
    
    $newIscrizione = [
        'UtenteID' => $utenteId,
        'EventoID' => $eventoId,
        'CheckinEffettuato' => false
    ];
    
    $resIsc = supabaseRequest('POST', 'Iscrizioni', '', $newIscrizione);
    
    if ($resIsc['code'] >= 200 && $resIsc['code'] < 300) {
        jsonResponse(['success' => true, 'message' => 'Iscrizione effettuata con successo!'], 201);
    } else {
        // Possibile errore UNIQUE constraint
        jsonResponse(['error' => 'Iscrizione fallita. Sei gia iscritto a questo evento?'], 400);
    }
}

if ($method === 'DELETE') {
    // Annulla iscrizione
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    if (!isset($input['IscrizioneID'])) {
        jsonResponse(['error' => 'IscrizioneID mancante per la cancellazione'], 400);
    }
    
    $iscrizioneId = $input['IscrizioneID'];
    $utenteId = $payload['UtenteID'];
    
    // Recupera l'iscrizione per verificare appartenenza e data evento
    $resIsc = supabaseRequest('GET', 'Iscrizioni', 'IscrizioneID=eq.' . $iscrizioneId . '&select=*,Eventi(Data)');
    if ($resIsc['code'] !== 200 || count($resIsc['data']) === 0) {
        jsonResponse(['error' => 'Iscrizione non trovata'], 404);
    }
    
    $iscData = $resIsc['data'][0];
    
    // Un dipendente può annullare solo una propria iscrizione
    if ($iscData['UtenteID'] !== $utenteId) {
        jsonResponse(['error' => 'Non sei autorizzato a cancellare questa iscrizione'], 403);
    }
    
    // Controllo su data evento (fino al giorno prima)
    if (!isset($iscData['Eventi']['Data'])) {
         jsonResponse(['error' => 'Errore nei dati dell\'evento per questa iscrizione'], 500);
    }
    
    $eventDate = new DateTime($iscData['Eventi']['Data']);
    $today = new DateTime();
    $today->setTime(0,0,0);
    $eventDate->setTime(0,0,0);
    
    if ($eventDate <= $today) {
        jsonResponse(['error' => 'Puoi ritirare l\'iscrizione solo fino al giorno prima dell\'evento'], 400);
    }
    
    $delQuery = 'IscrizioneID=eq.' . $iscrizioneId;
    $resDel = supabaseRequest('DELETE', 'Iscrizioni', $delQuery);
    
    if ($resDel['code'] >= 200 && $resDel['code'] < 300) {
        jsonResponse(['success' => true, 'message' => 'Iscrizione annullata con successo'], 200);
    } else {
         jsonResponse(['error' => 'Errore interno (Server error) durante la disiscrizione'], 500);
    }
}

jsonResponse(['error' => 'Metodo non consentito'], 405);
?>
