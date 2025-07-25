
#!/usr/bin/env sh

# Run user scripts, if they exist
for f in /var/www/html/.fly/scripts/*.sh; do
    # Bail out this loop if any script exits with non-zero status code
    bash "$f" -e
done

# By default, the .fly/scripts/caches.sh is going to get executed above
# So, set proper permissions for generated files from the caching commands ran in caches.sh:
chown -R www-data:www-data /var/www/html/boostrap
chown -R www-data:www-data /var/www/html/storage/framework

if [ $# -gt 0 ]; then
    # If we passed a command, run it as root
    exec "$@"
else
    exec supervisord -c /etc/supervisor/supervisord.conf
fi