# Base image is Google's Optimized Ubuntu Image

ARG BRANCH=staging
ARG IMAGE=gcr.io/ecommerce-platform-211419/magento-stage-primer:latest
FROM $IMAGE
ARG BRANCH

ENV NVM_DIR /home/magento/.nvm
ENV MAGENTO_DIR /var/www/html/magento2

LABEL company="The Scotts MiracleGro Company"
LABEL maintainer="rick.feldschau@scotts.com"

USER root
WORKDIR $MAGENTO_DIR
RUN chown -R magento:www-data .git

RUN if [ -d /usr/local/lib/node_modules ]; then chown -R magento /usr/local/lib/node_modules; fi

RUN npm install -g npm

USER magento
WORKDIR $MAGENTO_DIR

# Temporary add to test mass import of images
RUN mkdir -p $MAGENTO_DIR/var/import/images && gsutil -q -m rsync -r -u gs://magento-image-repo/import/ $MAGENTO_DIR/var/import/images ; exit 0

RUN git config --global user.name "Git User" && git config --global user.email "user@example.com" && git fetch --all && git reset --hard origin/${BRANCH} && git pull

# Add Akeneo to known hosts
RUN ssh-keyscan -p 443 distribution.akeneo.com >> ~/.ssh/known_hosts

RUN composer install
RUN ln -s /var/www/html/magento2/vendor/snowdog/frontools/ /var/www/html/magento2/tools

# Set developer Mode if NOT in production or release-candidate or hotfixes
RUN if [ "$BRANCH" != "master" ] && [ "$BRANCH" != "release-candidate" ] && [ "$BRANCH" != "hotfixes" ]; then bin/magento deploy:mode:set developer && bin/magento setup:config:set --enable-debug-logging=true && sed -i "s/display_errors.*/display_errors = on/" php.ini; fi

# Temporary Patch for Inventory Management (Can remove after 2.3.4)
COPY msi-numeric-skus.diff /tmp/msi-numeric-skus.diff
WORKDIR $MAGENTO_DIR/vendor
RUN git apply /tmp/msi-numeric-skus.diff; exit 0

WORKDIR $MAGENTO_DIR

USER root

RUN cd tools && rm -rf node_modules && npm install

RUN find var pub/static pub/media app/etc -type f -exec chmod g+w {} + && find var pub/static pub/media app/etc -type d -exec chmod g+ws {} +

#Swapping config w/wo scopes to create static files during docker build
COPY scopes.config.php $MAGENTO_DIR/app/etc/scopes.config.php
RUN mv $MAGENTO_DIR/app/etc/env.php $MAGENTO_DIR/app/etc/env.php.copy
RUN mv -f $MAGENTO_DIR/app/etc/config.php $MAGENTO_DIR/app/etc/modules.config.php
RUN php -r '$modules = include "/var/www/html/magento2/app/etc/modules.config.php"; $scopes = include "/var/www/html/magento2/app/etc/scopes.config.php"; $config = array_merge($modules, $scopes); file_put_contents("/var/www/html/magento2/app/etc/config.php", "<?php\nreturn " . var_export($config, true) . ";\n");'

RUN bin/magento setup:di:compile
RUN rm /var/www/html/magento2/tools/yarn.lock ; cd /var/www/html/magento2/tools && npm rebuild node-sass && gulp clean -f /var/www/html/magento2/tools/gulpfile.esm.js && gulp styles --prod -f /var/www/html/magento2/tools/gulpfile.esm.js
RUN bin/magento setup:static-content:deploy -f

## Move original configs to their place, to avoid config import into end system
RUN mv $MAGENTO_DIR/app/etc/env.php.copy $MAGENTO_DIR/app/etc/env.php
RUN mv -f $MAGENTO_DIR/app/etc/modules.config.php $MAGENTO_DIR/app/etc/config.php

RUN chown -R magento:www-data var/ app/etc/ pub/media/

# Run entrypoint file
COPY ondemand-entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]

# Expose Ports for Apache
EXPOSE 80/tcp 443/tcp
