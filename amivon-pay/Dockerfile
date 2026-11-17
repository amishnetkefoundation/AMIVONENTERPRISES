FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libcurl4-openssl-dev libsqlite3-dev && docker-php-ext-install pdo_sqlite curl && a2enmod rewrite && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!\${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf
COPY . /var/www/html
RUN mkdir -p /var/www/html/storage && chown -R www-data:www-data /var/www/html/storage && chmod -R 775 /var/www/html/storage
EXPOSE 80
CMD ["apache2-foreground"]
