#!/bin/sh
set -eu
printer_name="${PRINTER_NAME:-PCRepairShop}"
printer_uri="${PRINTER_URI:-ipp://192.168.1.189:631/ipp/print}"
print_queue="/data/print-queue"
print_failed="/data/print-failed"

mkdir -p "$print_queue" "$print_failed"

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

print_worker() {
  while true; do
    if lpstat -r >/dev/null 2>&1; then
      for pdf_file in "$print_queue"/*.pdf; do
        [ -f "$pdf_file" ] || continue

        file_name="$(basename "$pdf_file")"
        echo "Printopdracht gestart: $file_name"

        if lp -d "$printer_name" "$pdf_file"; then
          rm -f "$pdf_file"
          echo "Printopdracht geaccepteerd: $file_name"
        else
          mv "$pdf_file" "$print_failed/$file_name"
          echo "Printopdracht mislukt: $file_name" >&2
        fi
      done
    fi

    sleep 2
  done
}

print_worker &
exec cupsd -f
