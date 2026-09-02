<?php
declare(strict_types=1);

$ticket = (string) ($_GET['ticket'] ?? '');
if (!preg_match('/^[0-9]{4,}$/', $ticket)) {
    http_response_code(400);
    exit('Ongeldig bonnummer.');
}

$file = '/data/tickets/' . $ticket . '.pdf';
if (!is_file($file)) {
    http_response_code(404);
    exit('Bon niet gevonden.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="reparatiebon-' . $ticket . '.pdf"');
header('Content-Length: ' . (string) filesize($file));
header('X-Content-Type-Options: nosniff');
readfile($file);
