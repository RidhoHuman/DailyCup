# 📊 DATABASE SCHEMA DOCUMENTATION
**DailyCup Coffee CRM System**

---

## 📋 OVERVIEW

**Database Name:** `dailycup_db`  
**Character Set:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  
**Engine:** InnoDB (supports transactions & foreign keys)  
**Total Tables:** 15

---

## 📁 TABLE STRUCTURE

### 1. `users` - Customer Accounts
**Purpose:** Store customer account information

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | NO | | | Full name |
| email | VARCHAR(100) | NO | UNI | | Email (unique) |
| password | VARCHAR(255) | NO | | | Bcrypt hashed |
| phone | VARCHAR(20) | YES | | NULL | Phone number |
| address | TEXT | YES | | NULL | Delivery address |
| loyalty_points | INT(11) | NO | | 0 | Current points |
| refund_count | INT(11) | NO | | 0 | Monthly refund count |
| last_refund_date | DATE | YES | | NULL | Last refund request |
| oauth_provider | VARCHAR(50) | YES | | NULL | google/facebook |
| oauth_id | VARCHAR(255) | YES | UNI | NULL | OAuth user ID |
| is_admin | TINYINT(1) | NO | | 0 | Admin flag |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Registration date |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`email`)
- UNIQUE KEY (`oauth_id`)
- INDEX (`refund_count`, `last_refund_date`)

**Relations:**
- → orders (one-to-many)
- → favorites (one-to-many)
- → reviews (one-to-many)
- → notifications (one-to-many)
- → loyalty_transactions (one-to-many)
- → refunds (one-to-many)

---

### 2. `categories` - Product Categories
**Purpose:** Organize products into categories

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | NO | UNI | | Category name |
| description | TEXT | YES | | NULL | Description |
| image | VARCHAR(255) | YES | | NULL | Category image |
| display_order | INT(11) | NO | | 0 | Sort order |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Created date |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`name`)

**Relations:**
- → products (one-to-many)

---

### 3. `products` - Menu Items
**Purpose:** Store coffee shop menu items

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| category_id | INT(11) | NO | FK | | References categories.id |
| name | VARCHAR(100) | NO | | | Product name |
| description | TEXT | YES | | NULL | Product description |
| price | DECIMAL(10,2) | NO | | | Base price |
| image | VARCHAR(255) | YES | | NULL | Product image |
| stock | INT(11) | NO | | 0 | Current stock |
| is_available | TINYINT(1) | NO | | 1 | Availability flag |
| rating_avg | DECIMAL(3,2) | NO | | 0.00 | Average rating |
| rating_count | INT(11) | NO | | 0 | Number of reviews |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Created date |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`category_id`) REFERENCES `categories(id)`
- INDEX (`is_available`)

**Relations:**
- ← categories (many-to-one)
- → order_items (one-to-many)
- → favorites (one-to-many)
- → reviews (one-to-many)

---

### 4. `orders` - Customer Orders
**Purpose:** Store order transactions

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| user_id | INT(11) | NO | FK | | References users.id |
| kurir_id | INT(11) | YES | FK | NULL | References kurir.id |
| order_number | VARCHAR(50) | NO | UNI | | Unique order number |
| status | ENUM | NO | IDX | 'pending' | Order status |
| subtotal | DECIMAL(10,2) | NO | | | Items subtotal |
| discount_amount | DECIMAL(10,2) | NO | | 0.00 | Discount applied |
| loyalty_points_used | INT(11) | NO | | 0 | Points redeemed |
| loyalty_discount | DECIMAL(10,2) | NO | | 0.00 | Points value |
| final_amount | DECIMAL(10,2) | NO | | | Final total |
| payment_method | VARCHAR(50) | NO | | | Payment method |
| payment_proof | VARCHAR(255) | YES | | NULL | Payment image |
| delivery_type | VARCHAR(20) | NO | | 'delivery' | delivery/pickup |
| delivery_address | TEXT | YES | | NULL | Delivery address |
| notes | TEXT | YES | | NULL | Order notes |
| assigned_at | DATETIME | YES | | NULL | Kurir assigned time |
| pickup_time | DATETIME | YES | | NULL | Pickup timestamp |
| delivery_time | DATETIME | YES | | NULL | Delivery timestamp |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Order created |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | Last updated |

