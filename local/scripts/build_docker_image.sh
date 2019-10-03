#!/bin/bash
eval $(minikube docker-env)
docker build build/ -t local-magento:1.0.0