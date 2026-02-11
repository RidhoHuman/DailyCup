# 🚀 DEPLOYMENT GUIDE
**DailyCup Coffee CRM System**

---

## 📋 PRE-DEPLOYMENT REQUIREMENTS

### Minimum Server Requirements
- **PHP:** 8.0 or higher
- **MySQL/MariaDB:** 5.7+ / 10.3+
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **SSL Certificate:** Required (Let's Encrypt - FREE)
- **RAM:** 2GB minimum, 4GB recommended
- **Storage:** 20GB minimum
- **PHP Extensions:**
  - PDO
  - PDO_MySQL
  - GD (for image processing)
  - mbstring
  - OpenSSL
  - cURL
  - JSON

---

## 🔧 DEPLOYMENT STEPS

### Step 1: Prepare Server

#### Option A: Shared Hosting (Easy)
```bash
1. Purchase hosting with PHP 8+ support
2. Get domain name
3. Request SSL certificate from hosting provider
```

#### Option B: VPS (Recommended)
```bash
# Ubuntu 22.04 LTS

# 1. Update system
sudo apt update && sudo apt upgrade -y

# 2. Install LAMP Stack
sudo apt install apache2 -y
sudo apt install mysql-server -y
sudo apt install php8.2 php8.2-{mysql,gd,mbstring,curl,xml,zip} -y

# 3. Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2

# 4. Secure MySQL
sudo mysql_secure_installation

# 5. Install Let's Encrypt SSL
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

---

### Step 2: Upload Files

#### Via FTP/SFTP (FileZilla)
```
1. Connect to server with credentials
2. Upload all files to /var/www/html/dailycup (or public_html)
3. Ensure these folders exist and writable:
   - assets/images/products/
   - assets/images/reviews/
   - assets/images/returns/
   - assets/images/payments/
```

#### Via Git (Recommended)
```bash
cd /var/www/html
git clone https://github.com/yourusername/dailycup.git
cd dailycup
```

---

### Step 3: Set File Permissions

```bash
# Navigate to project folder
cd /var/www/html/dailycup

# Set ownership
sudo chown -R www-data:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make upload directories writable
chmod -R 775 assets/images/
```

---

### Step 4: Configure Database

#### Create Database
```sql
mysql -u root -p

CREATE DATABASE dailycup_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dailycup_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON dailycup_db.* TO 'dailycup_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Import Database
```bash
mysql -u dailycup_user -p dailycup_db < database/dailycup_db.sql
```

---

### Step 5: Configure Application

#### Edit config/database.php
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'dailycup_user');
define('DB_PASS', 'YOUR_STRONG_PASSWORD');
define('DB_NAME', 'dailycup_db');

function getDB() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // In production, log error to file, don't display
        error_log("Database Connection Error: " . $e->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}
?>
```

#### Edit config/constants.php
```php
<?php
// Site Configuration
define('SITE_URL', 'https://yourdomain.com'); // ⚠️ CHANGE THIS!
define('SITE_NAME', 'DailyCup Coffee');
define('ADMIN_EMAIL', 'admin@yourdomain.com');

// Business Rules
define('AUTO_APPROVE_THRESHOLD', 50000); // Rp 50,000
define('REFUND_WINDOW_DAYS', 3);
define('MAX_REFUNDS_PER_MONTH', 3);

// GPS Coordinates (⚠️ CHANGE TO YOUR ACTUAL LOCATION!)
define('CAFE_LATITUDE', -6.2088);  // Example: Jakarta
define('CAFE_LONGITUDE', 106.8456);

// Email Configuration (SMTP Recommended)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');

// Security
define('SESSION_LIFETIME', 3600); // 1 hour
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS only
ini_set('session.use_strict_mode', 1);

// Error Handling (Production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
error_reporting(E_ALL);
?>
```

---

### Step 6: Update GPS Coordinates

#### customer/track_order.php (Line ~51)
```javascript
const cafeLocation = {
    lat: -6.2088,  // ⚠️ Change to your cafe latitude
    lng: 106.8456, // ⚠️ Change to your cafe longitude
    address: 'DailyCup Coffee Shop'
};
```

#### admin/kurir/monitor.php (Line ~187)
```javascript
const cafeMarker = L.marker([-6.2088, 106.8456], { // ⚠️ Change coordinates
    icon: L.divIcon({
        className: 'cafe-marker',
        html: '<i class="bi bi-shop" style="font-size: 2rem; color: #28a745;"></i>',
        iconSize: [40, 40]
    })
}).addTo(map).bindPopup('<b>DailyCup Coffee</b><br>Cafe Location');
```

---

### Step 7: Configure Apache Virtual Host

#### Create VirtualHost Config
```bash
sudo nano /etc/apache2/sites-available/dailycup.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/dailycup
    
    <Directory /var/www/html/dailycup>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/dailycup-error.log
    CustomLog ${APACHE_LOG_DIR}/dailycup-access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/dailycup
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    <Directory /var/www/html/dailycup>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/dailycup-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/dailycup-ssl-access.log combined
</VirtualHost>
```

```bash
# Enable site and restart Apache
sudo a2ensite dailycup.conf
sudo systemctl restart apache2
```

---

### Step 8: Create .htaccess (Security & Redirects)

#### Root .htaccess
```apache
# Redirect HTTP to HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevent directory browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect config files
<FilesMatch "\.(sql|md|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

