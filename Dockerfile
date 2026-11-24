FROM php:8.3-apache

# App-Dateien
COPY app/ /var/www/html/

# Datenordner außerhalb des Webroots
ENV BINAERCHAT_DATEN=/data
RUN mkdir -p /data && chown www-data:www-data /data \
 && echo "ServerName binaerchat" > /etc/apache2/conf-enabled/servername.conf

VOLUME /data
EXPOSE 80
