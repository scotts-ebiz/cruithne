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

RUN git fetch --all
RUN git reset --hard
RUN git pull
RUN composer install

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

RUN find var generated pub/static pub/media app/etc -type f -exec chmod g+w {} + 
RUN find var generated pub/static pub/media app/etc -type d -exec chmod g+ws {} +



# RUN find /var/www/html/magento2/pub/media /var/www/html/magento2/pub/static /var/www/html/magento2/app/etc /var/www/html/magento2/var /var/www/html/magento2/generated | xargs chown magento:www-data
# RUN find /var/www/html/magento2/pub/media /var/www/html/magento2/pub/static /var/www/html/magento2/app/etc /var/www/html/magento2/var /var/www/html/magento2/generated | xargs chmod 777

# Run entrypoint file
COPY ondemand-entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]
