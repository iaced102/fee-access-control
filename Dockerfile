ARG REPO_LOCATION=registry.symper.vn
ARG BASE_VERSION=2.0
FROM ${REPO_LOCATION}/php-7.4:${BASE_VERSION}
# Copy source code
WORKDIR /src
ADD ./ /var/www/accesscontrol.symper.vn
EXPOSE 9000
