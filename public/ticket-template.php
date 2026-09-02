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
    @page { size: A4 portrait; margin: 13mm 16mm 14mm; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #000; font-family: "DejaVu Sans", sans-serif; font-size: 12pt; }
    table { border-collapse: collapse; width: 100%; }
    .header { margin-bottom: 8mm; }
    .header td { vertical-align: top; }
    .logo { width: 53mm; height: auto; }
    .heading { text-align: center; padding-top: 2mm; }
    .heading h1 { font-size: 22pt; font-weight: normal; margin: 0 0 3mm; }
    .heading p { font-size: 10pt; line-height: 1.45; margin: 0; }
    .top td { border: 1px solid #000; padding: 3mm; font-size: 14pt; }
    .top .spacer { width: 4mm; border: 0; padding: 0; }
    .details { margin-top: 5mm; }
    .details th, .details td { border: 1px solid #000; padding: 2.7mm 3mm; height: 10mm; text-align: left; font-weight: normal; }
    .details th { width: 31%; }
    .computer { margin-top: 5mm; }
    .computer th, .computer td { border: 1px solid #000; padding: 2.7mm 3mm; height: 10mm; text-align: left; font-size: 14pt; font-weight: normal; }
    .computer th { width: 40%; }
    .options-price { margin-top: 1mm; }
    .options-price td { vertical-align: top; }
    .options { width: 48%; }
    .option-title { margin: 0 0 2mm; font-size: 13pt; }
    .option { margin: 0 0 2mm; }
    .box { display: inline-block; width: 4.5mm; height: 4.5mm; border: 1px solid #000; text-align: center; line-height: 4mm; margin-right: 2mm; font-size: 11pt; }
    .repair { margin-top: 5mm; }
    .price-wrap { padding-top: 1mm; }
    .price th, .price td { border: 1px solid #000; padding: 2.7mm 3mm; height: 10mm; text-align: left; font-size: 14pt; font-weight: normal; }
    .price th { width: 47%; }
    .signatures { margin-top: 16mm; }
    .signatures td { width: 49%; border: 1px solid #000; height: 33mm; text-align: center; vertical-align: top; padding: 3mm 2mm; font-size: 11pt; }
    .signatures .gap { width: 2%; border: 0; padding: 0; }
  </style>
</head>
<body>
  <table class="header"><tr>
    <td style="width:42%"><img class="logo" src="data:image/jpeg;base64,<?= $logoBase64 ?>" alt="PC Repair Shop"></td>
    <td class="heading"><h1>Bon</h1><p>Le Bourgetstraat 27 5042 TG Tilburg<br>mail: pcrepairshop-west@beterprojecten-tilburg.nl<br>Tel: 06-28215217</p></td>
  </tr></table>
  <table class="top"><tr><td style="width:67%">Datum: <?= $e($v['datum']) ?></td><td class="spacer"></td><td><?= $e($v['nummer']) ?></td></tr></table>
  <table class="details">
    <tr><th>Naam Klant:</th><td><?= $e($v['naam']) ?></td></tr><tr><th>Adres:</th><td><?= $e($v['adres']) ?></td></tr>
    <tr><th>PC-Woonplaats:</th><td><?= $e($v['postcode_woonplaats']) ?></td></tr><tr><th>Telefoon-GSM:</th><td><?= $e($v['telefoon']) ?></td></tr>
    <tr><th>E-mailadres:</th><td><?= $e($v['email']) ?></td></tr>
  </table>
  <table class="computer">
    <tr><th>Beschrijving computer:</th><td></td></tr><tr><th>Merk:</th><td><?= $e($v['merk']) ?></td></tr>
    <tr><th>Model:</th><td><?= $e($v['model']) ?></td></tr><tr><th>SN:</th><td><?= $e($v['serienummer']) ?></td></tr><tr><th>Zegel:</th><td><?= $e($v['zegel']) ?></td></tr>
  </table>
  <table class="options-price"><tr>
    <td class="options"><p class="option-title">Meedoenregeling</p><p class="option"><span class="box"><?= $checked($v['meedoenregeling'], 'Ja') ?></span>Ja</p><p class="option"><span class="box"><?= $checked($v['meedoenregeling'], 'Nee') ?></span>Nee</p>
      <div class="repair"><p class="option-title">Reparatie</p><p class="option"><span class="box"><?= $checked($v['reparatie'], 'Ja') ?></span>Ja</p><p class="option"><span class="box"><?= $checked($v['reparatie'], 'Nee') ?></span>Nee</p></div></td>
    <td style="width:7%"></td><td class="price-wrap"><table class="price"><tr><th>Prijs:</th><td><?= $v['prijs'] !== '' ? 'EUR ' . $e($v['prijs']) : '' ?></td></tr><tr><th>Totaal:</th><td><?= $v['totaal'] !== '' ? 'EUR ' . $e($v['totaal']) : '' ?></td></tr></table></td>
  </tr></table>
  <table class="signatures"><tr><td>Paraaf Akkoord <?= $e($v['medewerker']) ?>:</td><td class="gap"></td><td>Handtekening klant voor akkoord:</td></tr></table>
</body></html>
    <?php
    return (string) ob_get_clean();
}
