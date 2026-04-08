GIT_COMMIT ?= $(shell git rev-parse --short=8 HEAD)
.PHONY: help

production-container-build:
	docker build --build-arg COMPOSER_AUTH=$(AUTH_JSON) -f docker/common/Dockerfile-app -t logeecom-application:$(GIT_COMMIT) .

production-container-push:
	@docker tag logeecom-application:$(GIT_COMMIT) $(APP_REGISTRY_URI):$(GIT_COMMIT)
	@docker push $(APP_REGISTRY_URI):$(GIT_COMMIT)

production-nginx-build:
	docker build -f docker/ecs/Dockerfile-nginx -t nginx-logeecom-application:$(GIT_COMMIT) .

production-nginx-push:
	@docker tag nginx-logeecom-application:$(GIT_COMMIT) $(NGINX_REGISTRY_URI):$(GIT_COMMIT)
	@docker push $(NGINX_REGISTRY_URI):$(GIT_COMMIT)