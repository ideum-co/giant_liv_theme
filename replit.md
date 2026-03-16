# Magento 2 E-Commerce Store

## Project Overview
This is a Magento 2.4.6 Community Edition e-commerce store for Giant/Liv/Momentum bicycles (Colombia market). It's a PHP-based e-commerce platform with a MySQL database.

## Architecture
- **Framework**: Magento 2.4.6-p4 Community Edition
- **Language**: PHP 8.2
- **Database**: MariaDB 10.11 (local)
- **Web Server**: PHP built-in server (dev) on port 5000
- **Session Storage**: Files
- **Lock Provider**: Files

## Key Files
- `start_app.sh` - Main startup script that starts MariaDB and PHP server
- `app/etc/env.php` - Magento environment configuration (database, cache, session settings)
- `composer.json` - PHP dependencies
- `pub/index.php` - Magento entry point
- `phpserver/router.php` - PHP built-in server router

## Database Setup
- MariaDB runs locally on socket `/home/runner/mysql-run/mysql.sock` and port 3307
- Database name: `magento`
- User: `root` (no password, skip-grant-tables mode)
- Data directory: `/home/runner/workspace/mysql-data-persistent/` (persistent across restarts)
- Initial dump: `db/magento-db-dump.sql.gz` (auto-imported on first run)

## Running the Application
The `Start application` workflow runs `bash /home/runner/workspace/start_app.sh` which:
1. Starts MariaDB in background
2. Imports the database dump if empty
3. Updates Magento base URLs to current Replit domain
4. Installs Composer dependencies if vendor/ missing
5. Starts PHP built-in server on port 5000

## Configuration Notes
- Base URL is set dynamically using `$REPLIT_DEV_DOMAIN` environment variable
- x-frame-options set to ALLOWALL for Replit proxy compatibility
- Lock provider configured as 'file' with path `/home/runner/workspace/var/locks`
- Session save set to 'files'
- Cache cleared on each startup
- MAGE_MODE set to 'developer' for debugging visibility
- **Search engine**: OpenSearch 2.19.2 installed via Nix. Config at `/home/runner/opensearch-config/`, data at `/home/runner/opensearch-data/`, logs at `/home/runner/opensearch-logs/`. JAVA_HOME: `/nix/store/z8k0md5rbj2m415705kpb4fihp8kcd11-openjdk-headless-21.0.7+6`. Started via `$OPENSEARCH_HOME/bin/.opensearch-wrapped` in start_app.sh. JVM memory: 256MB. Security disabled, single-node discovery. Magento engine set to `opensearch` in core_config_data.
  - `Giant_MysqlSearch` module still present as fallback but not actively used with OpenSearch running
  - Reindex runs on startup: `catalogsearch_fulltext catalog_product_attribute` (with `-d memory_limit=756M`)
  - Missing DB column `thumbcarousel` added to `catalog_product_entity_media_gallery` (required by Olegnax theme)
- **Product images**: Automated image recovery completed — 587 images downloaded from Giant CDN (images2.giant-bicycles.com), 234 products fixed via SKU matching + family inheritance + fuzzy matching. Coverage improved from 43.5% to 58.7% (1,402/2,387 products). Remaining 96 configurable + 889 simple products still missing (mostly used generic `image_XX` admin uploads, no external source). Placeholder image configured at `pub/media/catalog/product/placeholder/placeholder.png`. Start_app.sh sets placeholder config in core_config_data on each startup.
- XSD fix applied: esconfig.xml reordered `<default>` before `<type>` to fix schema validation
- External JS/CSS from `static.giant-bicycles.com` blocked by CORS — expected behavior in dev
- **Checkout JS fix**: External scripts from `static.giant-bicycles.com` (common.js, custom.js, swiper.js) were in `design/footer/absolute_footer` config and loaded on ALL pages, causing "Mismatched anonymous define()" RequireJS error that blocked payment components (MercadoPago). Fix: cleared `absolute_footer`, moved scripts to `giant-external-scripts.phtml` template (added via `default.xml` layout), and excluded from checkout via `checkout_index_index.xml` (`<referenceBlock name="giant.external.scripts" remove="true"/>`)
- **DeferJS system**: `deferjs=1`, `deferjs_combine_inline=1` — moves ALL `<script>` tags to before `</body>`; combined inline scripts may run after DOMContentLoaded in some timing scenarios
- **OX_* variables fix**: `requirejs-config.js` uses `typeof OX_CATALOG_AJAX !== 'undefined'` guards to handle cases where OX_* globals aren't yet defined when config loads (DeferJS timing)
- **Lazy-load race condition fix**: `lazy_min.phtml` and `lazy.phtml` check `document.readyState === 'loading'` before adding DOMContentLoaded listener (handles deferjs_combine_inline scenario)
- **imageTemplate fix**: `Magento_Catalog/js/view/image.js` guards `window.checkout` access to avoid crash on non-checkout pages
- **AOS library fix**: Removed `<script src="aos.js">` from layout XML (caused "Mismatched anonymous define()" error in RequireJS); AOS now loaded via RequireJS paths/shim in theme `requirejs-config.js`; `aos-init.js` updated to `require(['jquery', 'aos'], ...)`
- **Amasty_Xsearch theme override removed**: LESS files imported `_mixins.less` from Amasty module (not installed), which broke CSS compilation for `styles-m.css`/`styles-l.css` entirely; Amasty_CheckoutCore (CSS only), Amasty_CheckoutThankYouPage (layout XML), Amasty_Shopby (self-contained LESS) remain safe
- **Router MIME fix**: `phpserver/router.php` now sets Content-Type header before `static.php` fallback (for CSS/JS files compiled on-the-fly in developer mode)
- **Known issue**: RevSlider hero on homepage has `visibility:hidden` until its JS initializes; DeferJS timing means `rs6loader` may not be ready when inline init scripts execute — slider stays hidden
- **Known issue (resolved)**: Minicart component error is no longer appearing after AOS fix eliminated the "Mismatched anonymous define()" error that was disrupting RequireJS module loading

