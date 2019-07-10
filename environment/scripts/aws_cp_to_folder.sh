#!/usr/bin/env bash
  
###############################################################
#
# This script is used to copy a file from the 
# amazon bucket that is used for the SAP process
#
################################################################
# AWS S3 Bucket
S3_BUCKET=s3://scotts-scotts-b2c-nonprod

# Get the file name and location that is desired to be copied
read -p "Enter File To Copy: " COPY_FROM_FOLDER
#echo $COPY_FROM_FOLDER

# Get the file name and location
read -p "Enter Location to Copy: " COPY_TO_FOLDER
#echo $COPY_FROM_FOLDER
#echo $S3_BUCKET/$COPY_FROM_FOLDER $COPY_TO_FOLDER

# Run the command to list the files in the S3 bucket
aws s3 cp $COPY_TO_FOLDER $S3_BUCKET/$COPY_FROM_FOLDER --recursive
