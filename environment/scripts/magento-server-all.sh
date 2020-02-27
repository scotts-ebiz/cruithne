#!/usr/bin/env bash

###############################################################
#
# This script calls common commands that are used while working
# with Magneto 2 on the server.  This script makes the following calls.
# 
# There is probably no use for this script locally
#
# setup:upgrade
# setup:di:compile
# cd tools && gulp clean && gulp styles && cd ..
# setup:static-content:deploy
# indexer:reindex
# cache:flush
# 
#
#
################################################################
# Magento Direcotry
MAGENTO=./bin/magento

# Setup Upgrade
echo 'Begin Setup Upgrade...'
php $MAGENTO setup:upgrade
echo 'End Setup Upgrade.'
echo ''

# Setup Code Compile
echo 'Begin Setup Di Compile...'
php $MAGENTO setup:di:compile
echo 'End Setup Di Compile.'
echo ''

# Gulp Clean and Styles
echo 'Begin Gulp Clean and Styles...'
cd tools && gulp clean && gulp styles --prod && cd ..
echo 'End Gulp Clean and Styles.'
echo ''

# Setup Code Compile
echo 'Begin Static Content Deploy...'
php $MAGENTO setup:static-content:deploy -f
echo 'End Static content Deploy.'
echo ''

# Reindex
echo 'Begin Reindexing...'
php $MAGENTO indexer:reindex
echo 'End Reindexing.'
echo ''

echo 'Begin Flushing Cache...'
php $MAGENTO cache:flush
echo 'End Flushing Cache.'
echo ''
