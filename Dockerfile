FROM php:8.3-cli-alpine

# App-Dateien
COPY app/ /app/

# Datenordner außerhalb des Webroots, genug parallele Verbindungen für eine Klasse
ENV BINAERCHAT_DATEN=/data \
    PHP_CLI_SERVER_WORKERS=48
RUN mkdir -p /data

VOLUME /data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]
