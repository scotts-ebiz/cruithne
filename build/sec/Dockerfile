# TODO: Pre-cache composer as much as possible
#Base image is Google's Optimized Ubuntu Image

# TODO: environment switch to test image OR replace env.php file here

ARG IMAGE=gcr.io/ecommerce-platform-211419/test-primer:latest
FROM $IMAGE
ENTRYPOINT []

RUN date

ENV NVM_DIR /home/magento/.nvm
ENV MAGENTO_DIR /var/www/html/magento2

LABEL company="The Scotts MiracleGro Company"
LABEL maintainer="rick.feldschau@scotts.com"

RUN rm /usr/local/bin/entrypoint.sh

# Can move these to nightly build

RUN curl -sL https://deb.nodesource.com/setup_11.x | bash -
RUN apt-get install -y --no-install-recommends nodejs php7.2-redis
RUN npm i -g gulp

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

RUN git pull

RUN sed -i "s/save.*/save' => 'redis',/" app/etc/env.php

RUN date

USER root

# Added this in as a test to force php to save sessions in redis
RUN sed -i '/session.save_handler/s/^/; /g' /etc/php/7.2/apache2/php.ini

RUN git apply $MAGENTO_DIR/build/magento_security_patches/PRODSECBUG-2198-2.3-EE.composer-2019-03-27-06-10-53.patch

RUN sed 's/newrelic\.license/;newrelic\.license/' /etc/php/7.2/apache2/conf.d/20-newrelic.ini
RUN echo "newrelic.license = 'eeaa206f88996c7640153d2c3ba77c0d3522d942'" >> /etc/php/7.2/apache2/conf.d/20-newrelic.ini

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
