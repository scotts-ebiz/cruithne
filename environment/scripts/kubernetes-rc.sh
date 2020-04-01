gcloud config set project ecommerce-platform-211419
gcloud container clusters get-credentials magento-rc-ha-cluster --zone us-east1
instance=$(kubectl get pod -l app=magento-rc -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c rc-magento /bin/bash
