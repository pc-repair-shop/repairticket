<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/ticket-template.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /', true, 303);
    exit;
}

const DATA_DIR = '/data';
const TICKET_DIR = DATA_DIR . '/tickets';
const COUNTER_FILE = DATA_DIR . '/counter.txt';

function field(string $name): string
{
    return trim((string) ($_POST[$name] ?? ''));
}

function choice(string $name): string
{
    $value = field($name);
    return in_array($value, ['Ja', 'Nee'], true) ? $value : '';
}

function money(string $value): string
{
    $normalized = str_replace(',', '.', $value);
    if ($normalized === '' || !is_numeric($normalized)) {
        return '';
    }
    return number_format((float) $normalized, 2, ',', '.');
}

function nextTicketNumber(): string
{
    $handle = fopen(COUNTER_FILE, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        throw new RuntimeException('Bonnummer kon niet worden aangemaakt.');
    }

    try {
        rewind($handle);
        $next = max((int) trim((string) stream_get_contents($handle)) + 1, 1);
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) $next);
        fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

/** @return array{success: bool, message: string} */
function printPdf(string $pdfFile): array
{
    $server = getenv('CUPS_SERVER') ?: 'cups:631';
    $printer = getenv('PRINTER_NAME') ?: 'PCRepairShop';
    $copies = max(1, min(5, (int) (getenv('PRINT_COPIES') ?: 1)));

    if (!preg_match('/^[a-zA-Z0-9._-]+(?::[0-9]{1,5})?$/', $server)
        || !preg_match('/^[a-zA-Z0-9._-]+$/', $printer)) {
        return ['success' => false, 'message' => 'De CUPS-configuratie is ongeldig.'];
    }

    $process = proc_open([
        '/usr/bin/lp', '-h', $server, '-d', $printer,
        '-n', (string) $copies, $pdfFile,
    ], [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);

    if (!is_resource($process)) {
        return ['success' => false, 'message' => 'Het printproces kon niet worden gestart.'];
    }

    $stdout = trim((string) stream_get_contents($pipes[1]));
    $stderr = trim((string) stream_get_contents($pipes[2]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        error_log('CUPS printfout: ' . $stderr);
        return ['success' => false, 'message' => 'De PDF is opgeslagen, maar printen is mislukt.'];
    }

    return ['success' => true, 'message' => $stdout !== '' ? $stdout : 'Printopdracht geaccepteerd.'];
}

if (!is_dir(TICKET_DIR) && !mkdir(TICKET_DIR, 0775, true) && !is_dir(TICKET_DIR)) {
    http_response_code(500);
    exit('De opslagmap voor bonnen kon niet worden aangemaakt.');
}

$datumRaw = field('datum');
$date = DateTimeImmutable::createFromFormat('Y-m-d', $datumRaw);
if ($date === false || $date->format('Y-m-d') !== $datumRaw || field('naam') === '') {
    http_response_code(422);
    exit('Vul minimaal een geldige datum en klantnaam in.');
}

$ticketNumber = nextTicketNumber();
$values = [
    'datum' => $date->format('d-m-Y'),
    'nummer' => $ticketNumber,
    'naam' => field('naam'),
    'adres' => field('adres'),
    'postcode_woonplaats' => field('postcode_woonplaats'),
    'telefoon' => field('telefoon'),
    'email' => field('email'),
    'merk' => field('merk'),
    'model' => field('model'),
    'serienummer' => field('serienummer'),
    'zegel' => field('zegel'),
    'prijs' => money(field('prijs')),
    'totaal' => money(field('totaal')),
    'meedoenregeling' => choice('meedoenregeling'),
    'reparatie' => choice('reparatie'),
    'medewerker' => field('medewerker'),
];

$logo = base64_encode((string) file_get_contents(__DIR__ . '/assets/pcrepairshop-logo.jpg'));
$ticketHtml = renderTicketHtml($values, $logo);

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($ticketHtml, 'UTF-8');
$dompdf->render();

$pdfFile = TICKET_DIR . '/' . $ticketNumber . '.pdf';
if (file_put_contents($pdfFile, $dompdf->output(), LOCK_EX) === false) {
    http_response_code(500);
    exit('De PDF kon niet worden opgeslagen.');
}

$printResult = printPdf($pdfFile);
$statusClass = $printResult['success'] ? 'success' : 'warning';
$statusTitle = $printResult['success'] ? 'Bon opgeslagen en verzonden naar de printer' : 'Bon opgeslagen, printen niet gelukt';
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bon <?= htmlspecialchars($ticketNumber) ?></title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 720px; margin: 40px auto; padding: 0 20px; }
    .message { border: 2px solid; padding: 20px; }
    .success { border-color: #237a3b; }
    .warning { border-color: #b36b00; }
    .actions { margin-top: 24px; }
    a { margin-right: 16px; }
  </style>
</head>
<body>
  <div class="message <?= $statusClass ?>">
    <h1><?= htmlspecialchars($statusTitle) ?></h1>
    <p>Bonnummer: <strong><?= htmlspecialchars($ticketNumber) ?></strong></p>
    <p><?= htmlspecialchars($printResult['message']) ?></p>
  </div>
  <p class="actions">
    <a href="/">Nieuwe bon</a>
    <a href="/download.php?ticket=<?= rawurlencode($ticketNumber) ?>">PDF bekijken of downloaden</a>
  </p>
</body>
</html>
