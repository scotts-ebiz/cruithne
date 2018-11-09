#!/usr/bin/env bash

# builds a csv file of orders for the last hour

# get todays date
today=$(date +%Y%m%d);

# database connection
host="localhost";
username="varien";
mypwd="j7K9u3Lm2wA6";
database="cruithne";

# directories
lastrunfile="lastrun.txt";
outputfile="sales-orders-${today}.csv";
backupfile=/databak

# sql file
sqlfile="orders.sql";

# sql variables
startdate=$(cat $lastrunfile);
enddate=$(date +"%Y-%m-%d %H:%M:%S");

echo "Getting the orders for the start date of '$startdate' and end date of '$enddate'"
mysql -h $host -u $username -p$mypwd $database -B -e"set @startdate='$startdate'; set @enddate='$enddate'; `cat $sqlfile`" | sed 's/\t/,/g' > $outputfile

# update the last run time
echo $enddate > ${lastrunfile};

# create back up file
cp $outputfile $backupfile