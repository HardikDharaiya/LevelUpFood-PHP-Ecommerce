# LevelUpFood - PHP E-Commerce Website

**Technologies:** PHP (Procedural), MySQL, HTML5, CSS3, Bootstrap 5, JavaScript, jQuery  
**Environment:** XAMPP (Apache, MariaDB, PHP 8.x)  
**GitHub Repo:** [Click](https://github.com/HardikDharaiya/LevelUpFood-PHP-Ecommerce)

## 🔥 Project Description

**LevelUpFood** is a full-stack e-commerce web application for online food ordering. It supports secure user registration, session-based cart, order tracking, and a powerful admin panel for content and order management.

---

## 📌 Features

### 👤 User Panel:
- Register/Login with password hashing (`password_hash`)
- View and update profile (name, email, password)
- Add/remove/update food items in cart
- Place orders with address details
- View past order history
- Client-side validation using jQuery Validate

### 🛠️ Admin Panel:
- Login-based admin dashboard
- Manage Categories (CRUD)
- Manage Menu Items with image upload & delete
- Manage Users (view, change role, delete)
- View, update & filter Orders
- View/Delete Contact Messages

---

## 💾 Database

Import the provided SQL file `levelupfood_database_from_export.sql` into your phpMyAdmin or MySQL CLI to set up the database.  
Includes:
- `users`, `categories`, `menu_items`, `orders`, `order_items`, `contact_messages`

Relational schema includes foreign keys, cascading rules, and indexes.

---

## 🔐 Security
- Uses prepared statements (MySQLi)
- Passwords are securely hashed
- Form validation and input sanitation on server and client side

---

## 📷 Screenshots
You can check my LinkedIn post for UI/UX & admin preview images:  
🔗 [LinkedIn Post](https://www.linkedin.com/posts/hardik-dharaiya-150103257_php-mysql-bootstrap-activity-7349327684713246720-jOb4?utm_source=social_share_send&utm_medium=member_desktop_web&rcm=ACoAAD8ykQwB5cs7ZV2D6evydy5I7-hmSfCCyAw) *(Insert once posted)*

---

## 📦 Setup Instructions
1. Clone this repo or download ZIP
2. Copy to `htdocs` (for XAMPP)
3. Import the SQL file
4. Configure `/config/db_connect.php` with your credentials
5. Run on `http://localhost/your-folder`

---

## 🧠 Credits
Built as part of **Major Project - Semester 6 BCA**  
Guided by faculty at **RK University**  
All code authored by **Hardik Dharaiya**

---

## 📬 Contact
**Email**: hdharaiya145@rku.ac.in  
**LinkedIn**: [linkedin.com/in/hardikdharaiya](www.linkedin.com/in/hardik-dharaiya-150103257)

---
