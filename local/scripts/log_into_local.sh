#!/bin/bash
instance=$(kubectl get pod -l app=magento-local -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c local-magento /bin/bash
