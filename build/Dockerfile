# Base image is Google's Optimized Ubuntu Image

ARG IMAGE=gcr.io/ecommerce-platform-211419/magento-stage-primer:latest
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

# Temporary add to test mass import of images
RUN mkdir -p $MAGENTO_DIR/var/import/images && gsutil -q -m rsync -r -u gs://magento-image-repo/import/ $MAGENTO_DIR/var/import/images ; exit 0

RUN git fetch --all
RUN git reset --hard
RUN git pull

RUN composer install

RUN ln -s /var/www/html/magento2/vendor/snowdog/frontools/ /var/www/html/magento2/tools

RUN php /var/www/html/magento2/bin/magento setup:di:compile
RUN rm /var/www/html/magento2/tools/yarn.lock && cd /var/www/html/magento2/tools && npm install && npm rebuild node-sass && gulp clean -f /var/www/html/magento2/tools/gulpfile.esm.js && gulp styles -f /var/www/html/magento2/tools/gulpfile.esm.js

# Move env.php to avoid Magento reading it
RUN mv /var/www/html/magento2/app/etc/env.php /var/www/html/magento2/app/etc/__env.php
RUN php /var/www/html/magento2/bin/magento setup:static-content:deploy -f
# Move env.php back
RUN mv /var/www/html/magento2/app/etc/__env.php /var/www/html/magento2/app/etc/env.php

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

RUN find var pub/static pub/media app/etc -type f -exec chmod g+w {} +
RUN find var pub/static pub/media app/etc -type d -exec chmod g+ws {} +

# Run entrypoint file
COPY ondemand-entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]
