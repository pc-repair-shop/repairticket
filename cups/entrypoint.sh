#!/bin/sh
set -eu
printer_name="${PRINTER_NAME:-PCRepairShop}"
printer_uri="${PRINTER_URI:-ipp://192.168.1.189:631/ipp/print}"
cupsd
attempt=0
until lpstat -r >/dev/null 2>&1; do
  attempt=$((attempt + 1))
  [ "$attempt" -lt 20 ] || { echo "CUPS kon niet worden gestart." >&2; exit 1; }
  sleep 1
done
lpadmin -p "$printer_name" -E -v "$printer_uri" -m everywhere
cupsenable "$printer_name"
cupsaccept "$printer_name"
pid="$(cat /run/cups/cupsd.pid)"
kill "$pid"
while kill -0 "$pid" 2>/dev/null; do sleep 1; done
exec cupsd -f
