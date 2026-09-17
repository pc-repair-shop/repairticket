<?php
declare(strict_types=1);

function renderLeergeldTicketHtml(
    array $values,
    string $logoBase64
): string {
    $escape = static fn (mixed $value): string => htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    ob_start();
    ?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">

    <style>
        @page {
            size: A4 portrait;
            margin: 9mm 12mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.5pt;
            line-height: 1.25;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            margin-bottom: 4mm;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 43mm;
            height: auto;
        }

        .heading {
            text-align: right;
        }

        .heading h1 {
            margin: 0 0 1.5mm;
            font-size: 18pt;
        }

        .heading p {
            margin: 0;
            font-size: 8.5pt;
            line-height: 1.3;
        }

        .top {
            border-top: .5pt solid #777;
            border-bottom: .5pt solid #777;
        }

        .top td {
            padding: 2mm 0;
            font-size: 10pt;
        }

        .top .number {
            text-align: right;
            font-weight: bold;
        }

        .recipient {
            margin-top: 4mm;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0 0 1mm;
            font-size: 10pt;
            font-weight: bold;
        }

        .details td {
            padding: 1.5mm 2mm 1.5mm 0;
            vertical-align: top;
            border-bottom: .35pt solid #bbb;
        }

        .details .label {
            width: 19%;
            color: #333;
        }

        .details .value {
            width: 31%;
        }

        .computer {
            margin-top: 2mm;
        }

        .computer td {
            padding: 1.5mm 2mm 1.5mm 0;
            vertical-align: top;
            border-bottom: .35pt solid #bbb;
        }

        .computer .label {
            width: 19%;
            color: #333;
        }

        .computer .value {
            width: 31%;
        }

        .signatures {
            margin-top: 7mm;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 48%;
            height: 20mm;
            padding: 2mm 0;
            vertical-align: top;
            border-top: .5pt solid #777;
            font-size: 8.5pt;
        }

        .signatures .gap {
            width: 4%;
            border: 0;
        }
    </style>
</head>

<body>
    <table class="header">
        <tr>
            <td style="width:42%">
                <img
                    class="logo"
                    src="data:image/jpeg;base64,<?= $logoBase64 ?>"
                    alt="PC Repair Shop"
                >
            </td>

            <td class="heading">
                <h1>Bon Stichting Leergeld</h1>

                <p>
                    Le Bourgetstraat 27 5042 TG Tilburg<br>
                    mail: pcrepairshop-west@beterprojecten-tilburg.nl<br>
                    Tel: 06-28215217
                </p>
            </td>
        </tr>
    </table>

    <table class="top">
        <tr>
            <td>
                Datum: <?= $escape($values['datum']) ?>
            </td>

            <td>
                Gezinsnummer:
                <strong><?= $escape($values['gezinsnummer']) ?></strong>
            </td>

            <td class="number">
                Bonnummer: <?= $escape($values['nummer']) ?>
            </td>
        </tr>
    </table>

    <?php foreach ($values['ontvangers'] as $index => $recipient): ?>
        <div class="recipient">
            <div class="section-title">
                Klant en computer <?= $index + 1 ?>
            </div>

            <table class="details">
                <tr>
                    <td class="label">Naam:</td>
                    <td class="value">
                        <?= $escape($recipient['naam']) ?>
                    </td>

                    <td class="label">Telefoon:</td>
                    <td class="value">
                        <?= $escape($recipient['telefoon']) ?>
                    </td>
                </tr>

                <tr>
                    <td class="label">Adres:</td>
                    <td class="value">
                        <?= $escape($recipient['adres']) ?>
                    </td>

                    <td class="label">Postcode/plaats:</td>
                    <td class="value">
                        <?= $escape($recipient['postcode_woonplaats']) ?>
                    </td>
                </tr>
            </table>

            <table class="computer">
                <tr>
                    <td class="label">Merk:</td>
                    <td class="value">
                        <?= $escape($recipient['merk']) ?>
                    </td>

                    <td class="label">Model:</td>
                    <td class="value">
                        <?= $escape($recipient['model']) ?>
                    </td>
                </tr>

                <tr>
                    <td class="label">Serienummer:</td>
                    <td colspan="3">
                        <?= $escape($recipient['serienummer']) ?>
                    </td>
                </tr>
            </table>

            <table class="signatures">
                <tr>
                    <td>Paraaf akkoord medewerker:</td>
                    <td class="gap"></td>
                    <td>Paraaf ontvanger laptop:</td>
                </tr>
            </table>
        </div>
    <?php endforeach; ?>
</body>
</html>
    <?php

    return (string) ob_get_clean();
}