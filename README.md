

```markdown
# 🛒 DigitalPasal — Full-Stack E-Commerce Platform

[![Project Status: Active](https://img.shields.io/badge/Project%20Status-Active-brightgreen.svg)](#)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](#)
[![Node.js Version](https://img.shields.io/badge/Node.js-v18%2B-green.svg)](#)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](#)

**DigitalPasal** (Nepalese for *"Digital Shop"*) is an enterprise-ready, full-stack e-commerce solution engineered to empower local vendors and consumers with a seamless online shopping experience. Built with scalability, security, and high performance in mind, DigitalPasal offers an end-to-end shopping workflow—from interactive product browsing and dynamic shopping carts to secure checkout, online payment integrations, order management, and multi-role administrative dashboards.

---

## 📑 Table of Contents

1. [Executive Summary](#-executive-summary)
2. [Key Features](#-key-features)
   - [Customer Experience](#1-customer-experience)
   - [Vendor / Merchant Portal](#2-vendor--merchant-portal)
   - [Admin Control Center](#3-admin-control-center)
3. [System Architecture](#-system-architecture)
4. [Tech Stack & Dependencies](#-tech-stack--dependencies)
5. [Directory Structure](#-directory-structure)
6. [Getting Started & Local Installation](#-getting-started--local-installation)
   - [Prerequisites](#prerequisites)
   - [Environment Configuration](#environment-configuration)
   - [Database Setup & Seeding](#database-setup--seeding)
   - [Running the Application](#running-the-application)
7. [API Documentation Overview](#-api-documentation-overview)
8. [Database Schema Design](#-database-schema-design)
9. [Security Implementations](#-security-implementations)
10. [Roadmap & Future Enhancements](#-roadmap--future-enhancements)
11. [Contributing](#-contributing)
12. [License & Contact](#-license--contact)

---

## 🚀 Executive Summary

DigitalPasal bridge the gap between traditional brick-and-mortar stores and modern online marketplaces. Tailored for retail scalability, the platform provides real-time stock tracking, automated order processing, multi-channel payment gateway support (eSewa, Khalti, Mobile Banking, Cash on Delivery), and granular data analytics.

The application adheres to clean architecture principles, emphasizing separation of concerns, modular controller design, secure RESTful APIs, and responsive front-end user interface design.

---

## ✨ Key Features

### 1. Customer Experience
* **Dynamic Product Catalog**: Search, filter, and sort products by category, price range, brand, rating, and availability.
* **Smart Search & Autosuggestion**: Debounced client-side and server-side text querying for rapid product discovery.
* **Persistent Shopping Cart & Wishlist**: Real-time state management ensuring shopping sessions persist across page reloads.
* **Secure Checkout Flow**: Multi-step checkout process featuring saved shipping addresses, order summary validation, and dynamic tax/shipping cost calculation.
* **Payment Integration**: Seamless integration with popular regional payment gateways (eSewa, Khalti) alongside Cash on Delivery (COD) options.
* **Order History & Real-Time Tracking**: Interactive user profile dashboard showing order lifecycle status (`Pending`, `Processing`, `Shipped`, `Delivered`, `Cancelled`).
* **Ratings & Reviews**: Verified buyer review system allowing customer feedback, star ratings, and photo uploads.

### 2. Vendor / Merchant Portal
* **Inventory Management**: Add, update, archive, or delete products with multi-image support, stock thresholds, and SKU generation.
* **Order Fulfillment Center**: Receive real-time notifications for incoming orders and update tracking statuses.
* **Sales Analytics**: Visual breakdown of revenue trends, top-performing items, and order volume charts.

### 3. Admin Control Center
* **Role-Based Access Control (RBAC)**: Distinct permissions for `Super Admin`, `Store Manager`, `Support Staff`, and `Customer`.
* **User & Merchant Management**: Manage registered user accounts, ban/unban profiles, and verify store registrations.
* **Category & Banner Management**: Dynamic homepage slider, promo banner updates, and multi-level nested category trees.
* **System Logs & Financial Reports**: Audit trails for transactions, refund management, and platform commission tracking.

---

## 🏗️ System Architecture

DigitalPasal uses a decoupled, modular architecture designed for high scalability and maintainability:

```text
               +----------------------------------+
               |      Client Browser / Mobile     |
               +----------------------------------+
                                |
                                v
               +----------------------------------+
               |   Nginx / reverse proxy / CORS   |
               +----------------------------------+
                                |
                                v
               +----------------------------------+
               |    Express REST API Backend      |
               +----------------------------------+
                 /              |               \
                /               |                \
               v                v                 v
      +-----------------+ +------------+ +------------------+
      |  Database Layer | | Auth / JWT | | External Services|
      | MongoDB/MySQL   | | Middleware | | (eSewa, Cloudinary)|
      +-----------------+ +------------+ +------------------+

