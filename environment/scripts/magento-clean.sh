#!/usr/bin/env bash

###############################################################
#
# This script calls common commands that are used while working
# with Magneto 2.  This script makes the following calls.
#
# cache:flush
# cache:clean
#
# This script can be setup as an alias with in the vagrant ssh
# for the vagrant user to make to update the code as needed.
#
################################################################
# Magento Direcotry
MAGENTO=/var/www/cruithne/bin/magento

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
