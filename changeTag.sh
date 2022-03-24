#!/bin/bash
env=""
originEnv=""
if [ $2 != "" ]
then
    originEnv=$2
    env=$2"_"
fi
sed "s/{SYMPER_IMAGE}/$1/g" k8s/php_deployment.yaml > k8s/new_php_deployment.yaml
sed "s/{ENVIRONMENT}/$originEnv/g;s/{ENVIRONMENT_}/$env/g" k8s/config_env.yaml > k8s/new_config_env.yaml
rm -rf k8s/php_deployment.yaml
rm -rf k8s/config_env.yaml