FROM boxedcode/alpine-nginx-php-fpm:latest
COPY --from=composer:2.1.8 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
# remove unnecessary folder from parent image
RUN rm -rf localhost
ADD . ./
# do not let php-fpm wipe out environment vars
RUN echo "clear_env = no" >> /usr/etc/php-fpm.conf
RUN composer install