# Giant LIV - Magento 2 on Northflank

Deployment automatizado de Magento 2 con el tema Giant LIV en Northflank, incluyendo MySQL, Elasticsearch y Redis.

## Arquitectura

```
┌─────────────────────────────────────────────┐
│                 Northflank                   │
│                                              │
│  ┌──────────────┐    ┌──────────────────┐   │
│  │  Build Svc   │───▶│  Deploy Service  │   │
│  │  (Dockerfile) │    │  (Magento 2 App) │   │
│  └──────────────┘    └───────┬──────────┘   │
│        │                     │               │
│        │              ┌──────┴──────┐        │
│  ┌─────▼─────┐   ┌───▼────┐  ┌────▼─────┐  │
│  │  GitHub    │   │ MySQL  │  │ Elastic  │  │
│  │  Repo      │   │ 8.0   │  │ 7.17     │  │
│  └───────────┘   └────────┘  └──────────┘  │
│                       │                      │
│                  ┌────▼─────┐                │
│                  │  Redis   │                │
│                  │  7.0     │                │
│                  └──────────┘                │
└──────────────────────────────────────────────┘
```

## Requisitos Previos

1. **Cuenta en Northflank** con un API token ([crear aquí](https://app.northflank.com/account/api))
2. **GitHub** conectado como integración en Northflank (Settings > Integrations)
3. **Magento Auth Keys** de [repo.magento.com](https://commercemarketplace.adobe.com/customer/accessKeys/)
4. `curl`, `python3` y `bash` instalados localmente

## Inicio Rápido

### 1. Configurar variables de entorno

```bash
cp .env.example .env
# Editar .env con tus valores:
#   - NORTHFLANK_API_TOKEN
#   - MAGENTO_PUBLIC_KEY / MAGENTO_PRIVATE_KEY
#   - Datos de admin de Magento
```

### 2. Ejecutar setup completo

```bash
chmod +x scripts/*.sh
./scripts/northflank-setup.sh
```

Esto crea automáticamente en Northflank:
- Proyecto
- Addon MySQL 8.0
- Addon Elasticsearch 7.17
- Addon Redis 7.0
- Secret Group con todas las variables
- Build Service (desde GitHub + Dockerfile)
- Deployment Service con puerto público

### 3. Trigger del primer build

```bash
./scripts/northflank-trigger-build.sh
```

### 4. Verificar estado

```bash
./scripts/northflank-status.sh
```

## Scripts Disponibles

| Script | Descripción |
|--------|-------------|
| `scripts/northflank-setup.sh` | Crea toda la infraestructura en Northflank |
| `scripts/northflank-trigger-build.sh` | Dispara un nuevo build |
| `scripts/northflank-status.sh` | Muestra el estado de todos los recursos |
| `scripts/northflank-destroy.sh` | **⚠️ Elimina todo** el proyecto de Northflank |

## Desarrollo Local

Para desarrollo local con Docker Compose:

```bash
cp .env.example .env
# Configurar MAGENTO_PUBLIC_KEY y MAGENTO_PRIVATE_KEY
docker-compose up -d
```

Acceder a: `http://localhost:8080`
Admin: `http://localhost:8080/admin`

## Estructura del Proyecto

```
├── Dockerfile                    # Build multi-stage para Magento 2
├── docker-compose.yml            # Desarrollo local
├── docker/
│   ├── entrypoint.sh            # Script de inicio (install/upgrade)
│   ├── nginx/
│   │   ├── nginx.conf           # Configuración principal de Nginx
│   │   └── magento.conf         # Virtual host de Magento
│   ├── php/
│   │   ├── magento.ini          # PHP settings optimizados
│   │   └── php-fpm.conf         # Pool de PHP-FPM
│   └── supervisor/
│       └── supervisord.conf     # Supervisor (nginx + php-fpm + cron)
├── scripts/
│   ├── northflank-setup.sh      # Setup completo via API
│   ├── northflank-trigger-build.sh
│   ├── northflank-status.sh
│   └── northflank-destroy.sh
├── .env.example                  # Template de configuración
└── .gitignore
```

## Notas Importantes

- **GitHub Integration**: Debe estar conectado en Northflank *antes* de ejecutar el setup
- **Magento Keys**: Son obligatorias para instalar dependencias de `repo.magento.com`
- **Primera instalación**: Puede tomar 10-15 minutos mientras compila assets
- **Base URL**: Actualizar `MAGENTO_BASE_URL` con el dominio asignado por Northflank
- **Plan de Northflank**: Los addons usan `nf-compute-20` (ajustar en el script según tu plan)

## Solución de Problemas

```bash
# Ver logs del deployment
curl -s -H "Authorization: Bearer $NORTHFLANK_API_TOKEN" \
  https://api.northflank.com/v1/projects/<PROJECT_ID>/services/<SERVICE_ID>/logs

# Verificar estado de addons
./scripts/northflank-status.sh

# Recrear desde cero
./scripts/northflank-destroy.sh
./scripts/northflank-setup.sh
```
