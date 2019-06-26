gcloud container clusters get-credentials magento-cluster --zone us-east1-b
instance=$(kubectl get pod -l app=magento-app-workload -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c magento /bin/bash
