#!/bin/bash
# This entrypoint does some last minute magento calls before the image is ready
# works for both test and production
# Rick got his inspiration from: https://github.com/sensson/docker-magento2/blob/master/entrypoint.sh and we should probably look into it a little more

# This helps with debugging especially with Docker
set -euxo pipefail
COMMAND="$@"

# Run Setup Upgrade
su - magento -c '/var/www/html/magento2/bin/magento setup:upgrade --keep-generated'


# Notify of deploy
curl -X POST --data-urlencode "payload={\"channel\": \"#magento2-botalerts\", \"username\": \"m2deploybot\", \"text\": \"The most recent commit below has been deployed to the $(git rev-parse --abbrev-ref HEAD) environment $(git show | head -n 10)\", \"icon_emoji\": \":rocket:\"}" https://hooks.slack.com/services/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

# Activate Sumologic
service collector start

# always start elasticsearch for fulltext catalog search indexer
# Having a problem with elasticsearch, disabling for now
# service elasticsearch start

# always start cron
service cron start

# CMD "exec /usr/sbin/apachectl -DFOREGROUND -k start"
exec /usr/sbin/apachectl -DFOREGROUND
