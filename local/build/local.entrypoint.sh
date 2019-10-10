#!/bin/bash
# This entrypoint does some last minute magento calls before the image is ready

# This helps with debugging especially with Docker
set -euxo pipefail
COMMAND="$@"

# Move some local files over
su - magento -c 'cp /tmp/local_build/env.php /var/www/html/magento2/app/etc/env.php'
su - magento -c 'cp /tmp/local_build/.htaccess /var/www/html/magento2/.htaccess'
su - magento -c 'cp /tmp/local_build/phpinfo.php /var/www/html/magento2/pub/phpinfo.php'
su - magento -c 'cp /tmp/local_build/php.ini /var/www/html/magento2/php.ini'

# Run Setup Upgrade
su - magento -c '/var/www/html/magento2/bin/magento setup:upgrade'
su - magento -c '/var/www/html/magento2/bin/magento setup:di:compile' 

# Set Styles
su - magento -c 'cd /var/www/html/magento2/tools && yarn install && gulp clean && gulp styles'
su - magento -c '/var/www/html/magento2/bin/magento setup:static-content:deploy'
 
# Reindex and Cache Flush
su - magento -c '/var/www/html/magento2/bin/magento -v index:reindex'
su - magento -c '/var/www/html/magento2/bin/magento -v cache:flush'



exec /usr/sbin/apachectl -DFOREGROUND
