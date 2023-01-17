#!/bin/bash
env=""
envDomain=""
originEnv=""
# appName="php-"
if [ $SERVICE_ENV != "" ] && [ $SERVICE_ENV != "prod" ]
then
    originEnv=$SERVICE_ENV
    #env=$SERVICE_ENV"_"
    envDomain=$SERVICE_ENV"-"
fi

sed -i -e "s/{ENVIRONMENT}/$originEnv/g" \
       -e "s/{ENVIRONMENT_}/$env/g" \
       -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{POSTGRES_USER}/$POSTGRES_USER/g" \
       -e "s/{POSTGRES_PASS}/$POSTGRES_PASS/g" \
       -e "s/{POSTGRES_DB}/${POSTGRES_DB}/g" \
       -e "s/{POSTGRES_HOST}/$POSTGRES_HOST/g" k8s/app_configmap.yaml

sed -i -e "s/{SERVICE_NAME}/$SERVICE_NAME/g" \
       -e "s/{APP_NAME}/$APP_NAME/g" k8s/nginx_configmap.yaml  

sed -i -e "s/{SYMPER_IMAGE}/${SERVICE_NAME}:${BUILD_VERSION}/g" \
       -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{SERVICE_NAME}/$SERVICE_NAME/g" \
       -e "s/{TARGET_ROLE}/$TARGET_ROLE/g" k8s/app_deployment.yaml

sed -i -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{CURRENT_ROLE}/$CURRENT_ROLE/g" \
       -e "s/{HOST_DOMAIN}/${envDomain}${SERVICE_NAME}/g" k8s/service_ingress.yaml
