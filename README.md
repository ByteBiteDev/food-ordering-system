# Food Ordering System

A complete web-based food ordering application built with **PHP** and **MySQL**. This system provides a customer-facing storefront for browsing menu items, managing carts, and placing orders, along with an administrative dashboard for managing the entire food ordering operation.

## Features

### Customer Features
- **Menu Browsing**: Browse foods by category with search functionality
- **Food Details**: View detailed information including descriptions, prices, and images
- **Shopping Cart**: Add items to cart, update quantities, and manage selections
- **Order Placement**: Complete checkout process with address and payment method selection
- **Order History**: View past orders and track order status
- **Profile Management**: Update personal information and contact details
- **Reviews & Ratings**: Leave feedback and rate food items after ordering
- **Favorites**: Save favorite food items for quick access

### Admin Features
- **Dashboard**: Overview of key metrics including total orders, revenue, and customer count
- **Category Management**: Create, edit, and deactivate food categories
- **Food Management**: Add, update, and remove menu items with image uploads
- **Order Management**: View all orders, update order status, and manage fulfillment
- **Customer Management**: View registered customers and their order history
- **Reports**: Access sales reports and analytics

### Technical Features
- **Security**: CSRF protection for all form submissions
- **Database Security**: PDO with prepared statements to prevent SQL injection
- **Session Management**: Secure session-based authentication and cart handling
- **Input Validation**: Server-side validation for all user inputs
- **Error Handling**: Comprehensive error handling and user-friendly error messages
- **Responsive Design**: Mobile-friendly interface for both customer and admin sections

## Database Schema

The application uses a normalized database schema with the following tables:
- **users**: Stores customer and admin account information
- **categories**: Food categories for organizing menu items
- **foods**: Menu items with pricing, descriptions, and images
- **orders**: Customer orders with status and payment information
- **order_items**: Individual items within each order
- **food_reviews**: Customer ratings and reviews for food items
- **cart**: Optional persisted cart (session cart used by default)

## Project Structure

