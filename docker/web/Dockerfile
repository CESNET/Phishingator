# Stage 1: Build frontend assets
FROM node:24-trixie-slim AS frontend

WORKDIR /app

COPY src/package.json src/package-lock.json ./
RUN npm ci

COPY src/ ./
RUN npm run build

# Stage 2: Build PHP application image
FROM php:8.3-apache-trixie

ENV TZ="Europe/Prague" \
    APP_ROOT="/var/www/phishingator/" \
    APACHE_DOCUMENT_ROOT="/var/www/phishingator/public/" \
    LDAPTLS_CACERT="/etc/ssl/certs/ldap.crt" \
    LDAPTLS_REQCERT="demand"
ARG SMTP_HOST

RUN apt-get update && \
    apt-get install -y --no-install-recommends sudo libapache2-mod-auth-openidc msmtp vim && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -u 5000 phishingator

# PHP
RUN curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql ldap imap zip gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Apache
COPY docker/web/phishingator.conf /etc/apache2/sites-enabled/000-default.conf
COPY docker/web/security.conf /etc/apache2/conf-enabled/
RUN chown -R phishingator:phishingator /etc/apache2/sites-available/ /etc/apache2/sites-enabled/ /var/lib/apache2/site/ && \
    a2enmod rewrite headers

# MSMTP
COPY docker/web/mail.ini /usr/local/etc/php/conf.d/mail.ini
COPY docker/web/msmtprc /etc/msmtprc
RUN sed -i "s/\${SMTP_HOST}/${SMTP_HOST}/g" /etc/msmtprc && \
    touch /var/log/msmtp.log && \
    chown phishingator /var/log/msmtp.log

WORKDIR ${APP_ROOT}

# Composer
COPY --chown=phishingator:phishingator src/composer.json src/composer.lock ./

RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader && \
    chown -R phishingator:phishingator "${APP_ROOT}/vendor"

# Phishingator
COPY --chown=phishingator:phishingator src/ ./

COPY --chown=phishingator:phishingator --from=frontend /app/public/assets/css/ "${APP_ROOT}/public/assets/css/"
COPY --chown=phishingator:phishingator --from=frontend /app/public/assets/vendor/ "${APP_ROOT}/public/assets/vendor/"

USER phishingator