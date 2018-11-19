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

# Reindex
echo 'Begin Flushing Cache...'
sudo php $MAGENTO cache:flush
echo 'End Flushing Cache.'
echo ''

echo 'Begin Cleaning Cache...'
sudo php $MAGENTO cache:clean
echo 'End Cleaning Cache.'
echo ''
