gcloud config set project ecommerce-platform-211419
gcloud container clusters get-credentials magento-develop-ha-private-cluster --zone us-east1
instance=$(kubectl get pod -l app=magento-develop -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c develop-magento /bin/bash
