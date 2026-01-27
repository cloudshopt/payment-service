# Payment service

## Create payment database + user on mysql server
```
kubectl -n cloudshopt exec -it cloudshopt-mysql-0 -- bash

mysql -u root -prootpass
```

```
CREATE DATABASE cloudshopt_payments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'users'@'%' IDENTIFIED BY 'userspass';
GRANT ALL PRIVILEGES ON cloudshopt_payments.* TO 'users'@'%';
FLUSH PRIVILEGES;
```

Ustvari še bazo za *dev* okolje
```
CREATE DATABASE cloudshopt_payments_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'users_dev'@'%' IDENTIFIED BY 'userspass';
GRANT ALL PRIVILEGES ON cloudshopt_payments_dev.* TO 'users_dev'@'%';
FLUSH PRIVILEGES;
```

## Crete external secrets for prod and dev
prod:
```
kubectl -n cloudshopt create secret generic payment-service-secrets \
  --from-literal=DB_PASSWORD="userspass" \
  --from-literal=REDIS_PASSWORD="redispass" \
  --dry-run=client -o yaml | kubectl apply -f -
```

dev:
```
kubectl -n cloudshopt-dev create secret generic payment-service-secrets \
  --from-literal=DB_PASSWORD="userspass" \
  --from-literal=REDIS_PASSWORD="redispass" \
  --dry-run=client -o yaml | kubectl apply -f -
```

check for secrets:
```
kubectl get secret -n cloudshopt payment-service-secrets
kubectl get secret -n cloudshopt-dev payment-service-secrets
```

## Install payment-service for prod and dev
prod:
```
helm upgrade --install payment-service ./helm/payment-service \
-n cloudshopt \ 
-f helm/payment-service/values.yaml
```

dev:
```
helm upgrade --install payment-service-dev ./helm/payment-service \
-n cloudshopt-dev \ 
-f helm/payment-service/values-dev.yaml
```



## Migrations

run migrations:
```
kubectl exec -n cloudshopt-dev -it deploy/payment-service-dev -c app -- sh

# php artisan migrate
```
