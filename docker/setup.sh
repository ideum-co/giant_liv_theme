#!/bin/bash
set -e

echo "=========================================="
echo "  Magento 2 Local Setup Script"
echo "=========================================="

# Wait for MySQL to be ready
echo "[1/7] Waiting for MySQL..."
until docker exec magento-mysql mysqladmin ping -h localhost -uroot -pmagento_root --silent 2>/dev/null; do
    sleep 2
done
echo "  ✓ MySQL is ready"

# Import database dump
echo "[2/7] Importing database..."
if docker exec magento-mysql mysql -uroot -pmagento_root -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'magento'" 2>/dev/null | grep -q "^0$"; then
    echo "  Importing from dump file..."
    docker exec -i magento-mysql bash -c 'gunzip < /docker-entrypoint-initdb.d/source/magento-db-dump.sql.gz | mysql -uroot -pmagento_root magento'
    echo "  ✓ Database imported"
else
    echo "  ✓ Database already has data, skipping import"
fi

# Install composer dependencies
echo "[3/7] Installing Composer dependencies..."
docker exec magento-php bash -c 'cd /var/www/html && composer install --no-interaction 2>&1 | tail -5'
echo "  ✓ Composer dependencies installed"

# Update env.php for local environment
echo "[4/7] Updating env.php for local environment..."
docker exec magento-php php -r "
\$config = include '/var/www/html/app/etc/env.php';
\$config['db']['connection']['default']['host'] = 'mysql';
\$config['db']['connection']['default']['dbname'] = 'magento';
\$config['db']['connection']['default']['username'] = 'magento';
\$config['db']['connection']['default']['password'] = 'magento';
\$config['session']['save'] = 'files';
\$config['lock']['provider'] = 'file';
\$config['cache']['frontend']['default']['backend'] = 'Magento\\\Framework\\\Cache\\\Backend\\\File';
\$output = '<?php' . PHP_EOL . 'return ' . var_export(\$config, true) . ';' . PHP_EOL;
file_put_contents('/var/www/html/app/etc/env.php', \$output);
echo 'env.php updated';
"
echo "  ✓ env.php updated"

# Update base URLs in database
echo "[5/7] Setting base URLs to localhost..."
docker exec -i magento-mysql mysql -umagento -pmagento magento -e "
    UPDATE core_config_data SET value = 'http://localhost/' WHERE path = 'web/unsecure/base_url';
    UPDATE core_config_data SET value = 'http://localhost/' WHERE path = 'web/secure/base_url';
    UPDATE core_config_data SET value = '0' WHERE path = 'web/secure/use_in_frontend';
    UPDATE core_config_data SET value = '0' WHERE path = 'web/secure/use_in_adminhtml';
    UPDATE core_config_data SET value = 'http://localhost/' WHERE path LIKE '%base_link_url%';
" 2>/dev/null
echo "  ✓ Base URLs set to http://localhost/"

# Setup Magento
echo "[6/7] Running Magento setup (compile, static content)..."
docker exec magento-php bash -c '
    cd /var/www/html
    php bin/magento setup:upgrade 2>&1 | tail -3
    php bin/magento setup:di:compile 2>&1 | tail -3
    php bin/magento setup:static-content:deploy -f 2>&1 | tail -3
    php bin/magento cache:flush
'
echo "  ✓ Magento setup complete"

# Fix permissions
echo "[7/7] Fixing file permissions..."
docker exec magento-php bash -c '
    cd /var/www/html
    find var generated pub/static pub/media app/etc -type f -exec chmod g+w {} + 2>/dev/null || true
    find var generated pub/static pub/media app/etc -type d -exec chmod g+ws {} + 2>/dev/null || true
    chown -R www-data:www-data /var/www/html/var /var/www/html/generated /var/www/html/pub/static /var/www/html/pub/media 2>/dev/null || true
'
echo "  ✓ Permissions fixed"

echo ""
echo "=========================================="
echo "  Setup Complete!"
echo "=========================================="
echo ""
echo "  Frontend: http://localhost/"
echo "  Admin:    http://localhost/admin"
echo "  User:     user"
echo "  Password: bitnami1"
echo ""
