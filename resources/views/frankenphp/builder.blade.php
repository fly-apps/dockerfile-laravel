# Builder stage to create frankenphp binary containing our embedded app
FROM dunglas/frankenphp:static-builder as builder

# Copy our app found in the previous "base" stage
WORKDIR /go/src/app/dist/app
COPY --from=base /var/www/html .

# Build the static binary, be sure to select only the PHP extensions you want
WORKDIR /go/src/app/
RUN EMBED=dist/app/ \
    FRANKENPHP_VERSION=1.1.2 \
    PHP_EXTENSIONS=bcmath,cli,common,curl,gd,intl,mbstring,mysql,pgsql,redis,soap,sqlite3,xml,zip,swoole,fpm \
    ./build-static.sh

# Last runner stage, to only contain and run our generated binary from builder stage
FROM dunglas/frankenphp AS runner

# Replace the official binary by the one contained your custom modules
COPY --from=builder /go/src/app/dist/frankenphp-linux-x86_64 /usr/local/bin/frankenphp

# EXPOSE ports 
EXPOSE 8080

# Start app
ENTRYPOINT ["/usr/local/bin/frankenphp", "php-server", "--listen",":8080"] 
