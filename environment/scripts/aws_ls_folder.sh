#!/usr/bin/env bash
  
###############################################################
#
# This script is used to list all of the files in the S2
# amazon bucket that is used for the SAP process
#
################################################################
# AWS S3 Bucket
S3_BUCKET=s3://scotts-scotts-b2c-nonprod

# Get the folder to list
read -p "Enter Location to list: " FOLDER_TO_LIST

# Run the command to list the files in the S3 bucket
aws s3 ls $S3_BUCKET/$FOLDER_TO_LIST/
