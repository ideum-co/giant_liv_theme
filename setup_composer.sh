mkdir -p /bitnami/magento/.composer
export COMPOSER_HOME=/bitnami/magento/.composer
export COMPOSER_CACHE_DIR=/bitnami/magento
# Crear archivo de autenticación
cat <<EOT > /bitnami/magento/.composer/auth.json
{
  "http-basic": {
    "repo.magento.com": {
      "username": "e553382b4c0ea95c9eacdd85b07ca23f",
      "password": "your_password_here"
    }
  }
}
EOT

# Configurar Composer para usar el nuevo directorio de caché
composer config --global cache-dir /bitnami/magento
