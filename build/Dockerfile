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

# install SumoLogic
WORKDIR /tmp
RUN wget "https://collectors.sumologic.com/rest/download/deb/64" -O SumoCollector.deb && dpkg --install /tmp/SumoCollector.deb
RUN printf "name = Magento2-Collector\naccessid = suJx8dS3MKjFSu\naccesskey = Q1cq15TX5wS4BzERvxap0ugIdU2Ti5HX9oVw8yAFJaqfgrShfR7VpWontR9Qd4Wo\nephemeral=true\n" >  /opt/SumoCollector/config/user.properties

# Add crontab file in the cron directory
ADD crontab /etc/cron.d/cron

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/cron

# Apply cron job
RUN crontab /etc/cron.d/cron

# Create the log file to be able to run tail
RUN touch /var/log/cron.log
RUN touch /var/log/magento.cron.log
RUN touch /var/log/update.cron.log
RUN touch /var/log/setup.cron.log

# Change user back to root to continue on
RUN whoami
USER root

# install gsutil
RUN export CLOUD_SDK_REPO="cloud-sdk-$(lsb_release -c -s)"; echo "deb http://packages.cloud.google.com/apt $CLOUD_SDK_REPO main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list
RUN cat /etc/apt/sources.list.d/google-cloud-sdk.list

RUN curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key add -
RUN apt-get update -y  && apt-get install -y google-cloud-sdk

# for icons to stick need paths and permissions
RUN mkdir -p pub/media/tmp/design/file/
RUN chmod 0755 pub/media/tmp/design/file/


# Can move these to nightly build

RUN npm i -g gulp

## ElasticSearch
RUN a2enmod proxy_http

##n98-magerun tools
RUN curl -O https://files.magerun.net/n98-magerun2.phar
RUN chmod +x n98-magerun2.phar

#enabling the web-server to write
RUN chgrp www-data /var/www/html/magento2/
RUN chmod g+w /var/www/html/magento2/

## Installing the Qualys Cloud Agent in this Dockerfile for now. Will move to Nightly Build when proven successfull. 

# Copy Cloud agent to tmp directory
COPY qualys/qualys-cloud-agent.x86_64.deb /tmp/qualys/qualys-cloud-agent.x86_64.deb

# Run Qualys Installer
RUN dpkg --install /tmp/qualys/qualys-cloud-agent.x86_64.deb

COPY qualys/qualys-cloud-agent.postinst /tmp/qualys/qualys-cloud-agent.postinst
COPY qualys/qagent_restart.sh /usr/local/qualys/cloud-agent/bin/qagent_restart.sh

RUN chmod +x /tmp/qualys/qualys-cloud-agent.postinst
RUN /tmp/qualys/qualys-cloud-agent.postinst

RUN /usr/local/qualys/cloud-agent/bin/qualys-cloud-agent.sh ActivationId="67906ffb-cd7c-4105-bdc7-1540c13343aa" CustomerId="63d94f9b-9dfc-7538-823c-333fc1d63ac9" ProviderName="GCP" UseSudo=0

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
