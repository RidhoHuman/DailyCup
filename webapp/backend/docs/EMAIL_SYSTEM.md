# Email Notification System - DailyCup

## Overview
Automated email notification system that sends emails for order confirmations, payment receipts, and status updates.

## Features
- ✅ Order confirmation emails
- ✅ Payment confirmation emails  
- ✅ Order status update emails
- ✅ Welcome emails for new users
- ✅ Professional HTML templates
- ✅ Responsive design for all devices
- ✅ Inline CSS for email client compatibility

## Configuration

### Environment Variables (.env)
```env
# Email Settings
SMTP_ENABLED=true
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=noreply@dailycup.com
SMTP_FROM_NAME="DailyCup Coffee Shop"
SMTP_ENCRYPTION=tls
APP_URL=http://localhost:3000
```

### Gmail Setup (Recommended for Development)
1. Go to Google Account settings
2. Enable 2-Step Verification
3. Generate App Password:
   - Go to Security → 2-Step Verification → App passwords
   - Select "Mail" and your device
   - Copy the 16-character password
4. Use the app password in `SMTP_PASSWORD`

### Production Setup
For production, use a dedicated SMTP service:
- SendGrid
- Amazon SES
- Mailgun
- Postmark

## File Structure
```
backend/
├── api/
│   └── email/
│       └── EmailService.php      # Main email service class
├── templates/
│   └── email/
│       ├── order_confirmation.html
│       ├── payment_confirmation.html
│       ├── status_update.html
│       └── welcome.html
└── tests/
    └── test_email.php            # Email testing script
```

## Email Templates

### 1. Order Confirmation
**Triggered:** When new order is created  
**Sent to:** Customer email  
**Contains:**
- Order number and date
- Item list with quantities and prices
- Total amount
- Delivery method
- Payment method
- Link to track order

### 2. Payment Confirmation  
**Triggered:** When payment is successful (paid status)  
**Sent to:** Customer email  
**Contains:**
- Order number
- Payment date and method
- Items purchased
- Total amount paid
- Receipt information
- Link to order details

### 3. Status Update
**Triggered:** When admin updates order status  
**Sent to:** Customer email  
**Contains:**
- Order number
- New status (Processing/Ready/Delivering/Completed)
- Status message
- Progress timeline
- Link to track order

### 4. Welcome Email
**Triggered:** When new user registers (optional)  
**Sent to:** New user email  
**Contains:**
- Welcome message
- Account details
- Feature highlights
- Links to menu and profile
- Pro tips for first order

## Integration Points

### create_order.php
Sends order confirmation after successful order creation:
```php
EmailService::sendOrderConfirmation($orderData, $customer);
```

### pay_order.php
Sends payment confirmation when manual payment marked as paid:
```php
EmailService::sendPaymentConfirmation($orderData, $customerData);
```

### notify_midtrans.php
Sends payment confirmation when webhook receives 'paid' status:
```php
EmailService::sendPaymentConfirmation($orderData, $customerData);
```

### admin/update_order_status.php
Sends status update when admin changes order status:
```php
EmailService::sendStatusUpdate($orderData, $customerData, $newStatus);
```

## EmailService Methods

### sendOrderConfirmation($order, $customer)
Send order confirmation email
```php
$order = [
    'order_number' => 'ORD-123',
    'items' => [...],
    'total' => 80000,
    'subtotal' => 80000,
    'discount' => 0,
    'delivery_method' => 'takeaway',
    'payment_method' => 'midtrans',
    'created_at' => '2025-01-15 10:30:00'
];

$customer = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'address' => 'Jl. Example No. 123'
];

EmailService::sendOrderConfirmation($order, $customer);
```

### sendPaymentConfirmation($order, $customer)
Send payment confirmation email
```php
$order = [
    'order_number' => 'ORD-123',
    'items' => [...],
    'total' => 80000,
    'payment_method' => 'Midtrans'
];

$customer = [
    'name' => 'John Doe',
    'email' => 'john@example.com'
];

EmailService::sendPaymentConfirmation($order, $customer);
```

### sendStatusUpdate($order, $customer, $newStatus)
Send order status update email
```php
$order = ['order_number' => 'ORD-123'];
$customer = ['name' => 'John Doe', 'email' => 'john@example.com'];
$status = 'processing'; // or 'ready', 'delivering', 'completed'

EmailService::sendStatusUpdate($order, $customer, $status);
```

### sendWelcomeEmail($user)
Send welcome email to new user
```php
$user = [
    'name' => 'John Doe',
    'email' => 'john@example.com'
];

EmailService::sendWelcomeEmail($user);
```

## Testing

### Run Test Script
```bash
cd backend/tests
php test_email.php
```

### Manual Testing
1. Update test email in `test_email.php`:
   ```php
   'email' => 'your-email@gmail.com'
   ```

2. Set `SMTP_ENABLED=true` in `.env`

3. Run the test script

4. Check your email (including spam folder)

### Test via API
**Test order confirmation:**
```bash
POST http://localhost/DailyCup/backend/api/create_order.php
```

**Test payment confirmation:**
```bash
POST http://localhost/DailyCup/backend/api/pay_order.php
{
  "orderId": "ORD-123",
  "action": "paid"
}
```

**Test status update:**
```bash
POST http://localhost/DailyCup/backend/api/admin/update_order_status.php
{
  "orderId": "ORD-123",
  "status": "processing"
}
```

## Troubleshooting

### Emails not sending
1. Check `SMTP_ENABLED=true` in `.env`
2. Verify SMTP credentials
3. Check PHP `error_log` for errors
4. Test `mail()` function on server
5. Check spam/junk folder

### Gmail "Less secure app" error
- Use App Password instead of regular password
- Enable 2-Step Verification first

### Emails going to spam
- Use verified domain email
- Add SPF/DKIM records
- Use dedicated SMTP service (SendGrid, SES)
- Avoid spam trigger words in subject

### Template not loading
- Check file path in `EmailService::loadTemplate()`
- Verify template file exists
- Check file permissions

## Development vs Production

### Development (Current Setup)
- Uses PHP `mail()` function
- Email disabled by default (`SMTP_ENABLED=false`)
- Logs email actions to error_log
- Good for testing without actual sending

### Production Recommendations
1. **Use dedicated SMTP service** (SendGrid, Amazon SES, Mailgun)
2. **Implement PHPMailer** for better compatibility:
   ```php
   composer require phpmailer/phpmailer
   ```
3. **Add email queue system** (prevent blocking on send)
4. **Monitor delivery rates**
5. **Set up bounce handling**
6. **Use template engine** (Twig, Blade)

## Security Considerations
- ✅ Never log email passwords
- ✅ Validate email addresses
- ✅ Sanitize template data
- ✅ Use TLS encryption
- ✅ Rate limit email sending
- ✅ Don't expose SMTP credentials

## Future Enhancements
- [ ] Email queue system (Redis/RabbitMQ)
- [ ] Email tracking (open/click rates)
- [ ] Unsubscribe functionality
- [ ] Multi-language support
- [ ] Custom email preferences per user
- [ ] SMS notifications integration
- [ ] Push notifications
- [ ] Email template editor (admin panel)

## Support
For issues or questions, check:
- Error logs: `backend/logs/error.log`
- Email test script: `backend/tests/test_email.php`
- SMTP settings: `backend/api/.env`
