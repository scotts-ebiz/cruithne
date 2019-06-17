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
MAGENTO=./bin/magento

# Setup Upgrade
echo 'Begin Setup Upgrade...'
sudo php $MAGENTO setup:upgrade
echo 'End Setup Upgrade.'
echo ''

# Setup Code Compile
echo 'Begin Setup Di Compile...'
sudo php $MAGENTO setup:di:compile
echo 'End Setup Di Compile.'
echo ''

# Gulp Clean and Styles
echo 'Begin Gulp Clean and Styles...'
sudo cd tools && gulp clean && gulp styles && cd ..
echo 'End Gulp Clean and Styles.'
echo ''

# Setup Code Compile
echo 'Begin Static Content Deploy...'
sudo php $MAGENTO setup:static-content:deploy
echo 'End Static content Deploy.'
echo ''

# Reindex
echo 'Begin Reindexing...'
sudo php $MAGENTO indexer:reindex
echo 'End Reindexing.'
echo ''

echo 'Begin Flushing Cache...'
sudo php $MAGENTO cache:flush
echo 'End Flushing Cache.'
echo ''



