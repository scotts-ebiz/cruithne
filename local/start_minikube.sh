#!/bin/bash
# minikube delete
minikube start --disk-size='80g' --memory='8g' --extra-config=apiserver.service-node-port-range=80-30000
kubectl apply -k .