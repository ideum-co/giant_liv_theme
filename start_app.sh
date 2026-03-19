#!/bin/bash

WORKSPACE=/home/runner/workspace
MYSQL_DATA=$WORKSPACE/mysql-data-persistent
MYSQL_RUN=/home/runner/mysql-run
MYSQL_SOCK=$MYSQL_RUN/mysql.sock

echo "=== Starting Magento Setup ==="

# Start a temporary PHP server immediately so the port opens fast (deployment health check)
echo "Starting temporary health-check server on port 5000..."
php -S 0.0.0.0:5000 -t $WORKSPACE/pub/ $WORKSPACE/phpserver/router.php &
TEMP_PHP_PID=$!
echo "Temp PHP started (PID $TEMP_PHP_PID)"

mkdir -p $MYSQL_DATA $MYSQL_RUN

# Kill any existing mysqld
pkill -f "mysqld --user=runner" 2>/dev/null || true
sleep 1
rm -f $MYSQL_SOCK $MYSQL_RUN/*.pid 2>/dev/null || true

echo "Starting MariaDB..."
mysqld --user=runner \
  --datadir=$MYSQL_DATA \
  --socket=$MYSQL_SOCK \
  --port=3307 \
  --bind-address=127.0.0.1 \
  --skip-grant-tables \
  --skip-networking=0 \
  --innodb_buffer_pool_size=512M \
  --innodb_log_file_size=128M \
  --innodb_flush_log_at_trx_commit=2 \
  --innodb_flush_method=O_DIRECT \
  --innodb_io_capacity=200 \
  --query_cache_type=1 \
  --query_cache_size=128M \
  --query_cache_limit=4M \
  --tmp_table_size=128M \
  --max_heap_table_size=128M \
  --table_open_cache=4000 \
  --thread_cache_size=16 \
  --join_buffer_size=4M \
  --sort_buffer_size=4M \
  --read_buffer_size=2M \
  --read_rnd_buffer_size=4M \
  --max_connections=50 \
  --key_buffer_size=32M &

# Wait for MySQL socket
echo "Waiting for MySQL..."
for i in $(seq 1 30); do
  if mysql -u root --socket=$MYSQL_SOCK --connect-timeout=1 -e "SELECT 1;" >/dev/null 2>&1; then
    echo "MySQL ready after ${i}s"
    break
  fi
  sleep 1
done

# Check if MySQL is actually running
if ! mysql -u root --socket=$MYSQL_SOCK --connect-timeout=3 -e "SELECT 1;" >/dev/null 2>&1; then
  echo "ERROR: MySQL failed to start!"
  exit 1
fi

# Create magento database (safe to run multiple times)
mysql -u root --socket=$MYSQL_SOCK -e "
CREATE DATABASE IF NOT EXISTS magento CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
" 2>&1 || true

# Import Magento DB if empty
MAGENTO_TABLES=$(mysql -u root --socket=$MYSQL_SOCK -e "SHOW TABLES FROM magento;" 2>/dev/null | wc -l || echo "0")
echo "Magento tables: $MAGENTO_TABLES"

if [ "$MAGENTO_TABLES" -lt 5 ]; then
  echo "Importing Magento database dump (this may take a few minutes)..."
  zcat $WORKSPACE/db/magento-db-dump.sql.gz | mysql -u root --socket=$MYSQL_SOCK magento 2>&1
  echo "Database import complete"

  # Update base URLs after fresh import
  if [ -n "$REPLIT_DEV_DOMAIN" ]; then
    NEW_URL="https://$REPLIT_DEV_DOMAIN/"
    echo "Updating base URL to: $NEW_URL"
    mysql -u root --socket=$MYSQL_SOCK magento -e "
    UPDATE core_config_data SET value = '$NEW_URL' WHERE path = 'web/secure/base_url';
    UPDATE core_config_data SET value = '$NEW_URL' WHERE path = 'web/unsecure/base_url';
    UPDATE core_config_data SET value = '' WHERE path = 'web/cookie/cookie_domain';
    " 2>&1
  fi
fi

# Install Composer dependencies if needed
if [ ! -d "$WORKSPACE/vendor" ]; then
  echo "Installing Composer dependencies (this may take several minutes)..."
  cd $WORKSPACE
  COMPOSER_HOME=$WORKSPACE/.composer \
  COMPOSER_CACHE_DIR=/home/runner/.composer-cache \
  composer install \
    --no-interaction \
    --no-dev \
    --prefer-dist \
    --no-progress \
    --ignore-platform-req=ext-xsl \
    2>&1 | tail -30
  echo "Composer install complete"
fi

# Apply vendor patches (esconfig.xsd/xml fix for schema validation)
ESCONFIG_XML="$WORKSPACE/vendor/magento/module-elasticsearch/etc/esconfig.xml"
if grep -q '<type>stemmer</type>' "$ESCONFIG_XML" 2>/dev/null && head -14 "$ESCONFIG_XML" | grep -q '<type>'; then
  echo "Applying esconfig.xml patch (reorder default before type)..."
  sed -i '/<stemmer>/,/<\/stemmer>/{
    s|<type>stemmer</type>|__PLACEHOLDER_TYPE__|
    s|<default>\(.*\)</default>|<default>\1</default>\n        <type>stemmer</type>|
    /__PLACEHOLDER_TYPE__/d
  }' "$ESCONFIG_XML"
fi

ESCONFIG_XSD="$WORKSPACE/vendor/magento/module-elasticsearch/etc/esconfig.xsd"
if grep -q 'xs:choice' "$ESCONFIG_XSD" 2>/dev/null && grep -q 'mixedDataType' "$ESCONFIG_XSD"; then
  if grep -q '<xs:choice maxOccurs="unbounded" minOccurs="1">' "$ESCONFIG_XSD" 2>/dev/null; then
    if grep -A2 'mixedDataType">' "$ESCONFIG_XSD" | grep -q 'xs:choice'; then
      echo "Applying esconfig.xsd patch (fix non-deterministic content model)..."
      sed -i '/<xs:complexType name="mixedDataType">/,/<\/xs:complexType>/{
        s|<xs:choice|<xs:sequence|
        s|</xs:choice|</xs:sequence|
      }' "$ESCONFIG_XSD"
    fi
  fi
fi

# Start OpenSearch in background
echo "Starting OpenSearch..."
mkdir -p /home/runner/opensearch-data /home/runner/opensearch-logs /home/runner/opensearch-config
OPENSEARCH_HOME=$(dirname $(dirname $(which opensearch)))
export JAVA_HOME=/nix/store/z8k0md5rbj2m415705kpb4fihp8kcd11-openjdk-headless-21.0.7+6

if [ ! -f /home/runner/opensearch-config/opensearch.yml ]; then
  cat > /home/runner/opensearch-config/opensearch.yml << 'OSEOF'
cluster.name: magento-cluster
node.name: node-1
path.data: /home/runner/opensearch-data
path.logs: /home/runner/opensearch-logs
network.host: 127.0.0.1
http.port: 9200
discovery.type: single-node
plugins.security.disabled: true
indices.query.bool.max_clause_count: 10240
OSEOF
  cp $OPENSEARCH_HOME/config/jvm.options /home/runner/opensearch-config/jvm.options
  cp $OPENSEARCH_HOME/config/log4j2.properties /home/runner/opensearch-config/log4j2.properties
  cp -r $OPENSEARCH_HOME/config/jvm.options.d /home/runner/opensearch-config/ 2>/dev/null || true
  sed -i 's/-Xms[0-9]*[gm]/-Xms256m/' /home/runner/opensearch-config/jvm.options
  sed -i 's/-Xmx[0-9]*[gm]/-Xmx256m/' /home/runner/opensearch-config/jvm.options
  sed -i "s|logs/gc.log|/home/runner/opensearch-logs/gc.log|g" /home/runner/opensearch-config/jvm.options
fi

pkill -f 'org.opensearch.bootstrap.OpenSearch' 2>/dev/null || true
sleep 1

export OPENSEARCH_HOME
export OPENSEARCH_PATH_CONF=/home/runner/opensearch-config
$OPENSEARCH_HOME/bin/.opensearch-wrapped > /home/runner/opensearch-logs/startup.log 2>&1 &

echo "Waiting for OpenSearch..."
for i in $(seq 1 60); do
  if curl -s http://127.0.0.1:9200/ > /dev/null 2>&1; then
    echo "OpenSearch ready after ${i}s"
    break
  fi
  sleep 2
done

if curl -s http://127.0.0.1:9200/ > /dev/null 2>&1; then
  echo "OpenSearch is running"
else
  echo "WARNING: OpenSearch not available, layered navigation may not work"
fi

# Update search engine config to opensearch
mysql -u root --socket=$MYSQL_SOCK magento -e "
UPDATE core_config_data SET value='opensearch' WHERE path='catalog/search/engine' AND scope='default' AND scope_id=0;
INSERT INTO core_config_data (scope, scope_id, path, value) VALUES
  ('default', 0, 'catalog/search/opensearch_server_hostname', '127.0.0.1'),
  ('default', 0, 'catalog/search/opensearch_server_port', '9200'),
  ('default', 0, 'catalog/search/opensearch_index_prefix', 'magento2'),
  ('default', 0, 'catalog/search/opensearch_enable_auth', '0'),
  ('default', 0, 'catalog/search/opensearch_server_timeout', '15')
ON DUPLICATE KEY UPDATE value=VALUES(value);
" 2>&1 || true

# Register Giant custom modules (setup:upgrade can't run without Elasticsearch)
mysql -u root --socket=$MYSQL_SOCK magento -e "
INSERT INTO setup_module (module, schema_version, data_version) VALUES
('Giant_BikeRegistration', '1.0.0', '1.0.0'),
('Giant_Dealers', '1.0.0', '1.0.0'),
('Giant_MysqlSearch', '1.0.0', '1.0.0')
ON DUPLICATE KEY UPDATE schema_version='1.0.0', data_version='1.0.0';
" 2>&1 || true

# Create Giant module tables if they don't exist
mysql -u root --socket=$MYSQL_SOCK magento -e "
CREATE TABLE IF NOT EXISTS giant_bike_registration (
    registration_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL, document_type VARCHAR(50) NOT NULL,
    document_number VARCHAR(50) NOT NULL, address VARCHAR(500) DEFAULT NULL,
    gender VARCHAR(30) DEFAULT NULL, phone VARCHAR(30) NOT NULL,
    email VARCHAR(255) NOT NULL, birthday DATE DEFAULT NULL,
    department_city VARCHAR(255) NOT NULL, store_name VARCHAR(255) NOT NULL,
    purchase_city VARCHAR(255) NOT NULL, purchase_date DATE NOT NULL,
    bike_reference VARCHAR(255) NOT NULL, serial_number VARCHAR(100) NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL, invoice_number VARCHAR(100) NOT NULL,
    invoice_file VARCHAR(500) DEFAULT NULL, download_token VARCHAR(64) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (registration_id), INDEX (email), INDEX (serial_number)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS giant_dealers (
    dealer_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL, logo VARCHAR(500) DEFAULT NULL,
    city VARCHAR(255) NOT NULL, address VARCHAR(500) NOT NULL,
    phones VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL,
    latitude DECIMAL(11,8) DEFAULT NULL, longitude DECIMAL(11,8) DEFAULT NULL,
    is_active SMALLINT UNSIGNED NOT NULL DEFAULT 1, sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (dealer_id), INDEX (city), INDEX (is_active)
) ENGINE=InnoDB;
" 2>&1 || true

# Ensure thumbcarousel column exists (required by Athlete2 theme)
mysql -u root --socket=$MYSQL_SOCK magento -e "
ALTER TABLE catalog_product_entity_media_gallery ADD COLUMN IF NOT EXISTS thumbcarousel smallint(5) unsigned NOT NULL DEFAULT 0;
" 2>&1 || true

# Set placeholder images for missing product photos
mysql -u root --socket=$MYSQL_SOCK magento -e "
INSERT INTO core_config_data (scope, scope_id, path, value) VALUES
  ('default', 0, 'catalog/placeholder/image_placeholder', '/placeholder/placeholder.png'),
  ('default', 0, 'catalog/placeholder/small_image_placeholder', '/placeholder/placeholder.png'),
  ('default', 0, 'catalog/placeholder/thumbnail_placeholder', '/placeholder/placeholder.png'),
  ('default', 0, 'catalog/placeholder/swatch_image_placeholder', '/placeholder/placeholder.png')
ON DUPLICATE KEY UPDATE value=VALUES(value);
" 2>&1 || true

# Run setup:upgrade only if there are pending schema changes
echo "Checking for pending Magento schema updates..."
cd $WORKSPACE
NEEDS_UPGRADE=$(php -d memory_limit=756M bin/magento setup:db:status 2>&1)
if echo "$NEEDS_UPGRADE" | grep -qi "not up to date\|please upgrade\|outdated"; then
  echo "Running Magento setup:upgrade..."
  php -d memory_limit=756M bin/magento setup:upgrade --keep-generated 2>&1 | tail -10 || true
  rm -rf $WORKSPACE/var/cache/* $WORKSPACE/var/page_cache/* 2>/dev/null || true
else
  echo "Database schema is up to date, skipping setup:upgrade"
fi

# Keep developer mode (production mode requires full di:compile which exceeds container limits)
echo "Running in developer mode"
sed -i "s/'MAGE_MODE' => 'production'/'MAGE_MODE' => 'developer'/" $WORKSPACE/app/etc/env.php 2>/dev/null || true

# Disable minify/merge in developer mode (causes missing file errors without static-content:deploy)
mysql -u root --socket=$MYSQL_SOCK magento -e "
UPDATE core_config_data SET value = '0' WHERE path IN ('dev/js/minify_files','dev/css/minify_files','dev/js/merge_files','dev/css/merge_css_files','dev/template/minify_html');
" 2>&1 || true

# Deploy static content if not already deployed
STATIC_DIR=$WORKSPACE/pub/static/frontend/Olegnax/athlete2/es_ES
if [ ! -f "$STATIC_DIR/requirejs/require.js" ] 2>/dev/null; then
  echo "Deploying static content..."
  php -d memory_limit=1G bin/magento setup:static-content:deploy es_ES -f --jobs 1 2>&1 | tail -10 || true
fi

# Copy custom CSS to static
cp -f $WORKSPACE/app/design/frontend/Olegnax/athlete2/web/css/giant.css $STATIC_DIR/css/giant.css 2>/dev/null || true
printf '%s' "$(date +%s)" > $WORKSPACE/pub/static/deployed_version.txt

# Reindex if OpenSearch is available
if curl -s http://127.0.0.1:9200/ > /dev/null 2>&1; then
  echo "Running Magento indexer..."
  cd $WORKSPACE
  php -d memory_limit=756M bin/magento indexer:reindex catalogsearch_fulltext catalog_product_attribute 2>&1 | tail -5 || true
fi

# Truncate large log files to prevent I/O overhead
: > $WORKSPACE/var/log/system.log 2>/dev/null || true
: > $WORKSPACE/var/log/exception.log 2>/dev/null || true
: > $WORKSPACE/var/log/debug.log 2>/dev/null || true

# Now kill the temporary PHP server and start the real one with proper config
echo "Restarting PHP server with full Magento config..."
kill $TEMP_PHP_PID 2>/dev/null || true
sleep 1

echo "Starting PHP server on port 5000..."
cd $WORKSPACE

# Warmup: make background requests to pre-generate common pages
(sleep 5 && curl -s http://localhost:5000/ > /dev/null 2>&1 && \
 curl -s http://localhost:5000/bicicletas.html > /dev/null 2>&1 && \
 echo "Cache warmup complete") &

exec php -c $WORKSPACE/php-magento.ini -S 0.0.0.0:5000 -t pub/ phpserver/router.php
