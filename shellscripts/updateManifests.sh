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

sed -i -e "s/{SYMPER_IMAGE}/${SERVICE_NAME}:${DOCKER_TAG}/g" \
       -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{CURRENT_ROLE}/$CURRENT_ROLE/g" \
       -e "s/{TARGET_ROLE}/$TARGET_ROLE/g" k8s/accesscontrol_deployment.yaml

sed -i -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{CURRENT_ROLE}/$CURRENT_ROLE/g" k8s/php_service.yaml

sed -i -e "s/{ENVIRONMENT_DOMAIN}/$envDomain/g" \
       -e "s/{APP_NAME}/$APP_NAME/g" \
       -e "s/{CURRENT_ROLE}/$CURRENT_ROLE/g" \
       -e "s/{TARGET_ROLE}/$TARGET_ROLE/g" k8s/service_ing.yaml

sed -i -e "s/{ENVIRONMENT}/$originEnv/g" \
       -e "s/{ENVIRONMENT_}/$env/g" \
       -e "s/{POSTGRES_USER}/$POSTGRES_USER/g" \
       -e "s/{POSTGRES_PASSWORD}/$POSTGRES_PASSWORD/g" \
       -e "s/{POSTGRES_HOST}/$POSTGRES_HOST/g" k8s/config_env.yaml