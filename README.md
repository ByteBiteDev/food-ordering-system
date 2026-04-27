# Food Ordering System (PHP + MySQL)

A simple, modular food ordering web application built with vanilla **PHP** and **MySQL**. It provides a customer-facing storefront (browse menu, cart, checkout) and an admin dashboard (manage foods, categories, orders, customers).

## Core Features

**Customer**
- Browse foods by category and search
- View food details
- Add to cart and checkout
- Place orders and view order history
- Manage profile and favorites
- Leave ratings/reviews after ordering

**Admin**
- Dashboard overview
- Manage categories and menu items
- Manage customer orders and order status
- View customers and reports

**Security & Quality**
- CSRF protection for forms
- PDO database access with prepared statements
- Session-based cart (`$_SESSION['cart']`)

## Project Structure

- `frontend/` — customer UI pages and profile module
  - `frontend/pages/` — main customer pages (home, menu, cart, checkout, etc.)
  - `frontend/profile/` — profile pages and profile layout
- `backend/` — server-side logic and shared code
  - `backend/includes/` — shared helpers (auth, CSRF, cart, flash, layout)
  - `backend/config/` — app + database configuration
  - `backend/endpoints/` — AJAX endpoints (favorites, payments)
  - `backend/admin/` — admin pages and admin layout
  - `backend/database/` — SQL schema + seed files

Public static folders:
- `assets/` — CSS/JS/images (customer + admin)
- `uploads/` — uploaded images (e.g. `uploads/foods/`)

Root `*.php` files are lightweight entry stubs to keep classic URLs working (e.g. `/food.php`, `/cart.php`).

## Setup (Local)

1. Create a MySQL database (default name: `food_ordering`).
2. Update DB credentials in `backend/config/config.php`.
3. Run `setup.php` in your browser to create tables and seed sample data.

## Screenshots

Add/update screenshots here:
- `docs/screenshots/home.png`
- `docs/screenshots/admin.png`
- `docs/screenshots/profile.png`
- `docs/screenshots/cart.png`
- `docs/screenshots/product.png`

Once you add them, you can embed them in this README using standard Markdown image syntax.
