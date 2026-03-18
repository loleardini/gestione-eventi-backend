<?php
require_once __DIR__ . '/utils.php';
$method = $_SERVER['REQUEST_METHOD'];
$payload = authMiddleware();
checkRole($payload, 'Organizzatore');
if ($method === 'GET') {
    $dal = isset($_GET['dal']) ? $_GET['dal'] : null;
    $al = isset($_GET['al']) ? $_GET['al'] : null;
    $queryParts = [];
    if ($dal) {
        $queryParts[] = 'Data=gte.'.$dal;
    }
    if ($al) {
        $queryParts[] = 'Data=lte.'.$al;
    }
    $queryBase = count($queryParts) > 0 ? implode('&', $queryParts) . '&' : '';
    $queryContext = $queryBase . 'select=*,Iscrizioni(IscrizioneID,CheckinEffettuato)&order=Data.desc';
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