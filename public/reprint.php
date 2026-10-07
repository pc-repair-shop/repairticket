<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /', true, 303);
    exit;
}

const PRINT_QUEUE_DIR = '/data/print-queue';

require __DIR__ . '/print-queue.php';

$type = trim((string) ($_POST['type'] ?? 'reparatie'));
$ticket = trim((string) ($_POST['ticket'] ?? ''));

if (!in_array($type, ['reparatie', 'leergeld'], true)) {
    http_response_code(400);
    exit('Ongeldig type bon.');
}

if ($type === 'reparatie') {
    if (!preg_match('/^[0-9]{4,}$/', $ticket)) {
        http_response_code(400);
        exit('Ongeldig bonnummer.');
    }

    /*
     * Nieuwe opslaglocatie.
     */
    $pdfFile = '/data/tickets/reparatie/'
        . $ticket
        . '.pdf';

    /*
     * Tijdelijke ondersteuning voor bestaande reparatiebonnen
     * die nog rechtstreeks in /data/tickets staan.
     */
    if (!is_file($pdfFile)) {
        $legacyPdfFile = '/data/tickets/'
            . $ticket
            . '.pdf';

        if (is_file($legacyPdfFile)) {
            $pdfFile = $legacyPdfFile;
        }
    }

    $redirect = '/reparatie/ticket.php?ticket='
        . rawurlencode($ticket);
} else {
    if (
        !preg_match(
            '/^[A-Za-z0-9_-]+_[0-9]{4,}$/',
            $ticket
        )
    ) {
        http_response_code(400);
        exit('Ongeldig bonnummer.');
    }

    $pdfFile = '/data/tickets/leergeld/'
        . $ticket
        . '.pdf';

    $redirect = '/leergeld/ticket.php?ticket='
        . rawurlencode($ticket);
}

if (!is_file($pdfFile)) {
    http_response_code(404);
    exit('Bon niet gevonden.');
}

$result = queuePdf($pdfFile);

$status = $result['success']
    ? 'requeued'
    : 'queue-failed';

header(
    'Location: '
    . $redirect
    . '&status='
    . rawurlencode($status),
    true,
    303
);

exit;