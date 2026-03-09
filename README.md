# Giant LIV Theme - Magento 2

## Northflank Deployment Info

### Services
| Service | Image | Port |
|---------|-------|------|
| giant-magento | shinsenter/magento:php8.3-nginx | 8080 (HTTP), 8443 (TCP) |
| nginx | library/nginx:latest | 80 (HTTP) |
| giant-elasticsearch | bitnami/elasticsearch:7 | 9200, 9300 |

### Database
- **Type:** MySQL 8.0.36
- **Host:** primary.database--nkp6gnv9hqh9.addon.code.run:3306
- **Database name:** magento
- **Volume:** magento-data (60GB SSD)

### Credentials (Northflank Secret: `credentials`)
- MAGENTO_USERNAME: user
- MAGENTO_EMAIL: user@example.com
- ELASTICSEARCH_HOST: giant-elasticsearch
- ELASTICSEARCH_PORT_NUMBER: 9200

## Database Dump
The database dump is located at `db/magento-db-dump.sql.gz`.

To restore:
```bash
gunzip < db/magento-db-dump.sql.gz | mysql -u <user> -p magento
```

## Setup
1. Install dependencies:
```bash
composer install
```

2. Import database dump

3. Generate static content:
```bash
php bin/magento setup:static-content:deploy
```

4. Compile DI:
```bash
php bin/magento setup:di:compile
```

5. Update `app/etc/env.php` with your database credentials

## Excluded from repo (regenerable)
- `vendor/` — run `composer install`
- `generated/` — run `setup:di:compile`
- `pub/static/` — run `setup:static-content:deploy`
- `var/` — cache, logs, sessions
- `pub/media/catalog/` — product images (2.3GB, not included)
