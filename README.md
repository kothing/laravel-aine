# Laravel-Aine
Aine is a self-hosted headless `Content Management Framework` built with Laravel and VueJs. It provides a clean and easy to use interface and a powerful Content API.

# What is a Headless CMF?
A headless CMF is a backend-only `Content Management Framework` that makes content accessible via an API. A traditional CMS typically combines the content and frontend of a website. Headless CMS is a decoupled system that comprises just the content component and focuses entirely on the administrative interface. One advantage of this decoupled approach is that content can be sent via APIs to multiple display types, like mobile and Internet of Things (IoT) devices, alongside a website.

# How to start

[Aine document](https://github.com/kothing/laravel-aine-document/ "Aine document")

## Server Requirements
- PHP >= 8.1
- Mysql >= 5.7.8
- BCMath PHP Extension
- Ctype PHP Extension
- DOM PHP Extension
- Fileinfo PHP Extension
- JSON PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- PCRE PHP Extension
- PDO PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension

## Installation

**1. Clone From Github**
```bash
git clone https://github.com/kothing/laravel-aine.git
```

**2. Go to that folder**
```bash
cd your-project-folder
```

**3. Set folder permissions**
- storage 777
- bootstrap/cache 777
- public 777

**4. Create a Database named**

Create your database

**5. Create and config env file**

Create a .env file by cloning .env.example file, configure the following information 
```
APP_NAME='Your app name'
APP_ENV=production
APP_URL=https://your-site-domain.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user_name
DB_PASSWORD=your_database_user_password
```

**6. Install Composer**
```php
composer install
```

**7. Generate app APP_KEY**
```
php artisan key:generate
```

**8. Run Migration & Seed**
```php
php artisan migrate:fresh --seed
```

**9. Run On Local Machine**  
start Laravel's local development server
```bash
php artisan serve
```
and open browser at `http://localhost:8000`


**10. Go to CMS admin dashborad**

Login Now by giving this data `http://your-site-domain.com`
```php
Username: admin@admin.com
Password: admin
```
You can find it in `database/seeders/DatabaseSeeder.php`


## Clear cache
```
php artisan cache:clear
php artisan route:cache
php artisan config:cache
php artisan view:clear
```
