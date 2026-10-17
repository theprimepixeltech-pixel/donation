FROM php:8.3-cli
WORKDIR /app
# Install the locked Composer dependencies before building this image.
COPY vendor ./vendor
COPY app ./app
COPY public ./public
COPY bin ./bin
RUN chmod -R a+rX app public bin vendor && mkdir -p storage/uploads && chown -R www-data:www-data storage
RUN printf 'display_errors=Off\nlog_errors=On\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/hope.ini
USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/router.php"]