```

---

## 🛠️ Tech Stack & Dependencies

### **Front-End Layer**

* **HTML5 / CSS3 / JavaScript (ES6+)**: Semantic markup, CSS Custom Properties, and modern JavaScript modules.
* **Styling Frameworks**: Responsive grid layouts built with Tailwind CSS / Bootstrap / Custom Flexbox modules.
* **Icons & Assets**: FontAwesome, Lucide Icons, and optimized Cloudinary image storage.

### **Back-End Layer**

* **Runtime**: Node.js
* **Framework**: Express.js
* **Authentication**: JSON Web Tokens (JWT) & HTTP-Only secure cookies with bcrypt password hashing.
* **File Uploads**: Multer middleware coupled with Cloudinary / local storage solutions.

### **Database Layer**

* **Database Engine**: MongoDB (via Mongoose ODM) / MySQL (via Sequelize ORM).
* **Caching**: Redis (Optional / for session and query caching).

---

## 📂 Directory Structure

```text
DigitalPasal/
├── config/                 # Database connection and environment specs
│   ├── db.js
│   └── cloudinary.js
├── controllers/            # Route business logic handlers
│   ├── authController.js
│   ├── productController.js
│   ├── orderController.js
│   ├── paymentController.js
│   └── userController.js
├── middleware/             # Custom Express middlewares
│   ├── authMiddleware.js
│   ├── errorMiddleware.js
│   └── uploadMiddleware.js
├── models/                 # ORM/ODM Database Schemas
│   ├── User.js
│   ├── Product.js
│   ├── Order.js
│   └── Category.js
├── routes/                 # Express API Endpoints
│   ├── authRoutes.js
│   ├── productRoutes.js
│   ├── orderRoutes.js
│   └── paymentRoutes.js
├── public/                 # Static assets (images, CSS, frontend JS)
│   ├── css/
│   ├── js/
│   └── uploads/
├── views/                  # UI Templates (EJS/Handlebars/HTML)
│   ├── admin/
│   ├── shop/
│   └── partials/
├── .env.example            # Sample environment variables
├── .gitignore
├── package.json            # Node.js dependencies and scripts
├── server.js               # Application entry point
└── README.md               # Project documentation