## Custom Header Design
The header has been redesigned to match the Giant Bicycles reference:
- **Layout**: Hamburger icon + nav links (left), centered Giant logo, BUSCAR search button + cart icon (right)
- **Homepage**: Dark semi-transparent background (`rgba(0,0,0,0.75)`), white text/icons, `position: absolute` overlaying content
- **Other pages**: White background, dark text/icons, normal flow
- **Key CSS files** (load order: aos.css → giant-global.css → giant-global-async.css → styles-l.css → giant.css):
  - `app/design/frontend/Olegnax/athlete2/web/css/giant-global.css` — full Giant/Bootstrap 3 + Glyphicons base styles (775KB, from static.giant-bicycles.com)
  - `app/design/frontend/Olegnax/athlete2/web/css/giant-global-async.css` — async Giant styles (53KB)
  - `app/design/frontend/Olegnax/athlete2/web/css/giant.css` — overrides only (Magento-specific fixes)
  - `app/design/frontend/Olegnax/athlete2/web/css/giant-header-override.css` — loads before styles-l.css
- **Key template**: `app/design/frontend/Olegnax/athlete2/Olegnax_Athlete2/templates/html/header/header_2.phtml`
  - Hamburger, nav links, BUSCAR button added directly as server-side HTML (no JS dependency)
  - Inline script fixes lazy-load images in header (CORS blocks external lazy-load JS on homepage)
