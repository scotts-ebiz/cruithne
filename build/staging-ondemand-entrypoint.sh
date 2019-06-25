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

# /usr/local/qualys/cloud-agent/bin/qualys-cloud-agent.sh ActivationId="67906ffb-cd7c-4105-bdc7-1540c13343aa" CustomerId="63d94f9b-9dfc-7538-823c-333fc1d63ac9" ProviderName="GCP" UseSudo=0

# # su - magento -c '/var/www/html/magento2/bin/magento deploy:mode:set -s developer'
# su - magento -c '/var/www/html/magento2/bin/magento deploy:mode:set -s production'
# su - magento -c '/var/www/html/magento2/bin/magento setup:upgrade'
# su - magento -c '/var/www/html/magento2/bin/magento setup:di:compile'

# # TODO We should abstract the server address....
# su - magento -c 'php /var/www/html/magento2/bin/magento setup:config:set --cache-backend=redis --cache-backend-redis-server=10.0.1.3   --cache-backend-redis-db=0 -q'
# su - magento -c 'php /var/www/html/magento2/bin/magento setup:config:set --page-cache=redis --page-cache-redis-server=10.0.1.3 --page-cache-redis-db=1 -q'
# su - magento -c 'php /var/www/html/magento2/bin/magento setup:config:set --session-save=redis --session-save-redis-host=10.0.1.3 --session-save-redis-log-level=3 --session-save-redis-db=2 -q'

# su - magento -c 'gulp clean -f /var/www/html/magento2/tools/gulpfile.js'
# su - magento -c 'cd /var/www/html/magento2/tools && npm rebuild node-sass && gulp styles -f /var/www/html/magento2/tools/gulpfile.js'

# su - magento -c '/var/www/html/magento2/bin/magento setup:static-content:deploy'
# su - magento -c '/var/www/html/magento2/bin/magento -v cache:flush'

# For the readiness check
touch /tmp/healthy

# CMD "exec /usr/sbin/apachectl -DFOREGROUND -k start"
exec /usr/sbin/apachectl -DFOREGROUND
