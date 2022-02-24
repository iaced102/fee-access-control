ARG REPO_LOCATION=registry.symper.vn
ARG BASE_VERSION=1.0
FROM ${REPO_LOCATION}/php-7.4.8-master:${BASE_VERSION}
# Copy source code
WORKDIR /src
ADD ./ /var/www/accesscontrol.symper.vn
EXPOSE 9000
