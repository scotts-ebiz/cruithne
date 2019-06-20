#!/usr/bin/env bash
  
###############################################################
#
# This script is used to list all of the files in the S2
# amazon bucket that is used for the SAP process
#
################################################################
# AWS S3 Bucket
S3_BUCKET=s3://scotts-scotts-b2c-nonprod

# Run the command to list the files in the S3 bucket
aws s3 ls $S3_BUCKET
