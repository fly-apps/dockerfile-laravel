RUN rm -rf /etc/supervisor/conf.d/fpm.conf; \
    mv /etc/supervisor/octane-franken.conf /etc/supervisor/conf.d/octane-franken.conf; \
    rm -f frankenphp; \
    php artisan octane:install --no-interaction --server=frankenphp; \
    rm /etc/nginx/sites-enabled/default; \
    ln -sf /etc/nginx/sites-available/default-octane /etc/nginx/sites-enabled/default;
