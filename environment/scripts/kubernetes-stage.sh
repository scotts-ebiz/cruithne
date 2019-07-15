gcloud container clusters get-credentials magento-stage-cluster --zone us-east1-b
instance=$(kubectl get pod -l app=magento-stage -o jsonpath="{.items[0].metadata.name}")
kubectl exec -it $instance -c stage-magento /bin/bash
