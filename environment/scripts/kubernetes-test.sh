gcloud config set project ecommerce-platform-211419
gcloud container clusters get-credentials magento-test-ha-cluster --zone us-east1
instance=$(kubectl get pod -l app=magento-test -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c test-magento /bin/bash
