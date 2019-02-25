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

USER magento
WORKDIR $MAGENTO_DIR

RUN git fetch
RUN git checkout origin/test ./app/etc/config.php

RUN git pull
RUN  \
    . $NVM_DIR/nvm.sh \
    && cd $MAGENTO_DIR/tools \
    && gulp clean && gulp styles

RUN sed -i "s/save.*/save' => 'files',\n\t 'save_path' => '\/tmp',/" app/etc/env.php

RUN date

USER root

RUN sed 's/newrelic\.license/;newrelic\.license/' /etc/php/7.2/apache2/conf.d/20-newrelic.ini
RUN echo "newrelic.license = 'bc904518f55ecc33a8a79a28465a7fe36db5455e'" >> /etc/php/7.2/apache2/conf.d/20-newrelic.ini

# Expose Ports for Apache
EXPOSE 80/tcp 443/tcp

# CMD "exec /usr/sbin/apachectl -DFOREGROUND -k start"
CMD /usr/sbin/apachectl -DFOREGROUND 
