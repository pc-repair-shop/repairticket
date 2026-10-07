<?php
declare(strict_types=1);

$ticket = trim((string) ($_GET['ticket'] ?? ''));

if (!preg_match('/^[A-Za-z0-9_-]+_[0-9]{4,}$/', $ticket)) {
    http_response_code(400);
    exit('Ongeldig bonnummer.');
}

$pdfFile = '/data/tickets/leergeld/' . $ticket . '.pdf';

if (!is_file($pdfFile)) {
    http_response_code(404);
    exit('Bon niet gevonden.');
}

$messages = [
    'saved' => [
        'success',
        'Bon opgeslagen. Er is geen printopdracht aangemaakt.',
    ],
    'queued' => [
        'success',
        'Bon opgeslagen en in de printwachtrij geplaatst.',
    ],
    'requeued' => [
        'success',
        'De bon is opnieuw in de printwachtrij geplaatst.',
    ],
    'queue-failed' => [
        'warning',
        'De bon is opgeslagen, maar kon niet in de printwachtrij worden geplaatst.',
    ],
];

$status = (string) ($_GET['status'] ?? '');
$message = $messages[$status] ?? null;

$displayTicket = htmlspecialchars(
    $ticket,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Leergeld-bon <?= $displayTicket ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
        }

        .message {
            border: 2px solid;
            padding: 12px 16px;
            margin-bottom: 18px;
        }

        .success {
            border-color: #237a3b;
        }

        .warning {
            border-color: #b36b00;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .actions form {
            margin: 0;
        }

        iframe {
            display: block;
            width: 100%;
            height: 78vh;
            border: 1px solid #999;
        }
    </style>
</head>

<body>
<h1>Leergeld-bon <?= $displayTicket ?></h1>

<?php if ($message !== null): ?>
    <div class="message <?= htmlspecialchars($message[0]) ?>">
        <?= htmlspecialchars($message[1]) ?>
    </div>
<?php endif; ?>

<div class="actions">
    <a href="/leergeld/">Nieuwe bon</a>

    <form action="/leergeld/history.php" method="get">
        <button type="submit">
            Alle oude bonnen
        </button>
    </form>

    <a
        href="/leergeld/download.php?ticket=<?= rawurlencode($ticket) ?>"
        target="_blank"
    >
        PDF openen
    </a>

    <form
        action="/reprint.php"
        method="post"
        onsubmit="return confirm(
            'Weet je zeker dat je deze bon opnieuw wilt afdrukken?'
        );"
    >
        <input
            type="hidden"
            name="type"
            value="leergeld"
        >

        <input
            type="hidden"
            name="ticket"
            value="<?= $displayTicket ?>"
        >

        <button type="submit">
            Opnieuw afdrukken
        </button>
    </form>

    <a href="/">
        Naar home
    </a>
</div>

<iframe
    src="/leergeld/download.php?ticket=<?= rawurlencode($ticket) ?>"
    title="Voorbeeld van Leergeld-bon <?= $displayTicket ?>"
></iframe>
</body>
</html>