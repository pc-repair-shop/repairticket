<?php
declare(strict_types=1);

function renderTicketHtml(array $v, string $logoBase64): string
{
    $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $checked = static fn (string $actual, string $expected): string => $actual === $expected ? '&#10003;' : '';

    ob_start();
    ?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <style>
    @page { size: A4 portrait; margin: 9mm 12mm 10mm; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #000; font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; line-height: 1.25; }
    table { border-collapse: collapse; width: 100%; }
    .header { margin-bottom: 4mm; }
    .header td { vertical-align: top; }
    .logo { width: 43mm; height: auto; }
    .heading { text-align: right; }
    .heading h1 { font-size: 18pt; margin: 0 0 1.5mm; }
    .heading p { font-size: 8.5pt; line-height: 1.3; margin: 0; }
    .top { border-top: .5pt solid #777; border-bottom: .5pt solid #777; }
    .top td { padding: 2mm 0; font-size: 10pt; }
    .top .number { text-align: right; font-weight: bold; }
    .section-title { margin: 4mm 0 1mm; font-size: 10pt; font-weight: bold; }
    .details td { padding: 1.5mm 2mm 1.5mm 0; vertical-align: top; border-bottom: .35pt solid #bbb; }
    .details .label { width: 19%; color: #333; }
    .details .value { width: 31%; }
    .computer td { padding: 1.5mm 2mm 1.5mm 0; vertical-align: top; border-bottom: .35pt solid #bbb; }
    .computer .label { width: 14%; color: #333; }
    .computer .value { width: 36%; }
    .items th, .items td { padding: 1.4mm 1.5mm; text-align: left; border-bottom: .35pt solid #aaa; }
    .items th { font-size: 8.5pt; color: #333; }
    .items .number { text-align: right; white-space: nowrap; }
    .items tr { page-break-inside: avoid; }
    .description { margin-top: 1mm; min-height: 25mm; padding: 2.5mm; border: .5pt solid #777; white-space: pre-wrap; overflow-wrap: break-word; }
    .options-price { margin-top: 4mm; page-break-inside: avoid; }
    .options-price td { vertical-align: top; }
    .options { width: 55%; }
    .option-line { margin: 0 0 2mm; }
    .option-title { display: inline-block; width: 34mm; font-weight: bold; }
    .box { display: inline-block; width: 4mm; height: 4mm; border: .5pt solid #555; text-align: center; line-height: 3.5mm; margin: 0 1mm 0 2mm; font-size: 9pt; }
    .price th, .price td { border-bottom: .35pt solid #999; padding: 1.5mm 0; text-align: left; font-weight: normal; }
    .price th { width: 40%; }
    .signatures { margin-top: 7mm; page-break-inside: avoid; }
    .signatures td { width: 48%; height: 22mm; vertical-align: top; padding: 2mm 0; border-top: .5pt solid #777; font-size: 8.5pt; }
    .signatures .gap { width: 4%; border: 0; }
  </style>
</head>
<body>
  <table class="header"><tr>
    <td style="width:42%"><img class="logo" src="data:image/jpeg;base64,<?= $logoBase64 ?>" alt="PC Repair Shop"></td>
    <td class="heading"><h1>Bon</h1><p>Le Bourgetstraat 27 5042 TG Tilburg<br>mail: pcrepairshop-west@beterprojecten-tilburg.nl<br>Tel: 06-28215217</p></td>
  </tr></table>
  <table class="top"><tr><td>Datum: <?= $e($v['datum']) ?></td><td class="number">Bonnummer: <?= $e($v['nummer']) ?></td></tr></table>
  <div class="section-title">Klantgegevens</div>
  <table class="details">
    <tr><td class="label">Naam:</td><td class="value"><?= $e($v['naam']) ?></td><td class="label">Telefoon:</td><td class="value"><?= $e($v['telefoon']) ?></td></tr>
    <tr><td class="label">Adres:</td><td class="value"><?= $e($v['adres']) ?></td><td class="label">E-mail:</td><td class="value"><?= $e($v['email']) ?></td></tr>
    <tr><td class="label">Postcode/plaats:</td><td colspan="3"><?= $e($v['postcode_woonplaats']) ?></td></tr>
  </table>
  <div class="section-title">Product/apparaat</div>
  <table class="computer">
    <tr><td class="label">Merk:</td><td class="value"><?= $e($v['merk']) ?></td><td class="label">Model:</td><td class="value"><?= $e($v['model']) ?></td></tr>
    <tr><td class="label">Serienummer:</td><td class="value"><?= $e($v['serienummer']) ?></td><td class="label">Zegel:</td><td class="value"><?= $e($v['zegel']) ?></td></tr>
  </table>
  <?php if ($v['extra_artikelen'] !== []): ?>
  <div class="section-title">Extra artikelen</div>
  <table class="items">
    <thead><tr><th>Omschrijving</th><th class="number">Aantal</th><th class="number">Per stuk</th><th class="number">Subtotaal</th></tr></thead>
    <tbody>
    <?php foreach ($v['extra_artikelen'] as $item): ?>
      <tr>
        <td><?= $e($item['omschrijving']) ?></td>
        <td class="number"><?= $e((string) $item['aantal']) ?></td>
        <td class="number">EUR <?= $e($item['prijs']) ?></td>
        <td class="number">EUR <?= $e($item['subtotaal']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  <div class="section-title">Omschrijving</div>
  <div class="description"><?= $e($v['omschrijving']) ?></div>
  <table class="options-price"><tr>
    <td class="options">
      <p class="option-line"><span class="option-title">Meedoenregeling</span><span class="box"><?= $checked($v['meedoenregeling'], 'Ja') ?></span>Ja <span class="box"><?= $checked($v['meedoenregeling'], 'Nee') ?></span>Nee</p>
      <p class="option-line"><span class="option-title">Reparatie</span><span class="box"><?= $checked($v['reparatie'], 'Ja') ?></span>Ja <span class="box"><?= $checked($v['reparatie'], 'Nee') ?></span>Nee</p>
    </td>
    <td style="width:5%"></td><td><table class="price"><tr><th>Prijs:</th><td><?= $v['prijs'] !== '' ? 'EUR ' . $e($v['prijs']) : '' ?></td></tr><tr><th>Totaal:</th><td><?= $v['totaal'] !== '' ? 'EUR ' . $e($v['totaal']) : '' ?></td></tr></table></td>
  </tr></table>
  <table class="signatures"><tr><td>Paraaf akkoord medewerker:</td><td class="gap"></td><td>Handtekening klant voor akkoord:</td></tr></table>
</body></html>
    <?php
    return (string) ob_get_clean();
}
