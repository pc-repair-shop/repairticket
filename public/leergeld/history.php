<?php
declare(strict_types=1);

const TICKET_DIR = '/data/tickets/leergeld';

/**
 * @return list<array{
 *     file_base: string,
 *     nummer: string,
 *     gezinsnummer: string,
 *     datum: string,
 *     ontvangers: string,
 *     modified: int
 * }>
 */
function loadTickets(): array
{
    if (!is_dir(TICKET_DIR)) {
        return [];
    }

    $jsonFiles = glob(TICKET_DIR . '/*.json');

    if ($jsonFiles === false) {
        return [];
    }

    $tickets = [];

    foreach ($jsonFiles as $jsonFile) {
        $fileBase = basename($jsonFile, '.json');

        if (
            !preg_match(
                '/^[A-Za-z0-9_-]+_[0-9]{4,}$/',
                $fileBase
            )
        ) {
            continue;
        }

        $json = file_get_contents($jsonFile);

        if ($json === false) {
            continue;
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            continue;
        }

        $recipientNames = [];
        $recipients = $data['ontvangers'] ?? [];

        if (is_array($recipients)) {
            foreach ($recipients as $recipient) {
                if (!is_array($recipient)) {
                    continue;
                }

                $name = trim(
                    (string) ($recipient['naam'] ?? '')
                );

                if ($name !== '') {
                    $recipientNames[] = $name;
                }
            }
        }

        $modified = filemtime($jsonFile);

        $tickets[] = [
            'file_base' => $fileBase,
            'nummer' => (string) ($data['nummer'] ?? ''),
            'gezinsnummer' => (string) (
                $data['gezinsnummer'] ?? ''
            ),
            'datum' => (string) ($data['datum'] ?? ''),
            'ontvangers' => implode(', ', $recipientNames),
            'modified' => $modified !== false
                ? $modified
                : 0,
        ];
    }

    usort(
        $tickets,
        static function (array $left, array $right): int {
            $numberComparison =
                (int) $right['nummer']
                <=> (int) $left['nummer'];

            if ($numberComparison !== 0) {
                return $numberComparison;
            }

            return $right['modified'] <=> $left['modified'];
        }
    );

    return $tickets;
}

function escape(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$tickets = loadTickets();
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Geschiedenis Leergeld-bonnen</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #bbb;
            text-align: left;
            vertical-align: top;
        }

        th {
            border-bottom: 2px solid #777;
        }

        .ticket-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .empty {
            padding: 18px 10px;
            color: #555;
        }
    </style>
</head>

<body>
<h1>Geschiedenis Leergeld-bonnen</h1>

<div class="actions">
    <a href="/leergeld/">
        Nieuwe Leergeld-bon
    </a>

    <a href="/">
        Naar home
    </a>
</div>

<table>
    <thead>
    <tr>
        <th>Bonnummer</th>
        <th>Gezinsnummer</th>
        <th>Datum</th>
        <th>Ontvanger(s)</th>
        <th>Acties</th>
    </tr>
    </thead>

    <tbody>
    <?php if ($tickets === []): ?>
        <tr>
            <td colspan="5" class="empty">
                Er zijn nog geen Leergeld-bonnen opgeslagen.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td>
                    <?= escape($ticket['nummer']) ?>
                </td>

                <td>
                    <?= escape($ticket['gezinsnummer']) ?>
                </td>

                <td>
                    <?= escape($ticket['datum']) ?>
                </td>

                <td>
                    <?= escape($ticket['ontvangers']) ?>
                </td>

                <td>
                    <div class="ticket-actions">
                        <a
                            href="/leergeld/ticket.php?ticket=<?= rawurlencode(
                                $ticket['file_base']
                            ) ?>"
                        >
                            Bekijken
                        </a>

                        <a
                            href="/leergeld/download.php?ticket=<?= rawurlencode(
                                $ticket['file_base']
                            ) ?>"
                            target="_blank"
                        >
                            PDF openen
                        </a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>