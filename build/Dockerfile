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

# Neelam Special Per https://scotts.jira.com/browse/ECOM-496
# This is also a Patch for Magento per https://github.com/magento/magento2/issues/13269

RUN sed -i "s/.*mouseEventProps = .*/\tmouseEventProps = 0,/" $MAGENTO_DIR/pub/static/frontend/Scottsbase/mygro/en_US/jquery/jquery.mobile.custom.js

RUN sed -i "s/.*result = mb_convert_encoding.*/\t\t\$result = mb_convert_encoding(preg_replace('\/\&\/', \'\&amp\;\', html_entity_decode(\$domDocument->saveHTML())), \'HTML-ENTITIES\', \'UTF-8\');\n/" $MAGENTO_DIR/vendor/magento/framework/Escaper.php

USER root

RUN sed 's/newrelic\.license/;newrelic\.license/' /etc/php/7.2/apache2/conf.d/20-newrelic.ini
RUN echo "newrelic.license = 'bc904518f55ecc33a8a79a28465a7fe36db5455e'" >> /etc/php/7.2/apache2/conf.d/20-newrelic.ini

RUN REV=$(git rev-parse HEAD)

RUN \
	curl -X POST 'https://api.newrelic.com/v2/applications/180724804/deployments.json' \
     -H 'X-Api-Key:033455e6b2ed8503dbfaf55460d5a2868c42d238db5455e' -i \
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
