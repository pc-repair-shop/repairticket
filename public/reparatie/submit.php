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
const PRINT_QUEUE_DIR = DATA_DIR . '/print-queue';
const COUNTER_FILE = DATA_DIR . '/reparatie.txt';

require __DIR__ . '/print-queue.php';

function field(string $name): string
{
    return trim((string) ($_POST[$name] ?? ''));
}

function choice(string $name): string
{
    $value = field($name);
    return in_array($value, ['Ja', 'Nee'], true) ? $value : '';
}

function amount(string $value): float
{
    $normalized = str_replace(',', '.', $value);
    if ($normalized === '' || !is_numeric($normalized)) {
        return 0.0;
    }
    return max(0.0, (float) $normalized);
}

function money(float $value): string
{
    return number_format($value, 2, ',', '.');
}

/** @return list<array{omschrijving: string, aantal: int, prijs: string, subtotaal: string, subtotaal_waarde: float}> */
function extraItems(): array
{
    $descriptions = $_POST['extra_omschrijving'] ?? [];
    $quantities = $_POST['extra_aantal'] ?? [];
    $prices = $_POST['extra_prijs'] ?? [];

    if (!is_array($descriptions) || !is_array($quantities) || !is_array($prices)) {
        return [];
    }

    $items = [];
    foreach (array_slice($descriptions, 0, 10) as $index => $descriptionRaw) {
        $description = trim((string) $descriptionRaw);
        if ($description === '') {
            continue;
        }

        $quantity = max(1, min(999, (int) ($quantities[$index] ?? 1)));
        $unitPrice = amount((string) ($prices[$index] ?? ''));

        $items[] = [
            'omschrijving' => $description,
            'aantal' => $quantity,
            'prijs' => money($unitPrice),
            'subtotaal' => money($quantity * $unitPrice),
            'subtotaal_waarde' => $quantity * $unitPrice,
        ];
    }

    return $items;
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

if (!is_dir(TICKET_DIR) && !mkdir(TICKET_DIR, 0775, true) && !is_dir(TICKET_DIR)) {
    http_response_code(500);
    exit('De opslagmap voor bonnen kon niet worden aangemaakt.');
}

$datumRaw = field('datum');
$action = field('action');
if (!in_array($action, ['save', 'print'], true)) {
    http_response_code(400);
    exit('Ongeldige actie.');
}

$date = DateTimeImmutable::createFromFormat('Y-m-d', $datumRaw);
if ($date === false || $date->format('Y-m-d') !== $datumRaw || field('naam') === '') {
    http_response_code(422);
    exit('Vul minimaal een geldige datum en klantnaam in.');
}

$ticketNumber = nextTicketNumber();
$mainPriceRaw = field('prijs');
$mainPrice = amount($mainPriceRaw);
$extraItems = extraItems();
$total = $mainPrice;
foreach ($extraItems as $item) {
    $total += $item['subtotaal_waarde'];
}

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
    'prijs' => $mainPriceRaw !== '' ? money($mainPrice) : '',
    'totaal' => money($total),
    'extra_artikelen' => $extraItems,
    'omschrijving' => field('omschrijving'),
    'meedoenregeling' => choice('meedoenregeling'),
    'reparatie' => choice('reparatie'),
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

$status = 'saved';
if ($action === 'print') {
    $status = queuePdf($pdfFile)['success'] ? 'queued' : 'queue-failed';
}

header('Location: /ticket.php?ticket=' . rawurlencode($ticketNumber) . '&status=' . rawurlencode($status), true, 303);
exit;
