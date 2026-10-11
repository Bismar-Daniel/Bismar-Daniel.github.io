FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install pdo_mysql curl mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/
COPY deploy/railway-entrypoint.sh /usr/local/bin/hoa-entrypoint
RUN chmod 0755 /usr/local/bin/hoa-entrypoint \
    && chown -R www-data:www-data /var/www/html

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/hoa-entrypoint"]