**Status Values:**
- `pending` - Awaiting payment
- `confirmed` - Payment confirmed
- `processing` - Being prepared
- `ready` - Ready for pickup/delivery
- `delivering` - Out for delivery
- `completed` - Successfully delivered
- `cancelled` - Cancelled

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`order_number`)
- FOREIGN KEY (`user_id`) REFERENCES `users(id)`
- FOREIGN KEY (`kurir_id`) REFERENCES `kurir(id)`
- INDEX (`status`)
- INDEX (`kurir_id`)
- INDEX (`created_at`)

**Relations:**
- ← users (many-to-one)
- ← kurir (many-to-one)
- → order_items (one-to-many)
- → refunds (one-to-one)
- → delivery_history (one-to-many)

---

### 5. `order_items` - Order Line Items
**Purpose:** Store individual items in each order

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| order_id | INT(11) | NO | FK | | References orders.id |
| product_id | INT(11) | NO | FK | | References products.id |
| product_name | VARCHAR(100) | NO | | | Product name snapshot |
| price | DECIMAL(10,2) | NO | | | Price snapshot |
| quantity | INT(11) | NO | | | Quantity ordered |
| subtotal | DECIMAL(10,2) | NO | | | Item total |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`order_id`) REFERENCES `orders(id)` ON DELETE CASCADE
- FOREIGN KEY (`product_id`) REFERENCES `products(id)`

**Relations:**
- ← orders (many-to-one)
- ← products (many-to-one)

---

### 6. `favorites` - Customer Favorites
**Purpose:** Store customer favorite products

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| user_id | INT(11) | NO | FK | | References users.id |
| product_id | INT(11) | NO | FK | | References products.id |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Added date |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`user_id`, `product_id`)
- FOREIGN KEY (`user_id`) REFERENCES `users(id)` ON DELETE CASCADE
- FOREIGN KEY (`product_id`) REFERENCES `products(id)` ON DELETE CASCADE

**Relations:**
- ← users (many-to-one)
- ← products (many-to-one)

---

### 7. `reviews` - Product Reviews
**Purpose:** Store customer product reviews

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| user_id | INT(11) | NO | FK | | References users.id |
| product_id | INT(11) | NO | FK | | References products.id |
| order_id | INT(11) | NO | FK | | References orders.id |
| rating | INT(11) | NO | | | 1-5 stars |
| comment | TEXT | YES | | NULL | Review text |
| image | VARCHAR(255) | YES | | NULL | Review photo |
| is_approved | TINYINT(1) | NO | | 0 | Admin approval |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Review date |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`user_id`) REFERENCES `users(id)` ON DELETE CASCADE
- FOREIGN KEY (`product_id`) REFERENCES `products(id)` ON DELETE CASCADE
- FOREIGN KEY (`order_id`) REFERENCES `orders(id)` ON DELETE CASCADE
- INDEX (`is_approved`)

**Relations:**
- ← users (many-to-one)
- ← products (many-to-one)
- ← orders (many-to-one)

---

### 8. `notifications` - User Notifications
**Purpose:** Store user notifications

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| user_id | INT(11) | NO | FK | | References users.id |
| type | VARCHAR(50) | NO | IDX | | Notification type |
| title | VARCHAR(255) | NO | | | Notification title |
| message | TEXT | NO | | | Notification content |
| link | VARCHAR(255) | YES | | NULL | Target URL |
| is_read | TINYINT(1) | NO | IDX | 0 | Read status |
| created_at | TIMESTAMP | NO | IDX | CURRENT_TIMESTAMP | Created date |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`user_id`) REFERENCES `users(id)` ON DELETE CASCADE
- INDEX (`type`)
- INDEX (`is_read`)
- INDEX (`created_at`)

**Relations:**
- ← users (many-to-one)

---

### 9. `discounts` - Promotional Discounts
**Purpose:** Store discount campaigns

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | NO | | | Discount name |
| type | ENUM | NO | | 'percentage' | percentage/fixed |
| value | DECIMAL(10,2) | NO | | | Discount value |
| min_purchase | DECIMAL(10,2) | NO | | 0.00 | Min order amount |
| max_discount | DECIMAL(10,2) | YES | | NULL | Max discount cap |
| start_date | DATETIME | NO | | | Start datetime |
| end_date | DATETIME | NO | | | End datetime |
| is_active | TINYINT(1) | NO | | 1 | Active status |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Created date |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`is_active`)
- INDEX (`start_date`, `end_date`)

