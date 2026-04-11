# 🌿 Green Nursery — Online Plant Shop

A full-featured e-commerce web application for buying and selling plants, built with PHP and MongoDB Atlas.

## Features

- **Plant catalog** with search, category filter, price range filter, and sort options
- **Plant detail pages** with star ratings and customer reviews
- **Shopping cart** with quantity controls and atomic checkout
- **Order history** with status tracking
- **User authentication** (register / login / logout) with bcrypt passwords
- **Admin dashboard** with stats, plant/category CRUD, image upload, and order management

## Tech Stack

- **Backend:** PHP 8+, `mongodb/mongodb` library
- **Database:** MongoDB Atlas (cloud) or any MongoDB instance
- **Frontend:** Vanilla JS, responsive CSS (mobile-friendly, down to 480 px)
- **Sessions:** PHP native sessions for auth

## Getting Started

### Requirements

- PHP 8+ with the `ext-mongodb` extension enabled
- [Composer](https://getcomposer.org/) (for the MongoDB PHP library)
- A MongoDB Atlas account (free tier works great) or a local MongoDB instance
- A web server (Apache, Nginx, or PHP built-in server)

### Setup

1. **Install PHP dependencies:**

   ```bash
   composer install
   ```

2. **Set your MongoDB connection string** as an environment variable:

   ```bash
   # Copy the example and fill in your Atlas URI
   cp .env.example .env
   # Then export it before starting your server, e.g.:
   export MONGODB_URI="mongodb+srv://user:pass@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority"
   ```

   You can get the connection string from **MongoDB Atlas → Connect → Drivers (PHP)**.

3. **Seed the database** with sample categories, plants, and an admin user:

   ```bash
   MONGODB_URI="<your-uri>" php mongo_seed.php
   ```

4. **Serve the project** from your web server's document root, or use the PHP built-in server:

   ```bash
   MONGODB_URI="<your-uri>" php -S localhost:8000
   ```

5. **Open** `http://localhost:8000` in your browser.

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
│   └── db.php          # MongoDB connection + helper functions
├── includes/           # Shared PHP helpers (header, footer, auth checks)
├── uploads/            # Plant images uploaded via the admin panel
├── vendor/             # Composer dependencies (mongodb/mongodb)
├── index.php           # Plant catalog page
├── plant.php           # Plant detail & reviews page
├── cart.php            # Shopping cart page
├── orders.php          # Order history page
├── login.php           # Login page
├── register.php        # Registration page
├── styles.css          # Global stylesheet
├── mongo_seed.php      # Database seed script (run once)
├── composer.json       # PHP dependencies
└── .env.example        # Environment variable template
```

## License

MIT
