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
├── backend/                 # Server-side logic and shared components
│   ├── admin/              # Admin dashboard pages and layouts
│   ├── config/             # Application and database configuration
│   ├── database/           # SQL schema and seed data files
│   ├── endpoints/          # AJAX endpoints for dynamic features
│   ├── includes/           # Shared helper functions and utilities
│   │   ├── auth.php        # Authentication functions
│   │   ├── cart.php        # Cart management functions
│   │   ├── csrf.php        # CSRF protection utilities
│   │   ├── flash.php       # Flash message handling
│   │   ├── functions.php   # Common utility functions
│   │   ├── init.php        # Application initialization
│   │   └── layout_*.php    # Layout templates
│   └── bootstrap.php       # Backend bootstrap file
├── frontend/               # Customer-facing UI components
│   ├── pages/              # Main customer pages
│   └── profile/            # Profile management pages
├── assets/                 # Static assets (CSS, JS, images)
├── uploads/                # User-uploaded content (food images)
├── admin/                  # Admin entry point
├── profile/                # Profile entry point
├── *.php                   # Root-level entry stubs for clean URLs
├── setup.php               # Database setup and initialization
└── seed_20_foods.php       # Script to populate sample food data
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

