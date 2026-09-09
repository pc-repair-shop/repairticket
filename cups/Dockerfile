FROM debian:13-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cups \
        cups-client \
        cups-filters \
        cups-ipp-utils \
        ghostscript \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /run/cups /var/spool/cups /var/log/cups

COPY cupsd.conf /etc/cups/cupsd.conf
COPY entrypoint.sh /usr/local/bin/cups-entrypoint.sh

RUN chmod +x /usr/local/bin/cups-entrypoint.sh

EXPOSE 631

VOLUME ["/var/spool/cups"]

ENTRYPOINT ["/usr/local/bin/cups-entrypoint.sh"]