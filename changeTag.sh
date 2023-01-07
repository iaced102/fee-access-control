#!/bin/bash
env=""
envDomain=""
originEnv=""
if [ $2 != "" ] && [ $2 != "prod" ]
then
    originEnv=$2
    #env=$2"_"
    envDomain=$2"-"
fi
sed "s/{SYMPER_IMAGE}/$1/g" k8s/php_deployment.yaml > k8s/new_php_deployment.yaml
sed "s/{ENVIRONMENT_DOMAIN}/$envDomain/g" k8s/service_ing.yaml > k8s/new_service_ing.yaml
sed "s/{ENVIRONMENT}/$originEnv/g;s/{ENVIRONMENT_}/$env/g;s/{POSTGRES_USER}/$3/g;s/{POSTGRES_PASSWORD}/$4/g;s/{DB_HOST}/$5/g" k8s/config_env.yaml > k8s/new_config_env.yaml
rm -rf k8s/php_deployment.yaml
rm -rf k8s/config_env.yaml
rm -rf k8s/service_ing.yaml