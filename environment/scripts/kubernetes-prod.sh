gcloud config set project ecommerce-platform-211419
gcloud container clusters get-credentials magento-prod-cluster --zone us-east1
instance=$(kubectl get pod -l app=magento-prod -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c magento /bin/bash
