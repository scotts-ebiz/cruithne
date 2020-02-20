gcloud config set project ecommerce-platform-211419
gcloud container clusters get-credentials magento-prod-cluster --zone us-east1

# get the number of items in cluster
LIST=$(kubectl get pod -l app=magento-prod | wc -l)

LIST_COUNT=`expr $LIST - 1`
#echo $LIST_COUNT

MAX_COUNT=`expr $LIST - 2`
#echo $MAX_COUNT

# get the desired cluster to pull from
echo "There are $LIST_COUNT clusters available."
read -p "Enter number from 0 - $MAX_COUNT for cluster to view: " CLUSTER_ID
#echo $CLUSTER_ID

CLUSTER_NAME={.items[$CLUSTER_ID].metadata.name}
#echo $CLUSTER_NAME

INSTANCE=$(kubectl get pod -l app=magento-prod -o jsonpath="$CLUSTER_NAME")
#echo $INSTANCE

# connect to the desirec instance
kubectl exec -it $INSTANCE -c magento /bin/bash
