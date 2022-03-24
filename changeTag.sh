#!/bin/bash
sed "s/SymperImage/$1/g" k8n/php_deployment.yaml > k8n/new_php_deployment.yaml