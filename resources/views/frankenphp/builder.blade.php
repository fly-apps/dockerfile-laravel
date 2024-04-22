# OUR NEW and last build stage!
FROM dunglas/frankenphp:static-builder as builder

# Copy our app found in the previous "base" stage
WORKDIR /go/src/app/dist/app
COPY --from=base /var/www/html .

# Build the static binary, be sure to select only the PHP extensions you want
WORKDIR /go/src/app/
RUN ./dist/frankenphp-linux-x86_64 version \
    export FRANKENPHP_VERSION=1.1.2 \
    EMBED=dist/app/ \
    ./build-static.sh

# EXPOSE ports 
EXPOSE 443

ENTRYPOINT ["dist/frankenphp-linux-x86_64", "run", "-c","dist/app/Caddyfile"]