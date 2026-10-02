# Ardave Apparel — updated storefront

## What changed

- `index.php`: new editorial homepage hero using existing Ardave imagery, clearer category/featured sections, usable empty state, and a working fallback product photo.
- `products/products.php`: refreshed product grid, query-backed search, working category chips, safe sort options, result count and empty state. Add-to-cart now asks for a size and shows a confirmation message.
- `products/product-details.php`: layout refresh while keeping the existing product/cart form behavior.
- `assets/css/editorial.css`: new scoped styles for these three pages; other account/admin styles are untouched.
- `assets/js/products.js`: size-aware quick add and toast message.
- `admin/includes/header.php`: the Logout button now uses the POST request that `authentication/logout.php` expects.

## Folder findings

The live PHP system uses `admin/`, `authentication/`, `cart/`, `customer/`, `payment/`, `products/` and shared `includes/`, `assets/`, `database/`. The two React/Node folders in the uploaded ZIP (`ardave-apparel/` and `workspace/ardave-apparel/`) contain the exact same files and are not referenced by the PHP code. This updated copy keeps **one** under `prototypes/react-node/`. It does not move the live PHP pages, which would require changing many links and includes.

Keep your original `.env` and `uploads/payments/` safe. These private files are excluded from this shareable copy. See `.env.example` to configure a new local installation. `database/database.sql` contains sample product records; import only for a fresh database, never over your existing database without a backup.

## Install locally with XAMPP

1. Back up the existing `C:\xampp\htdocs\Ardave Apparel` folder and export its MySQL database.
2. For an existing installation, **copy only these six modified files** from this ZIP into matching paths in the backed-up project: `index.php`, `products/products.php`, `products/product-details.php`, `assets/css/editorial.css`, `assets/js/products.js`, and `admin/includes/header.php`. Keep your current `.env`, payment uploads, and database.
3. Open `http://localhost/Ardave%20Apparel/index.php`, then test the collection search, category chips, sorting, product detail, size choice, cart and checkout. If a product's image path is old or missing, the jersey photo is shown instead.

## Further work

The ZIP has existing admin, login, payment, and checkout flows. This update does not claim to have tested those flows with a running MySQL database. The admin schema has columns added at runtime; consolidate schema changes in migrations before reorganizing those files. Check existing product records if duplicate products still appear: the catalog displays what the database query returns.
