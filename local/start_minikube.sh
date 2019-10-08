#!/bin/bash
# minikube delete
minikube start --mount-string="/Users/tcasper/Development/cruithne/:/magento2" --disk-size='80g' --memory='4g' --extra-config=apiserver.service-node-port-range=80-30000
kubectl apply -k .