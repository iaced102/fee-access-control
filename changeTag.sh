#!/bin/bash
sed "s/SymperImage/$1/g" n8n/php_deployment.yaml > n8n/new_php_deployment.yaml