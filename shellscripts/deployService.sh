#!/bin/bash

ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S rm -rf /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}"
ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S mkdir -p /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}"
ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "rm -rf /tmp/${SERVICE_ENV}/${SERVICE_NAME}"
ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "mkdir -p /tmp/${SERVICE_ENV}/${SERVICE_NAME}"
scp -o StrictHostKeyChecking=no k8s/* $USER_NAME@14.225.36.157:/tmp/${SERVICE_ENV}/${SERVICE_NAME}
ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S  cp /tmp/${SERVICE_ENV}/${SERVICE_NAME}/* /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}"
ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S  kubectl config set-context --current --namespace=${SERVICE_ENV}"
ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S  kubectl apply -f /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}"

ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S kubectl rollout status deployment/$APP_NAME-deployment-$TARGET_ROLE"
STATUS=$?
echo "status: $STATUS"
if [ "$STATUS" == 0 ] 
then
    echo "Deployment succeed!"
    # ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S kubectl patch service $APP_NAME -p \'\'{\"spec\": {\"selector\": {\"role\": \"$TARGET_ROLE\"}}}\'\'"
    # ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S sed \"s/$CURRENT_ROLE/$TARGET_ROLE/g\" /tmp/${SERVICE_ENV}/${SERVICE_NAME}/new_php_service.yaml > /tmp/${SERVICE_ENV}/${SERVICE_NAME}/patch_php_service.yaml"
    # ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S cp /tmp/${SERVICE_ENV}/${SERVICE_NAME}/patch_php_service.yaml /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}/patch_php_service.yaml"
    # ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S ls -al /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}/patch_php_service.yaml"
    # ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S kubectl apply -f /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}/patch_php_service.yaml"
    ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S kubectl patch service $APP_NAME -p '{\"spec\": {\"selector\": {\"role\": \"$TARGET_ROLE\"}}}'"
    ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 "echo $USER_PASS | sudo -S kubectl delete deployment $APP_NAME-deployment-$CURRENT_ROLE --ignore-not-found=true"
fi