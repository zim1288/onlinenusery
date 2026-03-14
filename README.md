# 🌿 Green Nursery — Online Plant Shop

A full-featured e-commerce web application for buying and selling plants, built with PHP and MySQL.

## Features

- **Plant catalog** with search, category filter, price range filter, and sort options
- **Plant detail pages** with star ratings and customer reviews
- **Shopping cart** with quantity controls and atomic checkout
- **Order history** with status tracking
- **User authentication** (register / login / logout) with bcrypt passwords
- **Admin dashboard** with stats, plant/category CRUD, image upload, and order management

## Tech Stack

- **Backend:** PHP 8+, MySQLi with prepared statements
- **Database:** MySQL / MariaDB
- **Frontend:** Vanilla JS, responsive CSS (mobile-friendly, down to 480 px)
- **Sessions:** PHP native sessions for auth

## Getting Started

### Requirements

- PHP 8+
- MySQL / MariaDB
- A web server (Apache, Nginx, or PHP built-in server)

### Setup

1. **Create the database:**

   ```bash
   mysql -u root -p < database.sql
   ```

2. **Configure the database connection** in `api/db.php` (host, user, password, database).

3. **Serve the project** from your web server's document root, or use the PHP built-in server:

   ```bash
   php -S localhost:8000
   ```

4. **Open** `http://localhost:8000` in your browser.

### Default Admin Credentials

| Username | Password |
|----------|----------|
| `admin`  | `password` |

> ⚠️ Change the admin password immediately after setup.

## Project Structure

```
onlinenursery/
├── admin/              # Admin panel (dashboard, plants, orders, categories)
├── api/                # JSON API endpoints (auth, cart, orders, reviews, plants)
├── includes/           # Shared PHP helpers (header, footer, auth checks)
├── uploads/            # Plant images uploaded via the admin panel
├── index.php           # Plant catalog page
├── plant.php           # Plant detail & reviews page
├── cart.php            # Shopping cart page
├── orders.php          # Order history page
├── login.php           # Login page
├── register.php        # Registration page
├── styles.css          # Global stylesheet
└── database.sql        # Database schema + seed data
```

## License

MIT
