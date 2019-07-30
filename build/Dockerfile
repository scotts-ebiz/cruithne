# TODO: Pre-cache composer as much as possible
#Base image is Google's Optimized Ubuntu Image


# TODO: environment switch to test image OR replace env.php file here

ARG IMAGE=gcr.io/ecommerce-platform-211419/magento-dev-primer:latest
FROM $IMAGE
ENTRYPOINT []

RUN date

ENV NVM_DIR /home/magento/.nvm
ENV MAGENTO_DIR /var/www/html/magento2

LABEL company="The Scotts MiracleGro Company"
LABEL maintainer="rick.feldschau@scotts.com"

USER root
RUN chown -R magento:www-data .git

USER magento
WORKDIR $MAGENTO_DIR

RUN git fetch

RUN git pull

RUN sed -i "s/save.*/save' => 'redis',/" app/etc/env.php

RUN date

# for icons to stick need paths and permissions
RUN mkdir -p pub/media/tmp/design/file/
RUN chmod 0755 pub/media/tmp/design/file/

USER root

# Magento 2.3 fix that will fix catalog rules to be applied
# https://magento.stackexchange.com/questions/152101/catalog-price-rule-not-applied-working-in-magento-2-ee-v2-1-2
RUN sed -i '22s/.*/                <visible>true<\/visible>/' $MAGENTO_DIR/vendor/magento/module-catalog-rule-staging/view/adminhtml/ui_component/catalog_rule_form.xml 

RUN git apply $MAGENTO_DIR/build/magento_security_patches/PRODSECBUG-2198-2.3-EE.composer-2019-03-27-06-10-53.patch

RUN sed 's/newrelic\.license/;newrelic\.license/' /etc/php/7.2/apache2/conf.d/20-newrelic.ini
RUN echo "newrelic.license = 'eeaa206f88996c7640153d2c3ba77c0d3522d942'" >> /etc/php/7.2/apache2/conf.d/20-newrelic.ini

RUN REV=$(git rev-parse HEAD)

RUN \
	curl -X POST 'https://api.newrelic.com/v2/applications/203206085/deployments.json' \
     -H 'X-Api-Key:abb8c9f78734ce8444b721b2ad8f0474899a8932522d942' -i \
     -H 'Content-Type: application/json' \
     -d \
	'{ "deployment": { "revision": "$REV" } }'

# Expose Ports for Apache
EXPOSE 80/tcp 443/tcp

USER root

RUN find /var/www/html/magento2/pub/media /var/www/html/magento2/pub/static /var/www/html/magento2/app/etc /var/www/html/magento2/var /var/www/html/magento2/generated -exec chown magento:www-data {} \;
RUN find /var/www/html/magento2/pub/media /var/www/html/magento2/pub/static /var/www/html/magento2/app/etc /var/www/html/magento2/var /var/www/html/magento2/generated -exec chmod 777 {} \;

# Run entrypoint file
COPY ondemand-entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]

# CMD "exec /usr/sbin/apachectl -DFOREGROUND -k start"
# CMD /usr/sbin/apachectl -DFOREGROUND 

# Let's generate a new commit hash.
