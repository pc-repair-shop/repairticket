<?php
declare(strict_types=1);

const TICKET_DIR = '/data/tickets/leergeld';

function validTicketName(string $ticket): bool
{
    return preg_match(
        '/\A[A-Za-z0-9_-]+_[0-9]{4,}\z/',
        $ticket
    ) === 1;
}

$ticket = trim((string) ($_GET['ticket'] ?? ''));

if (!validTicketName($ticket)) {
    http_response_code(400);
    exit('Ongeldige Leergeld-bon.');
}

$pdfFile = TICKET_DIR . '/' . $ticket . '.pdf';

if (!is_file($pdfFile)) {
    http_response_code(404);
    exit('De PDF kon niet worden gevonden.');
}

$fileSize = filesize($pdfFile);

if ($fileSize === false) {
    http_response_code(500);
    exit('De bestandsgrootte kon niet worden vastgesteld.');
}

$download = ($_GET['download'] ?? '') === '1';
$disposition = $download ? 'attachment' : 'inline';
$downloadName = $ticket . '.pdf';

header('Content-Type: application/pdf');
header('Content-Length: ' . $fileSize);
header(
    'Content-Disposition: '
    . $disposition
    . '; filename="'
    . $downloadName
    . '"'
);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');

readfile($pdfFile);
exit;