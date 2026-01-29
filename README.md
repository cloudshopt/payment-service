## payment-service
- **Purpose:** Stripe integration (Checkout Session + webhooks).
- **Base path:** `/api/payments`

### Create product database
```
kubectl -n cloudshopt exec -it cloudshopt-mysql-0 -- bash

# mysql -u root -prootpass

CREATE DATABASE cloudshopt_payments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'users'@'%' IDENTIFIED BY 'CHANGE_ME_PASSWORD';
GRANT ALL PRIVILEGES ON cloudshopt_payments.* TO 'users'@'%';
FLUSH PRIVILEGES;
```

### Migrations

```
kubectl exec -n cloudshopt -it deploy/payment-service -c app -- sh
# php artisan migrate
```
