# Base image is Google's Optimized Ubuntu Image

ARG IMAGE=gcr.io/ecommerce-platform-211419/magento-test-primer:latest
FROM $IMAGE
ENTRYPOINT []

ENV NVM_DIR /home/magento/.nvm
ENV MAGENTO_DIR /var/www/html/magento2

LABEL company="The Scotts MiracleGro Company"
LABEL maintainer="rick.feldschau@scotts.com"

USER root
WORKDIR $MAGENTO_DIR
RUN chmod -R 777 /var/lib/php/sessions

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list

# I was reinstalling the nodejs and yarn (will fix in main Dockerfile soon)
# Removed php-redis so local doesn't try to use it for
RUN apt-get update && apt-get remove -y nodejs yarn php-redis && apt-get install -y nodejs yarn

RUN cd $MAGENTO_DIR/tools/ && yarn install

USER magento
WORKDIR $MAGENTO_DIR

RUN git fetch --all
RUN git reset --hard origin/staging
RUN git checkout staging
RUN composer install

COPY local.phpinfo.php $MAGENTO_DIR/pub/phpinfo.php

# Using the local.htacess and php.ini to overwride the repo
COPY local.htaccess $MAGENTO_DIR/.htaccess
COPY local.php.ini $MAGENTO_DIR/php.ini

USER root

# Using the local.env.php to overwride the repo
COPY local.env.php $MAGENTO_DIR/app/etc/env.php

# Chaning permissions for some files to make sure permissions are correct
RUN chown -R magento:www-data $MAGENTO_DIR/.git /var/lib/php/sessions $MAGENTO_DIR/app/etc/env.php
COPY local.00_magento.ini  /etc/php/7.2/cli/conf.d/00_magento.ini 
COPY local.00_magento.ini  /etc/php/7.2/apache2/conf.d/00_magento.ini 

# Expose Ports for Apache
EXPOSE 80/tcp 443/tcp

# Run entrypoint file
COPY local.entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]
