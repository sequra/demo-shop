GIT_COMMIT ?= $(shell git rev-parse --short=8 HEAD)
.PHONY: production-container-build production-container-push production-nginx-build production-nginx-push

production-container-build:
	echo '$(AUTH_JSON)' > /tmp/composer_auth.json
	docker build --secret id=composer_auth,src=/tmp/composer_auth.json -f docker/common/Dockerfile-app -t sequra-demo-app:$(GIT_COMMIT) .
	rm -f /tmp/composer_auth.json

production-container-push:
	@docker tag sequra-demo-app:$(GIT_COMMIT) $(APP_REGISTRY_URI):$(GIT_COMMIT)
	@docker push $(APP_REGISTRY_URI):$(GIT_COMMIT)

production-nginx-build:
	docker build \
		--build-arg APP_IMAGE=sequra-demo-app:$(GIT_COMMIT) \
		-f docker/ecs/Dockerfile-nginx \
		-t sequra-demo-nginx:$(GIT_COMMIT) .

production-nginx-push:
	@docker tag sequra-demo-nginx:$(GIT_COMMIT) $(NGINX_REGISTRY_URI):$(GIT_COMMIT)
	@docker push $(NGINX_REGISTRY_URI):$(GIT_COMMIT)