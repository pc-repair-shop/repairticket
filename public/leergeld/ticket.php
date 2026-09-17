<?php
declare(strict_types=1);

const TICKET_DIR = '/data/tickets/leergeld';

function escape(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function validTicketName(string $ticket): bool
{
    return preg_match(
        '/\A[A-Za-z0-9_-]+_[0-9]{4,}\z/',
        $ticket
    ) === 1;
}

$ticket = trim((string) ($_GET['ticket'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

if (!validTicketName($ticket)) {
    http_response_code(400);
    exit('Ongeldige Leergeld-bon.');
}

$jsonFile = TICKET_DIR . '/' . $ticket . '.json';
$pdfFile = TICKET_DIR . '/' . $ticket . '.pdf';

if (!is_file($jsonFile) || !is_file($pdfFile)) {
    http_response_code(404);
    exit('De Leergeld-bon kon niet worden gevonden.');
}

$json = file_get_contents($jsonFile);
$data = $json !== false
    ? json_decode($json, true)
    : null;

if (!is_array($data)) {
    http_response_code(500);
    exit('De gegevens van de Leergeld-bon konden niet worden gelezen.');
}

$statusMessages = [
    'saved' => [
        'class' => 'success',
        'title' => 'Bon opgeslagen',
        'message' => 'De Leergeld-bon is opgeslagen en kan worden bekeken.',
    ],
    'queued' => [
        'class' => 'success',
        'title' => 'Bon opgeslagen en naar de printer gestuurd',
        'message' => 'De Leergeld-bon staat in de printwachtrij.',
    ],
    'queue-failed' => [
        'class' => 'warning',
        'title' => 'Bon opgeslagen, printen niet gelukt',
        'message' => 'De PDF is veilig opgeslagen, maar kon niet in de printwachtrij worden geplaatst.',
    ],
];

$currentStatus = $statusMessages[$status] ?? $statusMessages['saved'];

$recipients = $data['ontvangers'] ?? [];

if (!is_array($recipients)) {
    $recipients = [];
}

$recipientCount = count($recipients);
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Leergeld-bon <?= escape($data['nummer'] ?? '') ?></title>

    <style>
        body {
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px;
            color: #111;
            font-family: Arial, sans-serif;
        }

        .status {
            margin-bottom: 24px;
            padding: 22px;
            border: 2px solid #26833a;
        }

        .status.warning {
            border-color: #ce7900;
        }

        .status h1 {
            margin: 0 0 14px;
        }

        .status p {
            margin: 8px 0;
        }

        .ticket-details {
            margin-bottom: 24px;
            padding: 18px;
            border: 1px solid #999;
        }

        .ticket-details h2 {
            margin-top: 0;
        }

        .ticket-details dl {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 10px;
            margin-bottom: 0;
        }

        .ticket-details dt {
            font-weight: bold;
        }

        .ticket-details dd {
            margin: 0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            border: 1px solid #333;
            background: #f2f2f2;
            color: #000;
            text-decoration: none;
        }

        .button:hover {
            background: #ddd;
        }

        .primary-button {
            background: #e5e5e5;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .ticket-details dl {
                display: block;
            }

            .ticket-details dt {
                margin-top: 12px;
            }

            .ticket-details dd {
                margin-top: 3px;
            }
        }
    </style>
</head>

<body>
    <section class="status <?= escape($currentStatus['class']) ?>">
        <h1><?= escape($currentStatus['title']) ?></h1>

        <p><?= escape($currentStatus['message']) ?></p>

        <p>
            Bonnnummer:
            <strong><?= escape($data['nummer'] ?? '') ?></strong>
        </p>
    </section>

    <section class="ticket-details">
        <h2>Gegevens van de bon</h2>

        <dl>
            <dt>Gezinsnummer</dt>
            <dd><?= escape($data['gezinsnummer'] ?? '') ?></dd>

            <dt>Datum</dt>
            <dd><?= escape($data['datum'] ?? '') ?></dd>

            <dt>Aantal ontvangers</dt>
            <dd><?= escape($recipientCount) ?></dd>

            <dt>Bestandsnaam</dt>
            <dd><?= escape($ticket) ?>.pdf</dd>
        </dl>
    </section>

    <nav class="actions">
        <a
            class="button primary-button"
            href="download.php?ticket=<?= rawurlencode($ticket) ?>"
            target="_blank"
        >
            PDF bekijken
        </a>

        <a
            class="button"
            href="download.php?ticket=<?= rawurlencode($ticket) ?>&download=1"
        >
            PDF downloaden
        </a>

        <a class="button" href="index.html">
            Nieuwe Leergeld-bon
        </a>

        <a class="button" href="history.php">
            Geschiedenis
        </a>

        <a class="button" href="../">
            Naar home
        </a>
    </nav>
</body>
</html>