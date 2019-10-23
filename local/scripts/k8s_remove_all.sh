#!/bin/bash

kubectl delete deployment --all 
kubectl delete service --all 
kubectl delete secrets --all
kubectl delete pods --all