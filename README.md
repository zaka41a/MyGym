# 🏋️ MyGym – Gym Management System

## 📌 Project Overview
MyGym is a **web-based gym management system** built with **PHP, MySQL, HTML, CSS, and JavaScript**.  
It allows members to **register, log in, subscribe to plans, and reserve classes**, while administrators can **manage users, subscriptions, and gym sessions**.

<!-- 🇫🇷 Ici on explique : cette section sert d'introduction claire pour les recruteurs et collaborateurs. -->

---

## 🚀 Features
- 🔑 **Authentication & Roles**
  - Member / Admin roles with secure login
  - CSRF protection & password hashing
- 🧑‍🤝‍🧑 **User Management**
  - Registration with email verification
  - Profile with avatar upload
- 💳 **Subscription System**
  - Multiple plans (BASIC, PLUS, PRO)
  - Active & pending subscriptions with automatic expiration
- 📅 **Class Reservations**
  - Members can book & cancel sessions
  - Coaches assigned to sessions
  - Remaining capacity automatically updated
- 📊 **Dashboard**
  - Active subscription status
  - Upcoming reservations
  - KPIs (days left, progress bar)

<!-- 🇫🇷 Ici on liste toutes les fonctionnalités principales, pour montrer la richesse du projet. -->

---

## 🛠️ Tech Stack
- **Backend:** PHP 8+, PDO, Sessions
- **Frontend:** HTML5, CSS3, JavaScript
- **Database:** MySQL (phpMyAdmin for management)
- **Environment:** XAMPP / Apache

<!-- 🇫🇷 Cette partie présente les technologies utilisées (important pour GitHub et les recruteurs). -->

---

## ⚙️ Installation & Setup

### 1️⃣ Clone the repository
```bash
git clone https://github.com/your-username/MyGym.git
cd MyGym


### 2️⃣ Configure Database
- Import `database/myschema.sql` into **phpMyAdmin**
- Update credentials in `backend/db.php`:
```php
$pdo = new PDO("mysql:host=localhost;dbname=mygym;charset=utf8", "root", "");


http://localhost/MyGym/frontend/login/login.html
