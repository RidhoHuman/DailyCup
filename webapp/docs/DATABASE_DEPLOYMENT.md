# 🗄️ Database Deployment & Migration Guide

Complete guide untuk database setup, migration, dan backup strategy.

---

## 📋 Table of Contents

1. [Database Schema Export](#database-schema-export)
2. [Production Database Setup](#production-database-setup)
3. [Migration Strategy](#migration-strategy)
4. [Backup & Recovery](#backup--recovery)
5. [Database Optimization](#database-optimization)

---

## 📦 Database Schema Export

### Step 1: Export from Local Development

```bash
# Navigate to Laragon MySQL bin
cd C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin

# Export database structure only
.\mysqldump.exe -u root --no-data dailycup_db > dailycup_schema.sql

# Export database with data
.\mysqldump.exe -u root dailycup_db > dailycup_full.sql

# Export specific tables
.\mysqldump.exe -u root dailycup_db products categories users > dailycup_partial.sql

# Compress for transfer
tar -czf dailycup_backup.tar.gz dailycup_full.sql
```

### Step 2: Clean SQL File

**Remove problematic statements:**
```sql
-- Remove these lines from exported SQL:
-- /*!40000 ALTER TABLE ... DISABLE KEYS */;
-- /*!40000 ALTER TABLE ... ENABLE KEYS */;

-- Keep only essential:
CREATE DATABASE IF NOT EXISTS dailycup_db;
USE dailycup_db;

-- Table creation statements
CREATE TABLE users (...);
CREATE TABLE products (...);
-- etc.
```

---

## 🖥️ Production Database Setup

### Option 1: Shared Hosting (cPanel)

#### Step 1: Create Database via cPanel

1. Login to **cPanel**
2. Go to **MySQL Databases**
3. **Create New Database:**
   - Database Name: `dailycup_db`
   - Click **Create Database**
4. **Create Database User:**
   - Username: `dailycup_user`
   - Password: (generate strong password)
   - Click **Create User**
5. **Add User to Database:**
   - User: `dailycup_user`
   - Database: `dailycup_db`
   - Privileges: **ALL PRIVILEGES**
   - Click **Make Changes**

#### Step 2: Import via phpMyAdmin

1. cPanel → **phpMyAdmin**
2. Select database: `dailycup_db`
3. Click **Import** tab
4. **Choose File:** Select `dailycup_schema.sql`
5. Format: **SQL**
6. Click **Go**
7. Wait for import to complete

#### Step 3: Verify Tables

```sql
-- Run in phpMyAdmin SQL tab
SHOW TABLES;

-- Check table structure
DESCRIBE users;
DESCRIBE products;
DESCRIBE orders;
```

---

### Option 2: VPS (Ubuntu + MySQL)

#### Step 1: Install MySQL

```bash
# Connect to VPS
ssh root@your_server_ip

# Update system
apt update && apt upgrade -y

# Install MySQL
apt install mysql-server -y

# Secure MySQL
mysql_secure_installation
# Answer prompts:
# - Set root password: YES (use strong password)
# - Remove anonymous users: YES
# - Disallow root login remotely: YES
# - Remove test database: YES
# - Reload privilege tables: YES
```

#### Step 2: Create Database & User

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE dailycup_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user
CREATE USER 'dailycup_user'@'localhost' IDENTIFIED BY 'your_strong_password_here';

# Grant privileges
GRANT ALL PRIVILEGES ON dailycup_db.* TO 'dailycup_user'@'localhost';
FLUSH PRIVILEGES;

# Verify
SHOW DATABASES;
SELECT User, Host FROM mysql.user WHERE User = 'dailycup_user';

EXIT;
```

#### Step 3: Import SQL File

```bash
# Upload SQL file to server
scp dailycup_full.sql root@your_server_ip:/tmp/

# Import database
mysql -u dailycup_user -p dailycup_db < /tmp/dailycup_full.sql

# Verify import
mysql -u dailycup_user -p

USE dailycup_db;
SHOW TABLES;
SELECT COUNT(*) FROM products;
EXIT;
```

---

## 🔄 Migration Strategy

### Version Control for Database

**Create migration files:**

```sql
-- migrations/001_create_users_table.sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- migrations/002_create_products_table.sql
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- migrations/003_add_featured_to_products.sql
ALTER TABLE products
ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER stock;

-- migrations/004_create_indexes.sql
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_featured ON products(is_featured);
```

### Migration Script (PHP)

```php
<?php
// migrate.php

require_once 'config/database.php';

$migrations_dir = __DIR__ . '/migrations';
$migration_files = glob($migrations_dir . '/*.sql');
sort($migration_files);

$pdo = getDBConnection();

// Create migrations tracking table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) UNIQUE NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

foreach ($migration_files as $file) {
    $migration_name = basename($file);
    
    // Check if already executed
    $stmt = $pdo->prepare("SELECT * FROM migrations WHERE migration = ?");
    $stmt->execute([$migration_name]);
    
    if ($stmt->rowCount() > 0) {
        echo "Skipping {$migration_name} (already executed)\n";
        continue;
    }
    
    // Execute migration
    echo "Running {$migration_name}...\n";
    $sql = file_get_contents($file);
    
    try {
        $pdo->exec($sql);
        
        // Record migration
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration_name]);
        
        echo "✓ {$migration_name} completed\n";
    } catch (PDOException $e) {
        echo "✗ {$migration_name} failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\nAll migrations completed!\n";
```

**Run migrations:**
```bash
php migrate.php
```

---

## 💾 Backup & Recovery

### Automated Backup Script

```bash
#!/bin/bash
# backup.sh

# Configuration
DB_NAME="dailycup_db"
DB_USER="dailycup_user"
DB_PASS="your_password"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=7

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/dailycup_$DATE.sql.gz

# Delete old backups
find $BACKUP_DIR -name "dailycup_*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Verify backup
if [ -f "$BACKUP_DIR/dailycup_$DATE.sql.gz" ]; then
    echo "Backup successful: dailycup_$DATE.sql.gz"
    
    # Optional: Upload to cloud storage
    # aws s3 cp $BACKUP_DIR/dailycup_$DATE.sql.gz s3://your-bucket/backups/
else
    echo "Backup failed!"
    exit 1
fi
```

**Make executable:**
```bash
chmod +x backup.sh
```

**Schedule with Cron:**
```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /path/to/backup.sh >> /var/log/mysql-backup.log 2>&1

# Add weekly backup (Sunday at 3 AM)
0 3 * * 0 /path/to/backup.sh >> /var/log/mysql-backup-weekly.log 2>&1
```

---

### Restore from Backup

```bash
# Decompress backup
gunzip dailycup_20260129_020000.sql.gz

# Restore database
mysql -u dailycup_user -p dailycup_db < dailycup_20260129_020000.sql

# Verify restoration
mysql -u dailycup_user -p

USE dailycup_db;
SHOW TABLES;
SELECT COUNT(*) FROM products;
EXIT;
```

---

### Cloud Backup (AWS S3)

```bash
# Install AWS CLI
apt install awscli -y

# Configure AWS credentials
aws configure

# Upload backup
aws s3 cp /var/backups/mysql/dailycup_backup.sql.gz s3://your-bucket/dailycup/backups/

# Download backup
aws s3 cp s3://your-bucket/dailycup/backups/dailycup_backup.sql.gz ./
```

---

## ⚡ Database Optimization

### Add Indexes

```sql
-- Products table
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_products_stock ON products(stock);
CREATE INDEX idx_products_price ON products(price);

-- Orders table
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);

-- Composite indexes
CREATE INDEX idx_products_cat_feat ON products(category_id, is_featured);
CREATE INDEX idx_orders_user_status ON orders(user_id, status);

-- Show all indexes
SHOW INDEX FROM products;
SHOW INDEX FROM orders;
```

---

### Optimize Tables

```sql
-- Analyze tables
ANALYZE TABLE products;
ANALYZE TABLE orders;
ANALYZE TABLE users;

-- Optimize tables (defragment)
OPTIMIZE TABLE products;
OPTIMIZE TABLE orders;

-- Check table status
SHOW TABLE STATUS WHERE Name = 'products';
```

---

### Database Maintenance Cron

```bash
# maintenance.sh
#!/bin/bash

mysql -u dailycup_user -p"$DB_PASS" dailycup_db <<EOF
ANALYZE TABLE products;
ANALYZE TABLE orders;
OPTIMIZE TABLE products;
OPTIMIZE TABLE orders;
EOF

echo "Database maintenance completed"
```

**Schedule weekly:**
```bash
# Every Sunday at 4 AM
0 4 * * 0 /path/to/maintenance.sh >> /var/log/mysql-maintenance.log 2>&1
```

---

## 🔐 Security Best Practices

### 1. Strong Passwords

```bash
# Generate strong password
openssl rand -base64 32
```

### 2. Limit Remote Access

```sql
-- Only allow localhost
CREATE USER 'dailycup_user'@'localhost' IDENTIFIED BY 'password';

-- If remote access needed (specific IP only)
CREATE USER 'dailycup_user'@'123.456.789.012' IDENTIFIED BY 'password';
```

### 3. Disable Remote Root

```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
bind-address = 127.0.0.1
```

### 4. Regular Updates

```bash
# Update MySQL
apt update
apt upgrade mysql-server
```

---

## ✅ Deployment Checklist

- [ ] Database created in production
- [ ] Database user created with strong password
- [ ] Schema imported successfully
- [ ] All tables verified
- [ ] Indexes created
- [ ] Sample data imported (if needed)
- [ ] Automated backups configured
- [ ] Backup restoration tested
- [ ] Database credentials updated in `.env`
- [ ] Connection tested from application
- [ ] Performance optimized
- [ ] Security hardened

---

## 🚨 Troubleshooting

### Connection Refused

```bash
# Check MySQL is running
systemctl status mysql

# Start MySQL
systemctl start mysql

# Check port
netstat -tlnp | grep 3306
```

### Access Denied

```sql
-- Verify user exists
SELECT User, Host FROM mysql.user WHERE User = 'dailycup_user';

-- Reset password
ALTER USER 'dailycup_user'@'localhost' IDENTIFIED BY 'new_password';
FLUSH PRIVILEGES;
```

### Import Errors

```bash
# Check SQL syntax
mysql -u root -p < dailycup_schema.sql --verbose

# Import with error logging
mysql -u root -p dailycup_db < dailycup_schema.sql 2> import_errors.log
```

---

**Database deployment ready!** 🗄️✅
