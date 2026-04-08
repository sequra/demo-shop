GIT_COMMIT ?= $(shell git rev-parse --short=8 HEAD)
.PHONY: production-container-build production-container-push production-nginx-build production-nginx-push

production-container-build:
	docker build --build-arg COMPOSER_AUTH=$(AUTH_JSON) -f docker/common/Dockerfile-app -t sequra-demo-app:$(GIT_COMMIT) .

production-container-push:
	@docker tag sequra-demo-app:$(GIT_COMMIT) $(APP_REGISTRY_URI):$(GIT_COMMIT)
	@docker push $(APP_REGISTRY_URI):$(GIT_COMMIT)

production-nginx-build:
	docker build -f docker/ecs/Dockerfile-nginx -t sequra-demo-nginx:$(GIT_COMMIT) .

production-nginx-push:
	@docker tag sequra-demo-nginx:$(GIT_COMMIT) $(NGINX_REGISTRY_URI):$(GIT_COMMIT)
	@docker push $(NGINX_REGISTRY_URI):$(GIT_COMMIT)