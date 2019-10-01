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
RUN apt-get update && apt-get remove -y nodejs yarn php-redis && apt-get install -y nodejs yarn

RUN cd $MAGENTO_DIR/tools/ && yarn install

USER magento
WORKDIR $MAGENTO_DIR

RUN git fetch --all
RUN git reset --hard origin/staging
RUN git checkout staging
RUN composer install

COPY phpinfo.php $MAGENTO_DIR/pub/phpinfo.php

# Add Correct .htaccess
COPY local.htaccess $MAGENTO_DIR/.htaccess

# Add Correct php.ini
COPY local.php.ini $MAGENTO_DIR/php.ini

USER root
# Add Correct Local Magento Environment Config
COPY env.local.php $MAGENTO_DIR/app/etc/env.php
RUN chown -R magento:www-data $MAGENTO_DIR/.git /var/lib/php/sessions $MAGENTO_DIR/app/etc/env.php
COPY 00_magento.local.ini  /etc/php/7.2/cli/conf.d/00_magento.ini 
COPY 00_magento.local.ini  /etc/php/7.2/apache2/conf.d/00_magento.ini 

# Expose Ports for Apache
EXPOSE 80/tcp 443/tcp

#RUN find /var/www/html/magento2/pub/media /var/www/html/magento2/pub/static /var/www/html/magento2/app/etc /var/www/html/magento2/var /var/www/html/magento2/generated | xargs chown magento:www-data
#RUN find /var/www/html/magento2/pub/media /var/www/html/magento2/pub/static /var/www/html/magento2/app/etc /var/www/html/magento2/var /var/www/html/magento2/generated | xargs chmod 777

# Run entrypoint file
COPY ondemand-entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]
