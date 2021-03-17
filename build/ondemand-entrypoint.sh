#!/bin/bash
# This entrypoint does some last minute magento calls before the image is ready
# works for both test and production
# Rick got his inspiration from: https://github.com/sensson/docker-magento2/blob/master/entrypoint.sh and we should probably look into it a little more

# This helps with debugging especially with Docker
set -euxo pipefail
COMMAND="$@"

cd $MAGENTO_DIR
su magento -c 'bin/magento maintenance:enable'
su magento -c 'bin/magento setup:upgrade --keep-generated'
su magento -c 'bin/magento -v index:reindex'
su magento -c 'bin/magento maintenance:disable'
su magento -c 'bin/magento -v cache:flush'

# Activate services
service collector start
service cron start
service logstash start

# CMD "exec /usr/sbin/apachectl -DFOREGROUND -k start"
exec /usr/sbin/apachectl -DFOREGROUND
