<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/ticket-template.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /leergeld/', true, 303);
    exit;
}

const DATA_DIR = '/data';
const TICKET_DIR = DATA_DIR . '/tickets/leergeld';
const COUNTER_DIR = DATA_DIR . '/counters';
const COUNTER_FILE = COUNTER_DIR . '/leergeld.txt';
const PRINT_QUEUE_DIR = DATA_DIR . '/print-queue';

require dirname(__DIR__) . '/print-queue.php';

function field(string $name): string
{
    return trim((string) ($_POST[$name] ?? ''));
}

function cleanValue(mixed $value, int $maximumLength): string
{
    return mb_substr(trim((string) $value), 0, $maximumLength);
}

/**
 * @return list<array{
 *     naam: string,
 *     telefoon: string,
 *     adres: string,
 *     postcode_woonplaats: string,
 *     merk: string,
 *     model: string,
 *     serienummer: string
 * }>
 */
function recipients(): array
{
    $submitted = $_POST['ontvangers'] ?? [];

    if (!is_array($submitted)) {
        return [];
    }

    $recipients = [];

    foreach (array_slice($submitted, 0, 10) as $submittedRecipient) {
        if (!is_array($submittedRecipient)) {
            continue;
        }

        $recipient = [
            'naam' => cleanValue($submittedRecipient['naam'] ?? '', 150),
            'telefoon' => cleanValue(
                $submittedRecipient['telefoon'] ?? '',
                50
            ),
            'adres' => cleanValue($submittedRecipient['adres'] ?? '', 200),
            'postcode_woonplaats' => cleanValue(
                $submittedRecipient['postcode_woonplaats'] ?? '',
                150
            ),
            'merk' => cleanValue($submittedRecipient['merk'] ?? '', 100),
            'model' => cleanValue($submittedRecipient['model'] ?? '', 100),
            'serienummer' => cleanValue(
                $submittedRecipient['serienummer'] ?? '',
                150
            ),
        ];

        if ($recipient['naam'] === '') {
            continue;
        }

        $recipients[] = $recipient;
    }

    return $recipients;
}

function nextTicketNumber(): string
{
    if (
        !is_dir(COUNTER_DIR)
        && !mkdir(COUNTER_DIR, 0775, true)
        && !is_dir(COUNTER_DIR)
    ) {
        throw new RuntimeException(
            'De map voor de bonnummering kon niet worden aangemaakt.'
        );
    }

    $handle = fopen(COUNTER_FILE, 'c+');

    if ($handle === false || !flock($handle, LOCK_EX)) {
        throw new RuntimeException(
            'Het bonnummer kon niet worden aangemaakt.'
        );
    }

    try {
        rewind($handle);

        $current = (int) trim(
            (string) stream_get_contents($handle)
        );

        $next = max($current + 1, 1);

        ftruncate($handle, 0);
        rewind($handle);

        if (fwrite($handle, (string) $next) === false) {
            throw new RuntimeException(
                'Het bonnummer kon niet worden opgeslagen.'
            );
        }

        fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function safeFamilyNumber(string $familyNumber): string
{
    $safe = preg_replace(
        '/[^A-Za-z0-9_-]+/',
        '-',
        trim($familyNumber)
    );

    $safe = trim((string) $safe, '-_');

    if ($safe === '') {
        throw new RuntimeException('Het gezinsnummer is ongeldig.');
    }

    return mb_substr($safe, 0, 80);
}

$action = field('action');

if (!in_array($action, ['save', 'print'], true)) {
    http_response_code(400);
    exit('Ongeldige actie.');
}

$dateValue = field('datum');
$date = DateTimeImmutable::createFromFormat('Y-m-d', $dateValue);

if ($date === false || $date->format('Y-m-d') !== $dateValue) {
    http_response_code(422);
    exit('Vul een geldige datum in.');
}

$familyNumber = field('gezinsnummer');

if ($familyNumber === '') {
    http_response_code(422);
    exit('Vul een gezinsnummer in.');
}

$recipients = recipients();

if ($recipients === []) {
    http_response_code(422);
    exit('Voeg minimaal één klant en computer toe.');
}

if (
    !is_dir(TICKET_DIR)
    && !mkdir(TICKET_DIR, 0775, true)
    && !is_dir(TICKET_DIR)
) {
    http_response_code(500);
    exit('De opslagmap voor Leergeld-bonnen kon niet worden aangemaakt.');
}

try {
    $ticketNumber = nextTicketNumber();
    $safeFamilyNumber = safeFamilyNumber($familyNumber);
} catch (RuntimeException $exception) {
    http_response_code(500);
    exit($exception->getMessage());
}

$fileBase = $safeFamilyNumber . '_' . $ticketNumber;

$values = [
    'datum' => $date->format('d-m-Y'),
    'nummer' => $ticketNumber,
    'gezinsnummer' => $familyNumber,
    'ontvangers' => $recipients,
];

$logoFile = dirname(__DIR__) . '/assets/pcrepairshop-logo.jpg';

if (!is_file($logoFile)) {
    http_response_code(500);
    exit('Het logo kon niet worden gevonden.');
}

$logo = base64_encode((string) file_get_contents($logoFile));
$ticketHtml = renderLeergeldTicketHtml($values, $logo);

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($ticketHtml, 'UTF-8');
$dompdf->render();

$pdfFile = TICKET_DIR . '/' . $fileBase . '.pdf';
$jsonFile = TICKET_DIR . '/' . $fileBase . '.json';

if (file_put_contents($pdfFile, $dompdf->output(), LOCK_EX) === false) {
    http_response_code(500);
    exit('De PDF kon niet worden opgeslagen.');
}

$json = json_encode(
    $values,
    JSON_PRETTY_PRINT
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);

if ($json === false || file_put_contents($jsonFile, $json, LOCK_EX) === false) {
    @unlink($pdfFile);

    http_response_code(500);
    exit('De gegevens van de bon konden niet worden opgeslagen.');
}

$status = 'saved';

if ($action === 'print') {
    $status = queuePdf($pdfFile)['success']
        ? 'queued'
        : 'queue-failed';
}

header(
    'Location: /leergeld/ticket.php?ticket='
    . rawurlencode($fileBase)
    . '&status='
    . rawurlencode($status),
    true,
    303
);

exit;