#!/bin/bash
set -e

echo "=== Giant LIV Magento 2 - Entrypoint ==="

# Wait for MySQL
echo "Waiting for MySQL..."
max_tries=30
count=0
while ! php -r "new PDO('mysql:host=${MYSQL_HOST};port=${MYSQL_PORT:-3306}', '${MYSQL_USER}', '${MYSQL_PASSWORD}');" 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "ERROR: MySQL not available after ${max_tries} attempts"
        exit 1
    fi
    sleep 5
done
echo "MySQL is ready!"

# Wait for Elasticsearch
echo "Waiting for Elasticsearch..."
count=0
while ! curl -sf "http://${ELASTICSEARCH_HOST}:${ELASTICSEARCH_PORT:-9200}/" > /dev/null 2>&1; do
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "ERROR: Elasticsearch not available after ${max_tries} attempts"
        exit 1
    fi
    sleep 5
done
echo "Elasticsearch is ready!"

cd /var/www/html

# Generate env.php if not present (first boot after image build)
if [ ! -f app/etc/env.php ]; then
    echo "=== Configuring Magento (setup:install) ==="
    php -d memory_limit=1536M bin/magento setup:install \
        --base-url="${MAGENTO_BASE_URL}" \
        --db-host="${MYSQL_HOST}:${MYSQL_PORT:-3306}" \
        --db-name="${MYSQL_DATABASE}" \
        --db-user="${MYSQL_USER}" \
        --db-password="${MYSQL_PASSWORD}" \
        --admin-firstname="${MAGENTO_ADMIN_FIRSTNAME:-Admin}" \
        --admin-lastname="${MAGENTO_ADMIN_LASTNAME:-Giant}" \
        --admin-email="${MAGENTO_ADMIN_EMAIL:-admin@giant.com}" \
        --admin-user="${MAGENTO_ADMIN_USER:-admin}" \
        --admin-password="${MAGENTO_ADMIN_PASSWORD:-Giant2026!}" \
        --language="${MAGENTO_LANGUAGE:-es_CO}" \
        --currency="${MAGENTO_CURRENCY:-COP}" \
        --timezone="${MAGENTO_TIMEZONE:-America/Bogota}" \
        --use-rewrites=1 \
        --search-engine=elasticsearch7 \
        --elasticsearch-host="${ELASTICSEARCH_HOST}" \
        --elasticsearch-port="${ELASTICSEARCH_PORT:-9200}" \
        --backend-frontname="${MAGENTO_BACKEND_FRONTNAME:-admin}" \
        --cleanup-database \
        --no-interaction
    echo "=== Install complete ==="
    
    echo "=== Deploying static content ==="
    php -d memory_limit=1536M bin/magento setup:static-content:deploy es_CO en_US -f -j 1
else
    echo "=== Magento already configured ==="
    php -d memory_limit=1536M bin/magento cache:flush 2>/dev/null || true
fi

# Ensure correct permissions
chown -R www-data:www-data var generated pub/static pub/media 2>/dev/null || true

# Create nginx dirs
mkdir -p /etc/nginx/sites-enabled /var/log/nginx /run

echo "=== Starting services ==="
exec "$@"
