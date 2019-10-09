#!/bin/bash
#/Users/tcasper/Desktop/staging_201909250500.sql
echo "Enter the full path to a mysql dump file..."
read dumpfile

instance=$(kubectl get pod -l tier=mysql -o jsonpath="{.items[0].metadata.name}")
kubectl exec -i $instance -c mysql -- mysql -u root -pmagento < scripts/create_magento_db.sql
kubectl exec -i $instance -c mysql -- mysql -u root -pmagento magento < $dumpfile
kubectl exec -i $instance -c mysql -- mysql -u root -pmagento magento < scripts/set_magento_to_localdb_url.sql