```
food-ordering-system/
├── Root Entry Points        # Public-facing PHP files (clean URLs)
│   ├── index.php           # → frontend/pages/index.php
│   ├── login.php           # → frontend/pages/login.php
│   ├── register.php        # → frontend/pages/register.php
│   ├── food.php            # → frontend/pages/food.php
│   ├── food_details.php    # → frontend/pages/food_details.php
│   ├── cart.php            # → frontend/pages/cart.php
│   ├── checkout.php        # → frontend/pages/checkout.php
│   ├── order.php           # → frontend/pages/order.php
│   ├── orders.php          # → frontend/pages/orders.php
│   ├── payment.php         # → frontend/pages/payment.php
│   ├── payment_success.php # → frontend/pages/payment_success.php
│   ├── profile.php         # → frontend/pages/profile.php
│   ├── about.php           # → frontend/pages/about.php
│   ├── contact.php         # → frontend/pages/contact.php
│   ├── logout.php          # → frontend/pages/logout.php
│   ├── ajax_favorite.php   # → backend/endpoints/ajax_favorite.php
│   ├── ajax_payment.php    # → backend/endpoints/ajax_payment.php
│   ├── setup.php           # Database setup script
│   └── seed_20_foods.php   # Seed data script
│
├── backend/                # Server-side logic and utilities
│   ├── admin/              # Admin dashboard implementations
│   │   ├── categories.php
│   │   ├── customers.php
│   │   ├── foods.php
│   │   ├── food_edit.php
│   │   ├── orders.php
│   │   ├── order_edit.php
│   │   ├── reports.php
│   │   ├── settings.php
│   │   └── includes/       # Admin-specific includes
│   ├── config/             # Configuration files
│   │   ├── config.php      # Database and app config
│   │   └── db.php          # Database connection
│   ├── database/           # SQL files
│   │   ├── schema.sql      # Database schema
│   │   ├── seed.sql        # Seed data
│   │   ├── admin_schema.sql
│   │   └── user_schema.sql
│   ├── endpoints/          # AJAX endpoints
│   │   ├── ajax_favorite.php
│   │   └── ajax_payment.php
│   ├── includes/           # Shared utilities
│   │   ├── auth.php        # Authentication functions
│   │   ├── cart.php        # Cart management
│   │   ├── csrf.php        # CSRF protection
│   │   ├── flash.php       # Flash messages
│   │   ├── functions.php   # Helper functions
│   │   ├── init.php        # Application init
│   │   ├── layout_top.php  # Layout header
│   │   └── layout_bottom.php # Layout footer
│   ├── scratch/            # Development utilities
│   └── bootstrap.php       # Backend bootstrap
│
├── frontend/               # Customer-facing UI
│   ├── pages/              # Customer page implementations
│   │   ├── index.php
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── food.php
│   │   ├── food_details.php
│   │   ├── cart.php
│   │   ├── checkout.php
│   │   ├── order.php
│   │   ├── orders.php
│   │   ├── payment.php
│   │   ├── payment_success.php
│   │   ├── profile.php
│   │   ├── about.php
│   │   ├── contact.php
│   │   └── logout.php
│   ├── profile/            # Profile management
│   │   ├── index.php
│   │   ├── edit.php
│   │   ├── addresses.php
│   │   ├── favorites.php
│   │   ├── orders.php
│   │   ├── order_view.php
│   │   ├── notifications.php
│   │   ├── security.php
│   │   └── includes/       # Profile-specific includes
│   └── bootstrap.php       # Frontend bootstrap
│
├── assets/                 # Static assets
│   ├── css/                # Stylesheets
│   │   └── style.css
│   ├── js/                 # JavaScript files
│   │   ├── app.js
│   │   └── main.js
│   ├── img/                # Images
│   │   ├── placeholder.svg
│   │   ├── food-placeholder.svg
│   │   ├── hero-pattern.svg
│   │   ├── chapa-logo.svg
│   │   ├── telebirr-logo.png
│   │   └── telebirr-logo.svg
│   ├── admin/              # Admin assets
│   │   ├── css/
│   │   │   └── admin.css
│   │   └── js/
│   │       └── admin.js
│   └── profile/            # Profile assets
│       ├── css/
│       │   └── profile.css
│       └── js/
│           └── profile.js
│
├── uploads/                # User-uploaded files
│   └── foods/              # Food images
│       ├── .gitkeep
│       ├── .htaccess
│       └── [uploaded images]
│
├── docs/                   # Documentation
├── .gitignore              # Git ignore rules
└── README.md               # This file
```

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Web server (Apache, Nginx, or PHP built-in server)

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/ByteBiteDev/food-ordering-system.git
   cd food-ordering-system
   ```

2. **Configure Database**
   - Create a MySQL database (default name: `food_ordering`)
   - Update database credentials in `backend/config/config.php`:
     ```php
     define('DB_HOST', '127.0.0.1');
     define('DB_PORT', '3306');
     define('DB_NAME', 'food_ordering');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

3. **Run Setup**
   - Open `setup.php` in your browser
   - Fill in the admin account details
   - Click "Run Setup" to create tables and seed sample data
   - After successful setup, delete `setup.lock` if you need to re-run setup

4. **Access the Application**
   - Customer interface: `http://localhost/food-ordering-system/`
   - Admin dashboard: `http://localhost/food-ordering-system/admin/`
   - Login with the admin credentials created during setup

### Running with PHP Built-in Server
```bash
php -S localhost:8000
```
Then access at `http://localhost:8000`

## Usage

### For Customers
1. Register a new account or login as a guest
2. Browse the menu and add items to cart
3. Proceed to checkout and enter delivery details
4. Select payment method and place order
5. Track order status in the orders section
6. Leave reviews for ordered items

### For Administrators
1. Login with admin credentials
2. Manage categories and food items
3. Monitor and update order statuses
4. View customer information and order history
5. Generate reports to analyze sales data

## Security Considerations

- All database queries use PDO prepared statements
- CSRF tokens are validated on all form submissions
- Passwords are hashed using PHP's `password_hash()` function
- Session management includes secure cookie parameters
- Input validation is performed on both client and server side
- File uploads are validated for type and size

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Architecture**: MVC-inspired modular structure

## License

This project is developed for educational purposes.

## Author

Developed as a course project for demonstrating full-stack web development skills using PHP and MySQL.

