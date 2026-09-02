FROM alpine:3.22
RUN apk add --no-cache cups cups-client cups-filters ghostscript && mkdir -p /run/cups /var/spool/cups /var/log/cups
COPY cupsd.conf /etc/cups/cupsd.conf
COPY entrypoint.sh /usr/local/bin/cups-entrypoint.sh
RUN chmod +x /usr/local/bin/cups-entrypoint.sh
EXPOSE 631
VOLUME ["/var/spool/cups"]
ENTRYPOINT ["/usr/local/bin/cups-entrypoint.sh"]
