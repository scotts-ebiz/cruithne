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

RUN rm /usr/local/bin/entrypoint.sh

# install SumoLogic
WORKDIR /tmp
RUN wget "https://collectors.sumologic.com/rest/download/deb/64" -O SumoCollector.deb && dpkg --install /tmp/SumoCollector.deb && service collector start
RUN printf "name = Magento2-Collector\naccessid = suJx8dS3MKjFSu\naccesskey = Q1cq15TX5wS4BzERvxap0ugIdU2Ti5HX9oVw8yAFJaqfgrShfR7VpWontR9Qd4Wo\n" >  /opt/SumoCollector/config/user.properties


# install gsutil
RUN export CLOUD_SDK_REPO="cloud-sdk-$(lsb_release -c -s)"; echo "deb http://packages.cloud.google.com/apt $CLOUD_SDK_REPO main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list
RUN cat /etc/apt/sources.list.d/google-cloud-sdk.list

RUN curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key add -
RUN apt-get update -y  && apt-get install -y google-cloud-sdk

# Can move these to nightly build

RUN curl -sL https://deb.nodesource.com/setup_11.x | bash -
RUN apt-get install -y --no-install-recommends nodejs php7.2-redis
RUN npm i -g gulp

## ElasticSearch
RUN a2enmod proxy_http

##n98-magerun tools
RUN curl -O https://files.magerun.net/n98-magerun2.phar
RUN chmod +x n98-magerun2.phar

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
RUN git checkout origin/test ./app/etc/config.php
RUN git checkout origin/fix_apis_buildOnTest .htaccess

RUN git pull

RUN sed -i "s/save.*/save' => 'redis',/" app/etc/env.php

RUN date

# Neelam Special Per https://scotts.jira.com/browse/ECOM-496
# This is a Patch for Magento per https://github.com/magento/magento2/issues/13269
# Should be able to be removed after Magento 2.3.1 is live
RUN sed -i "s/.*result = mb_convert_encoding.*/\t\t\$result = mb_convert_encoding(preg_replace('\/\&\/', \'\&amp\;\', html_entity_decode(\$domDocument->saveHTML())), \'HTML-ENTITIES\', \'UTF-8\');\n/" $MAGENTO_DIR/vendor/magento/framework/Escaper.php

USER root

# Magento 2.3 fix that will fix catalog rules to be applied
# https://magento.stackexchange.com/questions/152101/catalog-price-rule-not-applied-working-in-magento-2-ee-v2-1-2
RUN sed -i '22s/.*/                <visible>true<\/visible>/' $MAGENTO_DIR/vendor/magento/module-catalog-rule-staging/view/adminhtml/ui_component/catalog_rule_form.xml 

# Added this in as a test to force php to save sessions in redis
RUN sed -i '/session.save_handler/s/^/; /g' /etc/php/7.2/apache2/php.ini

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

# Run entrypoint file
COPY ondemand-entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]

# CMD "exec /usr/sbin/apachectl -DFOREGROUND -k start"
# CMD /usr/sbin/apachectl -DFOREGROUND 
