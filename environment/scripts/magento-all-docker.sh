#!/usr/bin/env bash

###############################################################
#
# This script calls common commands that are used while working
# with Magneto 2.  This script makes the following calls.
#
# indexer:reindex
# cache:flush
# cache:clean
# setup:upgrade
#
# This script can be setup as an alias with in the vagrant ssh
# for the vagrant user to make to update the code as needed.
#
################################################################
# Magento Direcotry
MAGENTO=/var/www/html/magento2

# Change to the working directory
# Should already be there but just in case
cd $MAGENTO

# Reindex
echo 'Begin Reindexing...'
php bin/magento indexer:reindex
echo 'End Reindexing.'
echo ''

# Flush the cache
echo 'Begin Flushing Cache...'
php bin/magento cache:flush
echo 'End Flushing Cache.'
echo ''

# Clear the generated code
echo 'Begin Code Generation Cleaning...'
rm -f -r generated/code/
echo 'End Code Generation Cleaning...'
echo ''

# Run Setup Uprade
echo 'Begin Module Update...'
php bin/magento setup:upgrade
echo 'End Module Update.'

# Run gulp
echo 'Begin Gulp...'
./environment/scripts/gulp.sh
echo 'End Gulp...'

# Updating permission
echo 'Begin permission update...'
chown -R www-data:www-data .
echo 'End permission update...'
echo ''