### Step 9: Configure Email (SMTP)

#### Install PHPMailer
```bash
cd /var/www/html/dailycup
composer require phpmailer/phpmailer
```

#### Update email functions in includes/functions.php
```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(SMTP_USER, SITE_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
```

---

### Step 10: Set Up Automated Backups

#### Create backup script
```bash
sudo nano /usr/local/bin/backup_dailycup.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/backup/dailycup"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="dailycup_db"
DB_USER="dailycup_user"
DB_PASS="YOUR_PASSWORD"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/dailycup

# Keep only last 7 days
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup_dailycup.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/backup_dailycup.sh
```

---

### Step 11: Test Production Environment

#### Checklist
- [ ] Visit https://yourdomain.com (check SSL)
- [ ] Register new customer account
- [ ] Place test order
- [ ] Login as kurir (test GPS in browser)
- [ ] Login as admin
- [ ] Test email delivery
- [ ] Test GPS tracking (HTTPS required!)
- [ ] Test on mobile device
- [ ] Check error logs: `tail -f /var/log/apache2/dailycup-error.log`

---

### Step 12: Performance Optimization

#### Enable OPcache
```bash
sudo nano /etc/php/8.2/apache2/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

#### Restart Apache
```bash
sudo systemctl restart apache2
```

---

## 🔒 SECURITY HARDENING

### 1. Hide PHP Version
```bash
sudo nano /etc/php/8.2/apache2/php.ini
# Set: expose_php = Off
```

### 2. Disable Dangerous Functions
```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

### 3. Set Up Fail2Ban
```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
```

### 4. Configure Firewall
```bash
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
```

---

## 📊 MONITORING SETUP

### Install Monitoring Tools
```bash
# Server monitoring
sudo apt install htop iotop nethogs -y

# Log monitoring
tail -f /var/log/apache2/dailycup-error.log
tail -f /var/www/html/dailycup/logs/error.log
```

### Set Up Uptime Monitoring
- Use external services (FREE):
  - UptimeRobot.com
  - StatusCake.com
  - Pingdom.com

---

## 🐛 TROUBLESHOOTING

### GPS Not Working
```
Issue: Geolocation API not working
Solution: HTTPS is REQUIRED for browser geolocation
Check: Certificate installed correctly
```

### Email Not Sending
```
Issue: Emails not delivered
Solution: 
1. Check SMTP credentials
2. Enable "Less secure app access" (Gmail)
3. Use App Password instead of account password
4. Check spam folder
```

### Database Connection Failed
```
Issue: Can't connect to database
Solution:
1. Check credentials in config/database.php
2. Verify MySQL is running: sudo systemctl status mysql
3. Check firewall: sudo ufw status
```

### 500 Internal Server Error
```
Issue: White screen or 500 error
Solution:
1. Check Apache error log
2. Verify file permissions (644 files, 755 dirs)
3. Check .htaccess syntax
4. Enable error display temporarily for debugging
```

---

## 📞 POST-DEPLOYMENT SUPPORT

### Update Admin Account
```sql
mysql -u dailycup_user -p dailycup_db

-- Change admin password
UPDATE users SET password = '$2y$10$HASHED_PASSWORD' WHERE email = 'admin@yourdomain.com';
```

### Update Cafe Information
- Edit cafe coordinates in track_order.php and monitor.php
- Update contact information in footer
- Add actual cafe photos

### Maintenance Mode
```php
// Add to index.php for maintenance
if (!isset($_SESSION['admin_id'])) {
    die('Site under maintenance. Please check back soon.');
}
```

---

## ✅ POST-LAUNCH CHECKLIST

- [ ] Change all default passwords
- [ ] Configure actual cafe GPS coordinates
- [ ] Test email delivery
- [ ] Test GPS tracking on mobile
- [ ] Set up automated backups
- [ ] Configure monitoring
- [ ] Update contact information
- [ ] Add real product data
- [ ] Train staff on system usage
- [ ] Document admin procedures

---

## 📚 ADDITIONAL RESOURCES

- **PHP Documentation:** https://www.php.net/docs.php
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **Leaflet.js Docs:** https://leafletjs.com/reference.html
- **Bootstrap 5 Docs:** https://getbootstrap.com/docs/5.3/
- **Let's Encrypt:** https://letsencrypt.org/getting-started/

---

**Deployment Guide Version:** 1.0  
**Last Updated:** January 11, 2026  
**System:** DailyCup Coffee CRM

🚀 **GOOD LUCK WITH YOUR DEPLOYMENT!**
