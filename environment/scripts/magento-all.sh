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
MAGENTO=/var/www/cruithne/bin/magento

# Reindex
echo 'Begin Reindexing...'
sudo php $MAGENTO indexer:reindex
echo 'End Reindexing.'
echo ''

echo 'Begin Flushing Cache...'
sudo php $MAGENTO cache:flush
echo 'End Flushing Cache.'
echo ''

# Cache Flush is more comprehensive so we don't
# need to do a cache clean
#echo 'Begin Cleaning Cache...'
#sudo php $MAGENTO cache:clean
#echo 'End Cleaning Cache.'
#echo ''

# Clear the generated code
echo 'Begin Code Generation Cleaning...'
sudo rm -f -r /var/www/cruithne/generated/code/
echo 'End Code Generation Cleaning...'
echo ''

echo 'Begin Module Update...'
sudo php $MAGENTO setup:upgrade
echo 'End Module Update.'
echo ''
