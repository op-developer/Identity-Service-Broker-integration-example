FROM dwchiang/nginx-php-fpm:8.2.9-fpm-bookworm-nginx-1.25.2

COPY --from=composer:2.1.8 /usr/bin/composer /usr/bin/composer
# copy nxing.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www
ADD . ./

RUN composer install