---

### 10. `redeem_codes` - Loyalty Redeem Codes
**Purpose:** Store loyalty point redemption codes

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| code | VARCHAR(20) | NO | UNI | | Redeem code |
| points | INT(11) | NO | | | Points value |
| is_used | TINYINT(1) | NO | IDX | 0 | Used status |
| used_by | INT(11) | YES | FK | NULL | References users.id |
| used_at | DATETIME | YES | | NULL | Used datetime |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Created date |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`code`)
- INDEX (`is_used`)
- FOREIGN KEY (`used_by`) REFERENCES `users(id)`

**Relations:**
- ← users (many-to-one, optional)

---

### 11. `loyalty_transactions` - Loyalty Point History
**Purpose:** Track loyalty points transactions

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| user_id | INT(11) | NO | FK | | References users.id |
| type | ENUM | NO | IDX | | earn/redeem/refund |
| points | INT(11) | NO | | | Points amount |
| order_id | INT(11) | YES | FK | NULL | References orders.id |
| description | TEXT | NO | | | Transaction note |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Transaction date |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`user_id`) REFERENCES `users(id)` ON DELETE CASCADE
- FOREIGN KEY (`order_id`) REFERENCES `orders(id)` ON DELETE SET NULL
- INDEX (`type`)

**Relations:**
- ← users (many-to-one)
- ← orders (many-to-one, optional)

---

### 12. `refunds` - Refund Requests
**Purpose:** Store refund requests and processing

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| order_id | INT(11) | NO | FK | | References orders.id |
| user_id | INT(11) | NO | FK | | References users.id |
| amount | DECIMAL(10,2) | NO | | | Refund amount |
| reason | TEXT | NO | | | Refund reason |
| bank_name | VARCHAR(100) | YES | | NULL | Bank name |
| account_number | VARCHAR(50) | YES | | NULL | Account number |
| account_holder | VARCHAR(100) | YES | | NULL | Account holder name |
| proof_image | VARCHAR(255) | YES | | NULL | Proof photo |
| status | ENUM | NO | IDX | 'pending' | Refund status |
| admin_notes | TEXT | YES | | NULL | Admin notes |
| approved_by | INT(11) | YES | FK | NULL | Admin user ID |
| approved_at | DATETIME | YES | | NULL | Approval datetime |
| processed_at | DATETIME | YES | | NULL | Processing datetime |
| created_at | TIMESTAMP | NO | IDX | CURRENT_TIMESTAMP | Request date |

**Status Values:**
- `pending` - Awaiting review
- `approved` - Approved for processing
- `rejected` - Rejected
- `processed` - Refund completed

**Auto-Approve Logic:**
- Amount < Rp 50,000 → Auto-approved
- Amount ≥ Rp 50,000 → Manual review

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`order_id`) REFERENCES `orders(id)` ON DELETE CASCADE
- FOREIGN KEY (`user_id`) REFERENCES `users(id)` ON DELETE CASCADE
- INDEX (`status`)
- INDEX (`created_at`)

**Relations:**
- ← orders (one-to-one)
- ← users (many-to-one)

---

### 13. `kurir` - Delivery Drivers
**Purpose:** Store kurir (delivery driver) accounts

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | NO | | | Full name |
| phone | VARCHAR(20) | NO | UNI | | Phone (login ID) |
| email | VARCHAR(100) | NO | UNI | | Email address |
| password | VARCHAR(255) | NO | | | Bcrypt hashed |
| photo | VARCHAR(255) | YES | | NULL | Profile photo |
| vehicle_type | VARCHAR(50) | NO | | | motor/bike/car |
| vehicle_number | VARCHAR(20) | YES | | NULL | License plate |
| status | ENUM | NO | IDX | 'offline' | available/busy/offline |
| rating | DECIMAL(3,2) | NO | | 0.00 | Average rating |
| total_deliveries | INT(11) | NO | | 0 | Completed count |
| is_active | TINYINT(1) | NO | | 1 | Active status |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Registration date |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`phone`)
- UNIQUE KEY (`email`)
- INDEX (`status`)
- INDEX (`is_active`)

