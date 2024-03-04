RUN rm -rf /etc/supervisor/conf.d/fpm.conf; \
    mv /etc/supervisor/octane-swoole.conf /etc/supervisor/conf.d/octane-swoole.conf; \
    rm /etc/nginx/sites-enabled/default; \
    ln -sf /etc/nginx/sites-available/default-octane /etc/nginx/sites-enabled/default;