- **Key JS**: `app/design/frontend/Olegnax/athlete2/web/js/giant-header.js` — handles interactive behaviors (slide menu, click handlers) and scroll-based header state (adds `.giant-scrolled` class after 50px scroll, switching homepage transparent header to solid white)
- **Info bar modals**: Homepage has "ENVÍO GRATUITO", "DEVOLUCIONES GRATUITAS", "CLICK & COLLECT" info bar (`.content-wrapper-topbaritems`) rendered from CMS page builder content. Uses `.faq-modal.modal` classes for popup details. Custom `bootstrap-modal.js` RequireJS module handles `data-toggle="modal"` clicks since Bootstrap JS isn't loaded. Base modal CSS provided by `giant-global.css`; `giant.css` adds only `.modal.in` display toggle and custom `.faq-modal-backdrop` (intentionally not `.modal-backdrop` to avoid Magento modal system conflicts). Init template: `Olegnax_Athlete2/templates/bootstrap-modal-init.phtml`
- **Important CSS fix**: Inner wrapper elements (`.sticky-wrapper`, `.container`, `.row`, `.col`, `.header__content-wrapper`, `.header__content`) must have `background-color: transparent !important` on homepage to prevent them from covering the dark page-header background
- **Lazy-load workaround**: Homepage JS fails due to CORS-blocked external scripts; inline script in header_2.phtml converts `data-original` to `src` for header images
- **Banner ticker**: `header-banner-below` moved to top of page (above header) via layout XML change in `default.xml`. Dark background (#333), white text. PHP `$stripInlineColor` function in `header_banner.phtml` strips inline `color: #333 !important` from CMS content and replaces with white
- **Footer redesign**: Custom `footer.phtml` replaces CMS block with hardcoded HTML matching Giant reference design. Dark blue (#0a0a5c) background, 4 columns (Varios, Soporte, Empresa, Newsletter), GIANT watermark SVG, bottom bar with Colombia country selector, social icons, copyright. Old footer elements (above_footer, copyright, newsletter wrapper) hidden via CSS.
- **Category banner**: Full-width dark hero banner on category pages (`giant_banner.phtml` template, `catalog_category_view.xml` layout). Category title overlaid at bottom-left. Original page.main.title, category.image, category.description blocks removed. Messages page-main and ox-nav-sections hidden via `display: none`. Banner uses `margin-top: -25px` to close gap from page-header extra height. Layout places banner in `page.wrapper` before `main.content`.
- **CSS symlinks**: giant.css and giant-header-override.css must be copied from source to `pub/static/frontend/Olegnax/athlete2/es_ES/css/` after every change using: `cp -f giant.css pub/static/frontend/Olegnax/athlete2/es_ES/css/giant.css`
- **Minicart/Cart/Checkout CSS**: Comprehensive styling for minicart sidebar dropdown, cart page, checkout page, and empty cart state — all using Giant brand colors (#06038d blue) and Overpass font. **Checkout redesigned as Bootstrap-style accordion** matching Giant Global reference (giant-bicycles.com/es/shop/review): `#checkoutSteps` has grouped border container (1px solid #d9d9d9), step titles as full-width panel headers with CSS counter numbering ("1. Shipping Address", "2. Shipping Methods", "3. Payment Method"), chevron indicators (►/▼), #fafafa background for inactive steps, blue text for active step. Inactive step content hidden via `#checkoutSteps > li:not(._active) .step-content { display:none }` (uses Magento's KO-driven `._active` class). Payment template override (`Magento_Checkout/web/template/payment.html`) adds `.step-title` div for accordion consistency. Custom `onepage.phtml` adds "PROCESO DE COMPRA" page title. Sticky order summary sidebar with product thumbnails (56×56px). Input/button overrides handle Olegnax `.inputs-style--underlined` body class with high-specificity selectors for bordered inputs (1px solid #ccc, white bg, 44px height). Buttons: primary filled blue (#06038d, 0 radius square, 700 weight), secondary outlined blue, cancel/remind underlined links. Authentication wrapper kept visible for returning customers. Mobile sidebar modal header preserved for close functionality
- **i18n translations**: `app/design/frontend/Olegnax/athlete2/i18n/es_ES.csv` (100+ checkout/cart translations). JS translations deployed to `pub/static/frontend/Olegnax/athlete2/es_ES/js-translation.json` (must be updated manually since full static-content:deploy is too heavy for dev). Covers: checkout form labels, order summary, shipping methods, validation messages, billing address, authentication
- **PDP styling**: Product title, price, and "Add to Cart" button styled with brand colors
- **PDP service bar (bicycle only)**: 3-icon bar (Servicio, Registra tu Bici, Armado Incluido) with modal popups — rendered below similar products section on bicycle PDPs only. Uses `Giant\Checkout\Block\Product\BicycleInfo` block for bicycle detection (checks configured category IDs). Modals contain service info, bike registration links, and assembly details. Price warning text ("Los precios en tienda pueden variar...") appears below service bar. Layout: `Giant_Checkout::catalog_product_view.xml`, templates: `product/service-bar.phtml`, `product/price-warning.phtml`, CSS in `giant.css`
- **PDP custom stock/SKU**: Replaces default "In Stock / SKU" with "Producto # {SKU} | EN STOCK: DISPONIBLE DE 3 A 7 DÍAS HÁBILES." (green) or "AGOTADO" (red). Template: `product/stock-sku.phtml`, uses `Magento\Catalog\Block\Product\View` block. Old `.product-info-stock-sku` hidden via CSS
- **PDP custom review stars**: SVG star icons (blue #06038D filled, grey #DEDEDE empty) with Spanish text. Template override: `Magento_Catalog/templates/product/view/review.phtml`
- **PDP element reordering**: CSS grid on `.product-info-main` — Stars(1)→Title(2)→Category(3)→Price(4)→SizingGuide(5)→Stock/SKU(6)→AddToCart(7)→Social(8)→AfterDesc(9)
- **PDP sizing guide (bicycle only)**: 5-step modal wizard for body measurement-based size recommendation. Trigger: "ELIGE TU TALLA / ¿Cuál es mi talla?" row below price. Steps: (1) Gender + Height + Shoe size, (2) Inseam with slider, (3) Femur with slider, (4) Torso with slider, (5) Arm with slider → Result shows recommended size (XS/S/M/L/XL). Each step 2-5 has "CÓMO MEDIRSE" instructions panel and illustration from static.giant-bicycles.com. Formulas use biomechanical ratios adjusted by gender/shoe size. All JS inline (DeferJS compatible). Template: `product/sizing-guide.phtml`, uses `BicycleInfo` block, CSS in `giant.css`
- **PDP Productos Similares carousel**: Custom `Giant\Checkout\Block\Product\SimilarProducts` block fetches 6 random products from same deepest category. Two-column card layout (image left + info right with subcategory/name/price). CSS scroll-snap carousel with peek effect. Black circle navigation arrows with inline `onclick` handlers. Old CMS widget block (`#similarproducts`, `.similares`) hidden via CSS. Template: `product/similar-products.phtml`, layout: `catalog_product_view.xml` (inside `product.info.social` container, between price warning and service bar — renders in product info column). Flex order: price-warning(1) → similar-products(2) → service-bar(3)
- **Lazy-load fix for widget carousels**: `giant-header.js` includes `triggerLazyImages()` function that forces `data-original` → `src` for lazy images in `.similares`, `.widget-product-carousel`, `.block.related`, and `.block.upsell` containers. Uses MutationObserver to watch for Slick carousel clone nodes, plus Slick `init`/`afterChange` event handlers, plus a single 4s fallback timeout
- **RequireJS fix**: `ox-catalog` and `ox-product` mapped to `js/ox-catalog` and `js/ox-product` in theme `requirejs-config.js` — these modules are loaded via `x-magento-init` in catalog templates but files live in `web/js/` subdirectory

## Custom Modules
- `Giant/BikeRegistration` - Product registration module for bikes
  - **URL**: `/bikeregistration` — multi-step accordion form (Personal Info → Purchase Info → Confirmation)
  - **Database table**: `giant_bike_registration` (21 columns: registration_id, full_name, document_type, document_number, address, gender, phone, email, birthday, department_city, store_name, purchase_city, purchase_date, bike_reference, serial_number, amount_paid, invoice_number, invoice_file, status, created_at, updated_at)
  - **Admin grid**: Customers → Registros de Bicicletas (`bikeregistration/registration/index`)
  - **PDF generation**: Pure PHP PDF (no external lib) for property card download (`Model/Pdf/PropertyCard.php`)
  - **File upload**: Invoice files stored in `pub/media/bike_registration/invoices/`, max 10MB
  - **Key files**:
    - `app/code/Giant/BikeRegistration/Block/RegistrationForm.php` — block with form key injection
    - `app/code/Giant/BikeRegistration/Controller/Index/Save.php` — AJAX form save (CsrfAwareActionInterface)
    - `app/code/Giant/BikeRegistration/Controller/Index/Download.php` — PDF download
    - `app/code/Giant/BikeRegistration/view/frontend/templates/registration_form.phtml` — full form template with JS
    - `app/code/Giant/BikeRegistration/view/frontend/web/css/registration.css` — styling
    - `app/code/Giant/BikeRegistration/view/adminhtml/ui_component/giant_bikeregistration_listing.xml` — admin grid
  - **Registration number format**: `GCO-XXXXXX` (zero-padded registration_id)
- `Giant/Checkout` - Custom 4-step checkout module with distributor selection
  - **4-step accordion checkout**: Personal Info (login/guest) → Shipping → Distributor → Payment
  - **Distributor step**: Only shows when cart contains a bicycle (configured via Admin > Giant > Giant Checkout > Bicycle Category IDs)
  - **Database table**: `giant_distributor` (entity_id, name, address, city, department, phone, email, is_active, sort_order, store_ids)
  - **Department filtering**: Each distributor has a `department` field (Colombian departamento). In checkout Step 3, user selects their department from a dropdown (32 departments). API filters distributors by matching department; if no distributors in that department, shows all distributors with a warning message. Source model: `Giant\Checkout\Model\Config\Source\Departments`
  - **Admin grid**: Giant > Distribuidores (`giant_checkout/distributor/index`) — CRUD + mass delete + mass status; department column with dropdown filter
  - **API endpoint**: `GET /giant/index/distributors?department=X` — returns active distributors (filtered by store + department, only if cart has bicycle)
  - **Layout override**: `checkout_index_index.xml` removes native `checkout.root` and renders `Giant_Checkout::checkout.phtml`
  - **Guest checkout**: Uses `maskedCartId` from Magento checkout session (passed via Block config), NOT a new empty cart
  - **Key files**:
    - `app/code/Giant/Checkout/Block/Checkout.php` — block with cart/customer helpers and JS config (maskedCartId, hasBicycle, urls)
    - `app/code/Giant/Checkout/Helper/Cart.php` — bicycle detection logic (checks category IDs from admin config)
    - `app/code/Giant/Checkout/view/frontend/templates/checkout.phtml` — full checkout template
    - `app/code/Giant/Checkout/view/frontend/web/js/giant-checkout.js` — vanilla JS checkout controller
    - `app/code/Giant/Checkout/view/frontend/web/css/giant-checkout.css` — checkout styling
    - `app/code/Giant/Checkout/Controller/Index/Distributors.php` — AJAX distributor endpoint
    - `app/code/Giant/Checkout/Model/DistributorRepository.php` — distributor CRUD repository
    - `app/code/Giant/Checkout/etc/adminhtml/system.xml` — admin config (bicycle category IDs)
  - **Static deploy**: CSS/JS must be copied to `pub/static/frontend/Olegnax/athlete2/es_ES/Giant_Checkout/{css,js}/`
  - **Sample data**: 3 distributors pre-loaded (Bogotá, Medellín, Cali)
  - **Country/Currency**: CO / COP
- `Giant/MysqlSearch` - MySQL-based search adapter (replaces Elasticsearch requirement)
  - Enables category product listings without Elasticsearch
  - Queries catalog index tables directly via SQL
  - **Aggregation support**: Computes filter counts for layered navigation (category, price, and all filterable EAV attributes) from MySQL — replaces Elasticsearch aggregation buckets
- `addi/magento2-payment` - Payment module
- `mercadopago/adb-payment` - MercadoPago payment integration
- `Olegnax/*` - Theme/UI modules
- `Magefan/*` - Blog module
- `Nwdthemes/*` - Custom themes
- `IDeum/*` - Custom modules

## Product Migration
- **Source**: giant-bicycles.com.co (Magento 2 CSV export, 3,115 unique SKUs)
- **Method**: Direct SQL import via PHP script (`migration/direct_sql_import.php`) — bypasses Elasticsearch dependency
- **Results**: 4,194 total products (609 configurable, 3,561 simple, 24 virtual)
- **New products created**: 1,783 (SKU-based dedup, existing products updated)
- **Configurable links**: 3,850 parent-child relationships via `catalog_product_super_link`
- **Category mapping**: Source `Default Category/` and `Category Liv/` paths mapped to existing target categories
- **Attributes created**: `tama_o` (Tamaño, 80 options), `tama_o_llantas` (Tamaño Llantas, 12 options)
- **Attribute options added**: 3 new color values, all tallas/tama_o/tama_o_llantas options
- **Images migrated**: 5,558 gallery entries synced from source DB; 3,285 products have images; 7,991 image files copied via SSH from Cloudways server; 81% main image coverage (769 products reference images that don't exist on source server either)
- **Customers migrated**: 577 customers imported from source via REST API (with addresses)
- **Orders migrated**: 2,091 orders imported from source via REST API (with items, addresses, payments, grid)
- **URL rewrites**: 16,644 generated (3,848 product + 12,358 category-product)
- **Migration scripts**: All in `migration/` directory
  - `migrate_products.php` — CSV transform (category mapping, date fixing, URL key dedup)
  - `create_attribute_options.php` — creates missing attribute option values
  - `direct_sql_import.php` — main import script (batch SQL insert/update)
  - `link_configurables.php` — links configurable parents to simple children
  - `migrate_images.php` — image downloader (both direct URL and /media/ path)
  - `migrate_customers.php` — customer migration via REST API
  - `migrate_orders.php` — order migration via REST API (items, addresses, payments, grid)
  - `generate_url_rewrites.php` — creates URL rewrites for all products
  - `customers_export.json` — 579 customers from source (raw JSON backup)
  - `orders_export.json` — 2,092 orders from source (raw JSON backup)

## Admin Access
- Admin URL: `/admin_giant`
- Credentials managed via environment/database

## Deployment
- Type: VM (always-on, since MariaDB needs to run persistently)
- Run command: `bash /home/runner/workspace/start_app.sh`
