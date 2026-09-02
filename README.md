# Reparatiebon PC Repair Shop

Lokale reparatiebon-app met Nginx, PHP-FPM, Dompdf en een aparte CUPS-printserver.

## Starten

```bash
docker compose up -d --build
```

Open daarna `http://IP-VAN-UNRAID:8080`.

De Epson staat standaard op `ipp://192.168.1.189:631/ipp/print`, met wachtrijnaam
`PCRepairShop` en één exemplaar. Deze waarden zijn aanpasbaar in `compose.yml`.

PDF-bonnen blijven staan in `data/tickets/`. Een printfout verwijdert de PDF niet.

De CUPS-configuratie wordt bij iedere containerstart opnieuw opgebouwd uit de waarden in
`compose.yml`. Daardoor blijven oude of mislukte printerinstellingen niet hangen na een update.

## Controle en handmatige test

```bash
docker compose ps
docker compose logs cups
docker compose exec cups lpstat -p -d
docker compose exec repair-ticket lp -h cups:631 -d PCRepairShop /data/tickets/0073.pdf
```
