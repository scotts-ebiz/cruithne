gcloud config set project ecommerce-platform-211419
gcloud container clusters get-credentials magento-gom1-ha-cluster --zone us-east1
instance=$(kubectl get pod -l app=magento-gom1 -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c gom1-magento /bin/bash
