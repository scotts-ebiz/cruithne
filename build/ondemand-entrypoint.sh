#!/bin/bash
# This entrypoint does some last minute magento calls before the image is ready
# works for both test and production
# Rick got his inspiration from: https://github.com/sensson/docker-magento2/blob/master/entrypoint.sh and we should probably look into it a little more

#This helps with debugging especially with Docker
set -euxo pipefail
COMMAND="$@"

# Set hooks
#PRE_INSTALL_HOOK="/hooks/pre_install.sh"
#PRE_COMPILE_HOOK="/hooks/pre_compile.sh"
#POST_INSTALL_HOOK="/hooks/post_install.sh"



# rm -rf /var/www/html/magento2/var/generation/*
# su - magento -c '/var/www/html/magento2/bin/magento deploy:mode:set -s developer'
su - magento -c '/var/www/html/magento2/bin/magento deploy:mode:set -s production'
su - magento -c '/var/www/html/magento2/bin/magento setup:upgrade'
su - magento -c '/var/www/html/magento2/bin/magento setup:di:compile'
su - magento -c 'gulp clean -f /var/www/html/magento2/tools/gulpfile.js'
su - magento -c 'gulp styles -f /var/www/html/magento2/tools/gulpfile.js'
su - magento -c '/var/www/html/magento2/bin/magento -vvv setup:static-content:deploy'



# chown -R magento:www-data /var/www/html/

# CMD "exec /usr/sbin/apachectl -DFOREGROUND -k start"
/usr/sbin/apachectl -DFOREGROUND 