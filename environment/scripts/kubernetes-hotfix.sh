gcloud config set project ecommerce-platform-hotfix
gcloud container clusters get-credentials magento-hotfix-ha-cluster --zone us-east1
instance=$(kubectl get pod -l app=magento-hotfix -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c hotfix-magento /bin/bash
