#!/usr/bin/env bash
  
###############################################################
#
# This script is used to copy a file from the 
# amazon bucket that is used for the SAP process
#
################################################################
# AWS S3 Bucket
S3_BUCKET=s3://scotts-scotts-b2c-prod

# Get the file name and location that is desired to be copied
read -p "Enter File To Copy: " COPY_FROM_FILE
#echo $COPY_FROM_FILE

# Get the file name and location
read -p "Enter Location to Copy: " COPY_TO_FILE
#echo $COPY_TO_FILE
#echo $S3_BUCKET/$COPY_FROM_FILE $COPY_TO_FILE

# Run the command to list the files in the S3 bucket
aws s3 cp $S3_BUCKET/$COPY_FROM_FILE $COPY_TO_FILE --profile produser
