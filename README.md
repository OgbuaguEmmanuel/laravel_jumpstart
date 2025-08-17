<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
  <a href="#"><img src="https://github.com/OgbuaguEmmanuel/laravel_jumpstart/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
  [![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
</p>

---

# 🚀 Laravel Jumpstart

A Laravel starter kit pre-configured with authentication, roles & permissions, user imports/exports, support tickets, payments, notifications, and more.  
It’s built on top of **Laravel 11**, optimized for API-driven apps.

---

## ✨ Features

- 🔐 Authentication (Email/Password, Socialite, 2FA)
- 👥 Role & permission management
- 📨 Notifications (database + mail)
- 🎫 Support tickets system
- 📊 Bulk user import/export with Excel/CSV
- ⚙️ Settings management (single & bulk upsert)
- 💳 Paystack payment integration
- 🛠️ Optimized for API usage
- Exception handling

---

## ⚡ API Overview

### Settings
- `GET /api/V1/admin/settings` – List all settings  
- `GET /api/V1/admin/settings/{key}` – Fetch setting by key  
- `POST /api/V1/admin/settings/set` – Create or update a setting  
- `POST /api/V1/admin/settings/bulk` – Bulk create/update multiple settings  

### Support Tickets
- `GET /api/V1/admin/tickets` – List tickets with sorting & filters  
- `POST /api/V1/admin/tickets` – Create a new ticket  
- `PATCH /api/V1/admin/tickets/{id}` – Update a ticket  

*(extend with other endpoints like users, payments, etc.)*

---

## ⚙️ Installation

```bash
git clone https://github.com/OgbuaguEmmanuel/laravel_jumpstart.git
cd laravel_jumpstart

composer install
cp .env.example .env
php artisan key:generate

php artisan migrate --seed
npm install && npm run dev
