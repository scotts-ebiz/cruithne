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

USER magento
WORKDIR $MAGENTO_DIR

# Temporary add to test mass import of images
RUN mkdir -p $MAGENTO_DIR/var/import/images && gsutil -q -m rsync -r -u gs://magento-image-repo/import/ $MAGENTO_DIR/var/import/images ; exit 0

RUN git config --global user.name "Git User" && git config --global user.email "user@example.com" && git fetch --all && git reset --hard origin/master && git pull

RUN composer install
RUN git checkout origin .htaccess
RUN ln -s /var/www/html/magento2/vendor/snowdog/frontools/ /var/www/html/magento2/tools


# Set developer Mode if NOT in production or release-candidate or hotfixes
RUN if [ "$BRANCH" != "master" ] && [ "$BRANCH" != "release-candidate" ] && [ "$BRANCH" != "hotfixes" ]; then bin/magento deploy:mode:set developer && bin/magento setup:config:set --enable-debug-logging=true && sed -i "s/display_errors.*/display_errors = on/" php.ini; fi

# Temporary patch for VendorVertex
# COPY vendor-vertex-compilation-issue.patch /tmp/vendor-vertex-compilation-issue.patch
# RUN git apply /tmp/vendor-vertex-compilation-issue.patch; exit 0

USER root

# Expose Ports for Apache
EXPOSE 80/tcp 443/tcp

RUN find var pub/static pub/media app/etc -type f -exec chmod g+w {} + && find var pub/static pub/media app/etc -type d -exec chmod g+ws {} +

# Run entrypoint file
COPY ondemand-entrypoint.sh /usr/local/bin/entrypoint.sh

# Make executable
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

# The apachectl command is in entrypoint :-)
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]
