# Reparatiebon PC Repair Shop

Interne webapp voor het aanmaken, opslaan, bekijken en afdrukken van reparatie- en verkoopbonnen.

De applicatie bestaat uit twee Docker-containers:

- `repair-ticket`: Nginx, PHP-FPM en Dompdf voor het formulier en de PDF-bestanden;
- `cups`: CUPS voor het versturen van de PDF naar de netwerkprinter.

De Dockerimages worden automatisch door GitHub Actions gebouwd en gepubliceerd op Docker Hub.

## Vereisten

- Een Unraid-server met Docker Compose;
- netwerktoegang vanaf Unraid naar de printer;
- Epson EcoTank ET-4800 op `192.168.1.189`;
- TCP-poort `631` van de printer bereikbaar;
- toegang tot de private Docker Hub-image `pieterdoardo/pcrepair-ticket-app`.

## Eenmalige installatie op Unraid

### 1. Installatiemap maken

```bash
mkdir -p /mnt/user/appdata/pcrepair-ticket
cd /mnt/user/appdata/pcrepair-ticket
```

Plaats vervolgens deze twee bestanden uit de map `deploy` in deze installatiemap:

```text
compose.yml
.env.example
```

### 2. Omgevingsbestand aanmaken

```bash
cp .env.example .env
```

Controleer de inhoud van `.env`:

```dotenv
IMAGE_TAG=stable
APP_PORT=8080

PRINTER_NAME=PCRepairShop
PRINTER_URI=ipp://192.168.1.189:631/ipp/print

DATA_PATH=/mnt/user/appdata/pcrepair-ticket/data
```

Het printer-IP moet statisch gereserveerd zijn. Pas `PRINTER_URI` aan wanneer het IP-adres verandert.

### 3. Inloggen bij Docker Hub

De app-image is privé. Log daarom eenmalig in met een Docker Hub-account dat toegang heeft tot de repository:

```bash
docker login
```

Gebruik bij voorkeur een Docker Hub access token als wachtwoord.

### 4. Applicatie starten

```bash
docker compose up -d --pull always
```

Open vervolgens:

```text
http://IP-VAN-UNRAID:8080
```

## Applicatie bijwerken

GitHub Actions publiceert de goedgekeurde productie-images met de tag `stable`. Een update installeren kan met:

```bash
cd /mnt/user/appdata/pcrepair-ticket
docker compose up -d --pull always
```

Docker haalt nieuwe images op en maakt alleen gewijzigde containers opnieuw aan. Opgeslagen bonnen blijven behouden.

## Status controleren

```bash
docker compose ps
```

Beide containers moeten actief zijn. De CUPS-container moet uiteindelijk `healthy` tonen.

De CUPS-log bekijken:

```bash
docker compose logs cups
```

De log live volgen tijdens een printtest:

```bash
docker compose logs -f cups
```

Bij een geaccepteerde opdracht verschijnt bijvoorbeeld:

```text
Printopdracht gestart: 0080.pdf
request id is PCRepairShop-1 (1 file(s))
Printopdracht geaccepteerd: 0080.pdf
```

## Printer controleren

Controleren of de printerqueue bestaat:

```bash
docker compose exec cups lpstat -p -v
```

Openstaande en eerdere printopdrachten bekijken:

```bash
docker compose exec cups lpstat -W all -o
```

Een bestaande bon handmatig afdrukken:

```bash
docker compose exec cups lp -d PCRepairShop /data/tickets/0080.pdf
```

Vervang `0080.pdf` door een bestaand bonnummer.

## Opslag en back-up

Alle blijvende gegevens staan op de Unraid-server in:

```text
/mnt/user/appdata/pcrepair-ticket/data
```

Belangrijke locaties:

```text
data/tickets/       opgeslagen PDF-bonnen
data/print-queue/   bonnen die nog verwerkt moeten worden
data/print-failed/  printopdrachten die niet geaccepteerd zijn
data/counter.txt    teller voor het volgende bonnummer
```

Deze map is via een bind mount gekoppeld aan beide containers. De gegevens blijven bestaan wanneer containers of images worden vervangen.

Neem de volledige map regelmatig mee in de normale Unraid-back-up. Verwijder of wijzig `reparatie.txt` niet handmatig zolang de applicatie draait.

## Stoppen en opnieuw starten

Stoppen:

```bash
docker compose down
```

Opnieuw starten:

```bash
docker compose up -d --pull always
```

Gebruik niet zomaar `docker compose down -v`: daarmee wordt het interne CUPS-volume verwijderd. Voor een gewone update is dit niet nodig.

## Terugrollen naar een eerdere release

Wijzig in `.env` bijvoorbeeld:

```dotenv
IMAGE_TAG=1.0.0
```

Voer daarna uit:

```bash
docker compose up -d --pull always
```

Om weer de actuele productieversie te gebruiken, zet `IMAGE_TAG` terug op `stable` en voer hetzelfde commando uit.

## Lokale ontwikkeling

De `compose.yml` in de hoofdmap bouwt de images vanuit de lokale broncode:

```bash
docker compose up -d --build
```

De productieconfiguratie in `deploy/compose.yml` gebruikt uitsluitend de vooraf gebouwde Docker Hub-images. Gebruik op Unraid daarom altijd de bestanden uit `deploy`.

Lokale wijzigingen testen met de productieconfiguratie kan vanuit de map `deploy`. Gebruik daarvoor in de lokale `.env`:

```dotenv
DATA_PATH=../data
```

Start vervolgens:

```bash
docker compose up -d --pull always
```

Het bestand `.env` en de map `data` mogen niet naar GitHub worden gecommit, omdat ze lokale configuratie en klantgegevens kunnen bevatten.
