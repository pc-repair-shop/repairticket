<?php
declare(strict_types=1);

const TICKET_DIR = '/data/tickets';

$tickets = [];
foreach (glob(TICKET_DIR . '/*.pdf') ?: [] as $file) {
    $number = pathinfo($file, PATHINFO_FILENAME);
    if (!preg_match('/^[0-9]{4,}$/', $number)) {
        continue;
    }

    $modified = filemtime($file);
    $tickets[] = [
        'number' => $number,
        'modified' => $modified !== false ? $modified : 0,
        'size' => filesize($file),
    ];
}

usort($tickets, static function (array $a, array $b): int {
    return $b['number'] <=> $a['number'];
});
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Oude bonnen</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 32px auto; padding: 0 18px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px 8px; border-bottom: 1px solid #aaa; text-align: left; }
    th { border-bottom: 2px solid #555; }
    .number { font-weight: bold; }
  </style>
</head>
<body>
  <h1>Oude bonnen</h1>
  <p><a href="/">Nieuwe bon maken</a></p>

  <?php if ($tickets === []): ?>
    <p>Er zijn nog geen opgeslagen bonnen.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Bonnummer</th><th>Opgeslagen</th><th>Bestandsgrootte</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($tickets as $ticket): ?>
        <tr>
          <td class="number"><?= htmlspecialchars($ticket['number']) ?></td>
          <td><?= date('d-m-Y H:i', $ticket['modified']) ?></td>
          <td><?= number_format(((int) $ticket['size']) / 1024, 1, ',', '.') ?> KB</td>
          <td><a href="/ticket.php?ticket=<?= rawurlencode($ticket['number']) ?>">Bekijken of printen</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
