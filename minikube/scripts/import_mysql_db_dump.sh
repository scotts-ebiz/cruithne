#!/bin/bash
instance=$(kubectl get pod -l app=local-magento -o jsonpath="{.items[0].metadata.name}")
kubectl exec -i $instance -c mysql -- mysql -u root -pmagento < create_magento_db.sql
kubectl exec -i $instance -c mysql -- mysql -u root -pmagento magento < staging_201909250500.sql
kubectl exec -i $instance -c mysql -- mysql -u root -pmagento magento < set_magento_to_localdb_url.sql

