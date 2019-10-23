# Base image is Google's Optimized Ubuntu Image

ARG IMAGE=gcr.io/ecommerce-platform-211419/magento-dev-primer:latest
FROM $IMAGE
ENTRYPOINT []

ENV NVM_DIR /home/magento/.nvm
ENV MAGENTO_DIR /var/www/html/magento2

LABEL company="The Scotts MiracleGro Company"
LABEL maintainer="rick.feldschau@scotts.com"

USER root
WORKDIR $MAGENTO_DIR
RUN chown -R magento:www-data .git

USER magento
WORKDIR $MAGENTO_DIR

RUN ls -la /var/www/html/magento2/vendor/snowdog/frontools/
RUN git fetch --all
RUN git reset --hard
RUN git pull
RUN ls -la /var/www/html/magento2/vendor/snowdog/frontools/
RUN composer install
RUN ls -la /var/www/html/magento2/vendor/snowdog/frontools/

USER root

RUN REV=$(git rev-parse HEAD)

RUN \
	curl -X POST 'https://api.newrelic.com/v2/applications/203206085/deployments.json' \
     -H 'X-Api-Key:abb8c9f78734ce8444b721b2ad8f0474899a8932522d942' -i \
     -H 'Content-Type: application/json' \
     -d \
	'{ "deployment": { "revision": "$REV" } }'

# Expose Ports for Apache
EXPOSE 80/tcp 443/tcp

RUN find /var/www/html/magento2/pub/media /var/www/html/magento2/pub/static /var/www/html/magento2/app/etc /var/www/html/magento2/var /var/www/html/magento2/generated -print0 | xargs -0 chown magento:www-data
RUN find /var/www/html/magento2/pub/media /var/www/html/magento2/pub/static /var/www/html/magento2/app/etc /var/www/html/magento2/var /var/www/html/magento2/generated -print0 | xargs -0 chmod 777

# Run entrypoint file
COPY ondemand-entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

RUN ls -la /var/www/html/magento/tools
RUN ls -la /var/www/html/magento2/vendor/snowdog/frontools/
# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]
