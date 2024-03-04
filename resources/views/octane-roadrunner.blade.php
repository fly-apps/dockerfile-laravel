RUN rm -rf /etc/supervisor/conf.d/fpm.conf; \
    mv /etc/supervisor/octane-rr.conf /etc/supervisor/conf.d/octane-rr.conf; \
    if [ -f ./vendor/bin/rr ]; then ./vendor/bin/rr get-binary; fi; \
    rm -f .rr.yaml; \
    rm /etc/nginx/sites-enabled/default; \
    ln -sf /etc/nginx/sites-available/default-octane /etc/nginx/sites-enabled/default;
