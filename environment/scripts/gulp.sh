#!/usr/bin/env bash

###############################################################
#
# This script calls gulp commands to refresh the styling of
# the site.  This should be ran at the project root.
#
# gulp clean
# gulp styles
#
################################################################
# Gulp Directory
GULP_DIRECTORY=vendor/snowdog/frontools

# Change to the snowdog directory
echo 'Change to Snowdog Direcctory...'
cd $GULP_DIRECTORY

# Gulp clean
echo 'Cleaning...'
gulp clean
echo 'End Cleaning.'
echo ''

echo 'Begin Creating Styles...'
gulp styles
echo 'End Creating Styles.'
echo ''
