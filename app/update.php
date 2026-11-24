<?php
/*
 * Chat-Server für den Binär-Chat (kompatibel zum Original-Binär-Chat).
 * Aufruf: update.php?kanal=NAME&timecode=ZAHL&nachricht=TEXT
 *  - timecode=0  -> keine alten Nachrichten, nur aktuellen Zeitstempel liefern
 *  - timecode=1  -> gesamten Verlauf des Kanals liefern
 *  - nachricht   -> wird gespeichert (die eigene Nachricht kommt nicht zurück)
 * Antwort: {"timecode":<ms>,"nachrichten":[...]}
 * Datenordner: Umgebungsvariable BINAERCHAT_DATEN, sonst Unterordner "daten" (muss beschreibbar sein).
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$kanal     = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['kanal'] ?? '');
$timecode  = (int)($_GET['timecode'] ?? 0);
$nachricht = mb_substr((string)($_GET['nachricht'] ?? ''), 0, 200);
if ($kanal === '') { echo json_encode(['timecode' => 0, 'nachrichten' => []]); exit; }

$ordner = getenv('BINAERCHAT_DATEN') ?: (__DIR__ . '/daten');
if (!is_dir($ordner)) { @mkdir($ordner, 0775, true); }
// gelegentlich aufräumen: Kanäle, die 7 Tage nicht benutzt wurden, löschen
if (mt_rand(1, 100) === 1) {
    foreach (glob($ordner . '/*.json') ?: [] as $f) {
        if (filemtime($f) < time() - 7 * 24 * 3600) @unlink($f);
    }
}
$datei = $ordner . '/' . md5($kanal) . '.json';

function jetzt() { return (int) round(microtime(true) * 1000); }

function lesen($datei) {
    if (!file_exists($datei)) return [];
    $d = json_decode((string) @file_get_contents($datei), true);
    return is_array($d) ? $d : [];
}

$eigenT = null;
if ($nachricht !== '') {
    $fh = fopen($datei, 'c+');
    flock($fh, LOCK_EX);
    $inhalt = stream_get_contents($fh);
    $liste = json_decode($inhalt ?: '[]', true) ?: [];
    $grenze = jetzt() - 24 * 3600 * 1000;                     // Nachrichten älter als 24 h löschen
    $liste = array_values(array_filter($liste, fn($m) => $m['t'] > $grenze));
    $letzte = $liste ? end($liste)['t'] : 0;
    $eigenT = max(jetzt(), $letzte + 1);
    $liste[] = ['t' => $eigenT, 'm' => $nachricht];
    ftruncate($fh, 0); rewind($fh);
    fwrite($fh, json_encode($liste));
    fflush($fh); flock($fh, LOCK_UN); fclose($fh);
}

$ende = microtime(true) + 1.0;   // kurzes Long-Polling (max. 1 s)
do {
    $liste = lesen($datei);
    $neu = [];
    $max = $timecode;
    foreach ($liste as $m) {
        if ($m['t'] > $max) $max = $m['t'];
        if ($timecode > 0 && $m['t'] > $timecode && $m['t'] !== $eigenT) $neu[] = $m['m'];
    }
    if ($neu || $nachricht !== '' || $timecode === 0) break;
    usleep(150000);
} while (microtime(true) < $ende);

// Zeitstempel der neuesten bekannten Nachricht zurückgeben (so geht nichts verloren)
echo json_encode(['timecode' => max($max, 1), 'nachrichten' => $neu]);
