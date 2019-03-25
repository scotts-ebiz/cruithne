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
RUN apt-get install -y nodejs
RUN npm i -g gulp

USER magento
WORKDIR $MAGENTO_DIR

RUN git fetch
RUN git checkout origin/test ./app/etc/config.php

RUN git pull

# RUN sed -i "s/save.*/save' => 'files',\n\t 'save_path' => '\/tmp',/" app/etc/env.php

RUN date

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
