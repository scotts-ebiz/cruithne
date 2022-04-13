#!/bin/bash
# This entrypoint does some last minute magento calls before the image is ready
# works for both test and production
# Rick got his inspiration from: https://github.com/sensson/docker-magento2/blob/master/entrypoint.sh and we should probably look into it a little more

# This helps with debugging especially with Docker
set -euxo pipefail
COMMAND="$@"

git status

# Run Setup Upgrade
su - magento -c '/var/www/html/magento2/bin/magento setup:upgrade'
su - magento -c '/var/www/html/magento2/bin/magento setup:di:compile'

# Set Styles
su - magento -c 'rm /var/www/html/magento2/tools/yarn.lock'
su - magento -c 'cd /var/www/html/magento2/tools/ && ls -la'
su - magento -c 'node --version'
su - magento -c 'cd /var/www/html/magento2/tools && npm rebuild node-sass && gulp clean -f /var/www/html/magento2/tools/gulpfile.esm.js && gulp styles --prod -f /var/www/html/magento2/tools/gulpfile.esm.js'
su - magento -c '/var/www/html/magento2/bin/magento setup:static-content:deploy -f'

# Reindex and Cache Flush
su - magento -c '/var/www/html/magento2/bin/magento -v index:reindex'
su - magento -c '/var/www/html/magento2/bin/magento -v cache:flush'

git status

# Activate services
service collector start
service cron start
service logstash start

# CMD "exec /usr/sbin/apachectl -DFOREGROUND -k start"
exec /usr/sbin/apachectl -DFOREGROUND
