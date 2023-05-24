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

replaceWith="-forward.symper.vn"
search=".symper.vn"
host=${SERVICE_NAME//$search/$replaceWith}
sed -i -e "s/{ENVIRONMENT}/$originEnv/g" \
       -e "s/{ENVIRONMENT_}/$env/g" \
       -e "s/{SERVICE_ENV}/$SERVICE_ENV/g" \
       -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{CACHE_HOST}/$CACHE_HOST/g" \
       -e "s/{POSTGRES_DB}/${POSTGRES_DB}/g" \
       -e "s/{POSTGRES_HOST}/$POSTGRES_HOST/g" \
       -e "s/{KAFKA_PREFIX}/$KAFKA_PREFIX/g"  k8s/app_configmap.yaml

sed -i -e "s/{SERVICE_NAME}/$SERVICE_NAME/g" \
       -e "s/{SERVICE_ENV}/$SERVICE_ENV/g" \
       -e "s/{APP_NAME}/$APP_NAME/g" k8s/nginx_configmap.yaml  

sed -i -e "s/{SYMPER_IMAGE}/${SERVICE_NAME}:${BUILD_VERSION}/g" \
       -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{SERVICE_NAME}/$SERVICE_NAME/g" \
       -e "s/{SERVICE_ENV}/$SERVICE_ENV/g" \
       -e "s/{TARGET_ROLE}/$TARGET_ROLE/g" k8s/app_deployment.yaml

sed -i -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{CURRENT_ROLE}/$CURRENT_ROLE/g" \
       -e "s/{HOST_DOMAIN}/${envDomain}${host}/g" k8s/service_ingress.yaml