```

---

## ⚙️ Getting Started & Local Installation

Follow these instructions to configure and launch DigitalPasal on your local development machine.

### Prerequisites

* **Node.js**: `v18.0.0` or higher installed.
* **Git**: Installed on your system.
* **Database**: MongoDB service running locally or a MongoDB Atlas URI string.

### Environment Configuration

1. Clone the repository:
```bash
git clone [https://github.com/RonashDahal/DigitalPasal.git](https://github.com/RonashDahal/DigitalPasal.git)
cd DigitalPasal

```


2. Install backend and frontend dependencies:
```bash
npm install

```


3. Create a `.env` file in the root directory:
```bash
cp .env.example .env

```


4. Populate `.env` with your environment configuration values:
```env
# Server Configuration
PORT=5000
NODE_ENV=development

# Database Settings
MONGO_URI=mongodb://localhost:27017/digitalpasal_db

# Authentication Secrets
JWT_SECRET=your_super_secret_jwt_key_here
JWT_EXPIRE=30d

# Cloudinary (Media Storage)
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret

# Payment Integrations (Testing Keys)
ESEWA_MERCHANT_CODE=EPAYTEST
KHALTI_SECRET_KEY=your_khalti_test_secret_key

```



### Database Setup & Seeding

To quickly test the platform with sample products, categories, and an admin user:

```bash
# Seed default admin and sample product data
npm run seed

```

### Running the Application

* **Development Mode** (with Nodemon hot reloading):
```bash
npm run dev

```


* **Production Mode**:
```bash
npm start

```



Once running, navigate to `http://localhost:5000` in your browser.

---

## 📡 API Documentation Overview

The backend provides a RESTful API returning standard JSON payloads.

| Method | Endpoint | Description | Access |
| --- | --- | --- | --- |
| **POST** | `/api/v1/auth/register` | Register a new user account | Public |
| **POST** | `/api/v1/auth/login` | Authenticate user & get JWT | Public |
| **GET** | `/api/v1/products` | Fetch all products (supports query params) | Public |
| **GET** | `/api/v1/products/:id` | Get detailed information for single product | Public |
| **POST** | `/api/v1/products` | Create a new product listing | Admin / Merchant |
| **PUT** | `/api/v1/products/:id` | Update an existing product | Admin / Merchant |
| **DELETE** | `/api/v1/products/:id` | Delete a product listing | Admin |
| **POST** | `/api/v1/orders` | Create a new purchase order | Authenticated |
| **GET** | `/api/v1/orders/my-orders` | Fetch orders placed by logged-in user | Authenticated |
| **POST** | `/api/v1/payment/verify-esewa` | Verify eSewa transaction checksum | Authenticated |

---

## 🗄️ Database Schema Design

```text
   +-------------------+          +-------------------+
   |       USER        |          |      PRODUCT      |
   +-------------------+          +-------------------+
   | _id (PK)          |          | _id (PK)          |
   | name              |          | title             |
   | email (Unique)    |<----+    | description       |
   | password (Hashed) |     |    | price             |
   | role              |     |    | stockQuantity     |
   | address           |     |    | category_id (FK)  |-----+
   +-------------------+     |    | images []         |     |
             |               |    +-------------------+     |
             | 1             |              | 1             |
             |               |              |               |
             | N             |              | N             |
   +-------------------+     |    +-------------------+     |
   |       ORDER       |     |    |     CATEGORY      |     |
   +-------------------+     |    +-------------------+     |
   | _id (PK)          |     |    | _id (PK)          |<----+
   | user_id (FK) -----+-----+    | name              |
   | orderItems []     |          | slug              |
   | totalAmount       |          +-------------------+
   | paymentStatus     |
   | shippingAddress   |
   | createdAt         |
   +-------------------+

```

---

## 🔒 Security Implementations

* **Password Security**: Passwords are standardly hashed using `bcryptjs` with a salt factor of 10 prior to database persistence.
* **JWT Authentication**: Secure API endpoints are protected via Bearer Token authentication.
* **Input Sanitization**: Requests pass through data validation and sanitization layers to prevent NoSQL Injection / SQL Injection and Cross-Site Scripting (XSS) attacks.
* **CORS & Rate Limiting**: Cross-Origin Resource Sharing is locked down to designated frontend origins. Express Rate Limit middleware protects endpoints from brute-force login attacks.

---

## 📈 Roadmap & Future Enhancements

* [ ] **Multi-Vendor Marketplace Expansion**: Allow merchant store registration with independent vendor dashboards.
* [ ] **PWA Support**: Transform the frontend into a Progressive Web App (PWA) with offline caching and push notifications.
* [ ] **AI-Powered Recommendation Engine**: Integrate collaborative filtering to recommend personalized products based on user browsing habits.
* [ ] **In-App Live Chat**: Real-time customer support chat between store owners and buyers using `Socket.io`.

---

## 🤝 Contributing

Contributions make the open-source community an incredible place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. **Fork the Project**
2. **Create your Feature Branch** (`git checkout -b feature/AmazingFeature`)
3. **Commit your Changes** (`git commit -m 'Add some AmazingFeature'`)
4. **Push to the Branch** (`git push origin feature/AmazingFeature`)
5. **Open a Pull Request**

---

## 📄 License & Contact

Distributed under the **MIT License**. See `LICENSE` for more information.
* **Website Available:**:(https://www.digitalpasal.gt.tc).
* **Developer**: **Ronash Dahal**
* **GitHub**: [@RonashDahal](https://www.google.com/search?q=https://github.com/RonashDahal)
* **Project Repository**: [DigitalPasal on GitHub](https://github.com/RonashDahal/DigitalPasal)

```

---


```
