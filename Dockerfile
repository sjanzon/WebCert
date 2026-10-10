FROM php:8.3-apache

# Die openssl-Erweiterung ist im offiziellen PHP-Image bereits enthalten.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    WEBCERT_DATA=/data

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'date.timezone=${TZ}\nexpose_php=Off\n' > "$PHP_INI_DIR/conf.d/webcert.ini" \
    && mkdir -p /data && chown www-data:www-data /data && chmod 700 /data

ENV TZ=Europe/Berlin

COPY --chown=www-data:www-data public/ /var/www/html/public/
COPY --chown=www-data:www-data src/ /var/www/html/src/

# CA, Zertifikate und Passwort-Hash – unbedingt als Volume persistieren
VOLUME /data

EXPOSE 80
