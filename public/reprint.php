<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /', true, 303);
    exit;
}

const PRINT_QUEUE_DIR = '/data/print-queue';
require __DIR__ . '/print-queue.php';

$ticket = trim((string) ($_POST['ticket'] ?? ''));
if (!preg_match('/^[0-9]{4,}$/', $ticket)) {
    http_response_code(400);
    exit('Ongeldig bonnummer.');
}

$pdfFile = '/data/tickets/' . $ticket . '.pdf';
if (!is_file($pdfFile)) {
    http_response_code(404);
    exit('Bon niet gevonden.');
}

$status = queuePdf($pdfFile)['success'] ? 'requeued' : 'queue-failed';
header('Location: /ticket.php?ticket=' . rawurlencode($ticket) . '&status=' . rawurlencode($status), true, 303);
exit;