**Relations:**
- → orders (one-to-many)
- → kurir_location (one-to-one)
- → delivery_history (one-to-many)

---

### 14. `kurir_location` - GPS Location Tracking
**Purpose:** Store real-time GPS coordinates of kurir

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| kurir_id | INT(11) | NO | FK | | References kurir.id |
| latitude | DECIMAL(10,8) | NO | | | GPS latitude |
| longitude | DECIMAL(11,8) | NO | | | GPS longitude |
| updated_at | TIMESTAMP | NO | IDX | CURRENT_TIMESTAMP ON UPDATE | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`kurir_id`)
- FOREIGN KEY (`kurir_id`) REFERENCES `kurir(id)` ON DELETE CASCADE
- INDEX (`updated_at`)

**Update Frequency:** Every 10 seconds (when kurir dashboard open)

**Relations:**
- ← kurir (one-to-one)

---

### 15. `delivery_history` - Delivery Status Log
**Purpose:** Track delivery status changes

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | INT(11) | NO | PRI | AUTO_INCREMENT | Primary key |
| order_id | INT(11) | NO | FK | | References orders.id |
| kurir_id | INT(11) | NO | FK | | References kurir.id |
| status | VARCHAR(50) | NO | IDX | | Status name |
| notes | TEXT | YES | | NULL | Status notes |
| location_lat | DECIMAL(10,8) | YES | | NULL | Location latitude |
| location_lng | DECIMAL(11,8) | YES | | NULL | Location longitude |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | Event datetime |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`order_id`) REFERENCES `orders(id)` ON DELETE CASCADE
- FOREIGN KEY (`kurir_id`) REFERENCES `kurir(id)` ON DELETE CASCADE
- INDEX (`status`)

**Relations:**
- ← orders (many-to-one)
- ← kurir (many-to-one)

---

## 🔗 ENTITY RELATIONSHIP DIAGRAM

```
┌─────────────┐
│    users    │──────┐
└─────────────┘      │
      │              │
      │              │
      ├──────────────┼──────────────┐
      │              │              │
      ▼              ▼              ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│   orders    │ │  favorites  │ │   reviews   │
└─────────────┘ └─────────────┘ └─────────────┘
      │              │              │
      │              │              │
      ▼              ▼              ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ order_items │ │  products   │ │ categories  │
└─────────────┘ └─────────────┘ └─────────────┘
      │
      │
      ▼
┌─────────────┐      ┌─────────────┐
│   refunds   │      │    kurir    │
└─────────────┘      └─────────────┘
                            │
                            ├──────────────┐
                            ▼              ▼
                    ┌───────────────┐ ┌──────────────────┐
                    │kurir_location│ │delivery_history  │
                    └───────────────┘ └──────────────────┘
```

---

## 📈 DATABASE STATISTICS

### Sample Capacities
- **Users:** Unlimited
- **Products:** 1,000+ recommended
- **Orders:** 100,000+ supported
- **Kurir:** 100+ supported
- **Reviews:** Unlimited
- **GPS Updates:** Real-time (10 sec interval)

### Storage Estimates (1 Year)
- **Orders (1000/month):** ~50MB
- **Order Items (avg 2 items/order):** ~100MB
- **Reviews with images:** ~500MB
- **GPS Location History:** ~200MB
- **Total Estimated:** ~1GB/year

---

## 🔧 MAINTENANCE QUERIES

### Reset Auto-Increment IDs
```sql
ALTER TABLE table_name AUTO_INCREMENT = 1;
```

### Rebuild Indexes
```sql
ANALYZE TABLE table_name;
OPTIMIZE TABLE table_name;
```

### Clean Old Notifications (30 days)
```sql
DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Update Product Ratings
```sql
UPDATE products p
SET rating_avg = (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id AND r.is_approved = 1),
    rating_count = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.is_approved = 1);
```

---

**Database Schema Version:** 1.0  
**Last Updated:** January 11, 2026  
**Total Tables:** 15  
**Total Relations:** 25+

📊 **SCHEMA DOCUMENTATION COMPLETE**
