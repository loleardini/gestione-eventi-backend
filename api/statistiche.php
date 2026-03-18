<?php
require_once __DIR__ . '/utils.php';

$method = $_SERVER['REQUEST_METHOD'];
$payload = authMiddleware();

// Solo la dashboard organizzatore mostra statistiche avanzate
checkRole($payload, 'Organizzatore');

if ($method === 'GET') {
    $dal = isset($_GET['dal']) ? $_GET['dal'] : null;
    $al = isset($_GET['al']) ? $_GET['al'] : null;
    
    // Mostrare solo gli eventi "già avvenuti" calcolabile con '< ORA' o '< OGGI'
    // Definiamo gia avvenuti quegli eventi per cui la data è passata.
    $now = (new DateTime())->format('Y-m-d\TH:i:s');
    
    $queryParts = ['Data=lt.'.$now];
    
    if ($dal) {
        $queryParts[] = 'Data=gte.'.$dal;
    }
    if ($al) {
        $queryParts[] = 'Data=lte.'.$al;
    }
    
    // Usiamo il Resource Embedding di PostgREST: selezioniamo gli eventi e mettiamo array le iscrizioni figlie
    $queryContext = implode('&', $queryParts) . '&select=*,Iscrizioni(IscrizioneID,CheckinEffettuato)';
    
    $res = supabaseRequest('GET', 'Eventi', $queryContext);
    
    if ($res['code'] === 200) {
        $statistiche = [];
        foreach ($res['data'] as $evento) {
            $iscritti = isset($evento['Iscrizioni']) ? count($evento['Iscrizioni']) : 0;
            $checkinEffettuati = 0;
            
            if (isset($evento['Iscrizioni'])) {
                foreach ($evento['Iscrizioni'] as $isc) {
                    if ($isc['CheckinEffettuato'] === true) {
                        $checkinEffettuati++;
                    }
                }
            }
            
            $percento = $iscritti > 0 ? round(($checkinEffettuati / $iscritti) * 100, 2) : 0;
            
            $statistiche[] = [
                'EventoID' => $evento['EventoID'],
                'Titolo' => $evento['Titolo'],
                'Data' => $evento['Data'],
                'Iscritti' => $iscritti,
                'CheckinEffettuati' => $checkinEffettuati,
                'PercentualePartecipazione' => $percento
            ];
        }
        jsonResponse($statistiche);
    } else {
        jsonResponse(['error' => 'Errore durante il parsing delle statistiche dal db'], 500);
    }
}

jsonResponse(['error' => 'Metodo non consentito'], 405);
?>
