gcloud config set project ecommerce-platform-211419
gcloud container clusters get-credentials magento-develop-cluster --zone us-east1-d
instance=$(kubectl get pod -l app=magento-develop -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c develop-magento /bin/bash
