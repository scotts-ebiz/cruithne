#!/bin/bash
instance=$(kubectl get pod -l app=local-magento -o jsonpath="{.items[0].metadata.name}")
kubectl logs $instance -c magento