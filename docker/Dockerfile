#FROM push-registry.desarrollo.unlp.edu.ar/desarrollo-cespi/kimkelen:base-latest
FROM registry.gitlab.com/kimkelen/kimkelen:base-latest
RUN mv /usr/local/bin/docker-php-entrypoint /usr/local/bin/original-docker-php-entrypoint
ADD docker/entrypoint /usr/local/bin/docker-php-entrypoint
ADD docker/setenvdefaults /usr/local/bin/setenvdefaults
ADD . /app
RUN rm /app/web/*_dev.php
ENV APACHE_DOCUMENT_ROOT=/app/web/ \
    DB_ENCODING=utf8
ARG REVISION
ENV VERSION=$REVISION
WORKDIR /app
RUN chown -R apache /app/data
COPY config/extra.conf /etc/apache2/conf.d/extra.conf
RUN apk add -U --no-cache apache2-ssl
COPY config/ssl/ssl.conf /etc/apache2/conf.d/ssl.conf
RUN mkdir -p /etc/cloudflare/
COPY config/ssl/epet36misiones.edu.ar.pem /etc/cloudflare/
COPY config/ssl/epet36misiones.edu.ar.key /etc/cloudflare/
