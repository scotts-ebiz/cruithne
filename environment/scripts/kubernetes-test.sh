gcloud container clusters get-credentials magento-test-cluster --zone us-east1-b
instance=$(kubectl get pod -l app=magento-test -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c test-magento /bin/bash
