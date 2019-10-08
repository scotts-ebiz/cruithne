#!/bin/bash
# minikube delete
LOCAL_MAGENTO_DIR=$(cd .. && pwd)
minikube start --mount=true --mount-string="${LOCAL_MAGENTO_DIR}:/magento2/" --disk-size='40g' --memory='4g' --extra-config=apiserver.service-node-port-range=80-30000
kubectl apply -k .