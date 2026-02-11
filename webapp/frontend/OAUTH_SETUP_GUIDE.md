# OAuth Setup Guide for DailyCup

## 🔐 NextAuth.js OAuth Configuration

This guide will help you set up OAuth authentication with Google, Facebook, and Apple for the DailyCup Next.js application.

---

## 📋 Prerequisites

1. **Google Cloud Console** account
2. **Facebook Developer** account
3. **Apple Developer** account (for Sign in with Apple)
4. **DailyCup backend** running (PHP API)

---

## 🔧 Environment Setup

### 1. Copy Environment File

```bash
cd webapp/frontend
cp .env.example .env.local
```

### 2. Generate NextAuth Secret

```bash
# On Windows PowerShell:
openssl rand -base64 32

# Or use online generator:
# https://generate-secret.vercel.app/32
```

Add to `.env.local`:
```env
NEXTAUTH_SECRET=your-generated-secret-here
NEXTAUTH_URL=http://localhost:3000
```

---

## 🌐 Google OAuth Setup

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable **Google+ API**

### Step 2: Create OAuth Credentials

1. Navigate to **APIs & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth client ID**
3. Choose **Web application**
4. Configure:
   - **Name**: DailyCup
   - **Authorized JavaScript origins**: 
     - `http://localhost:3000`
     - `https://yourdomain.com` (production)
   - **Authorized redirect URIs**:
     - `http://localhost:3000/api/auth/callback/google`
     - `https://yourdomain.com/api/auth/callback/google` (production)

### Step 3: Add to .env.local

```env
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
```

---

## 📘 Facebook OAuth Setup

### Step 1: Create Facebook App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Click **My Apps** → **Create App**
3. Choose **Consumer** app type
4. Fill in app details

### Step 2: Configure Facebook Login

1. In your app dashboard, add **Facebook Login** product
2. Go to **Settings** → **Basic**
3. Add **App Domains**: `localhost` (dev) and your domain (prod)
4. Go to **Facebook Login** → **Settings**
5. Add **Valid OAuth Redirect URIs**:
   - `http://localhost:3000/api/auth/callback/facebook`
   - `https://yourdomain.com/api/auth/callback/facebook` (production)

### Step 3: Add to .env.local

```env
FACEBOOK_APP_ID=your-facebook-app-id
FACEBOOK_APP_SECRET=your-facebook-app-secret
```

---

## 🍎 Apple OAuth Setup (Sign in with Apple)

### Step 1: Apple Developer Account

1. Go to [Apple Developer](https://developer.apple.com/account/)
2. Navigate to **Certificates, Identifiers & Profiles**

### Step 2: Create App ID

1. Click **Identifiers** → **+** button
2. Select **App IDs** → **Continue**
3. Choose **App** → **Continue**
4. Configure:
   - **Description**: DailyCup
   - **Bundle ID**: `com.dailycup.webapp`
   - Enable **Sign in with Apple**

### Step 3: Create Service ID

1. Click **Identifiers** → **+** button
2. Select **Services IDs** → **Continue**
3. Configure:
   - **Description**: DailyCup Web
   - **Identifier**: `com.dailycup.webapp.service`
   - Enable **Sign in with Apple**
4. Configure **Sign in with Apple**:
   - **Primary App ID**: Select the App ID created above
   - **Web Domain**: `yourdomain.com`
   - **Return URLs**: 
     - `http://localhost:3000/api/auth/callback/apple` (dev)
     - `https://yourdomain.com/api/auth/callback/apple` (prod)

### Step 4: Create Private Key

1. Go to **Keys** → **+** button
2. Configure:
   - **Key Name**: DailyCup Apple Sign In Key
   - Enable **Sign in with Apple**
   - Configure key with your App ID
3. **Download the key** (`.p8` file) - you can only download once!
4. Note your **Key ID** and **Team ID**

### Step 5: Add to .env.local

```env
APPLE_CLIENT_ID=com.dailycup.webapp.service
APPLE_TEAM_ID=YOUR_TEAM_ID
APPLE_KEY_ID=YOUR_KEY_ID
APPLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
YOUR_PRIVATE_KEY_CONTENT_HERE
-----END PRIVATE KEY-----"
```

---

## 🔗 Backend PHP Integration

### Update PHP Backend API

Your PHP backend (`api/auth.php`) should handle OAuth login:

```php
<?php
// api/auth.php

if ($_POST['action'] === 'oauth_login') {
    $provider = $_POST['provider']; // 'google', 'facebook', 'apple'
    $email = $_POST['email'];
    $name = $_POST['name'];
    $oauth_id = $_POST['oauth_id'];
    $picture = $_POST['picture'];
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR oauth_id = ?");
    $stmt->execute([$email, $oauth_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create new user
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, oauth_provider, oauth_id, profile_picture, email_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$name, $email, $provider, $oauth_id, $picture]);
        $user_id = $pdo->lastInsertId();
        
        // Fetch created user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        // Update existing user with OAuth info if needed
        $stmt = $pdo->prepare("
            UPDATE users 
            SET oauth_provider = ?, oauth_id = ?, profile_picture = ?, email_verified = 1
            WHERE id = ?
        ");
        $stmt->execute([$provider, $oauth_id, $picture, $user['id']]);
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    
    // Store session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['token'] = $token;
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'customer',
            'loyalty_points' => $user['loyalty_points'] ?? 0,
            'profile_picture' => $user['profile_picture']
        ],
        'token' => $token
    ]);
    exit;
}
?>
```

---

## 🚀 Testing OAuth Flow

### 1. Start Development Server

```bash
cd webapp/frontend
npm run dev
```

### 2. Test OAuth Login

1. Navigate to `http://localhost:3000/login`
2. Click on **Google**, **Facebook**, or **Apple** button
3. Complete OAuth flow in popup/redirect
4. Verify you're redirected back and logged in

### 3. Debug Mode

Add to `.env.local` for debugging:
```env
NEXTAUTH_DEBUG=true
```

Check console and network tab for OAuth flow details.

---

## 🔒 Security Best Practices

1. **Never commit** `.env.local` to git
2. **Rotate secrets** regularly in production
3. **Use HTTPS** in production
4. **Implement CSRF protection** (NextAuth handles this)
5. **Validate redirect URLs** on OAuth provider settings
6. **Monitor OAuth usage** in provider dashboards

---

## 📱 Production Deployment

### 1. Update OAuth Provider Settings

- Add production domain to **Authorized domains**
- Add production **redirect URIs**
- Update **callback URLs**

### 2. Update Environment Variables

```env
NEXTAUTH_URL=https://yourdomain.com
NEXTAUTH_SECRET=different-secret-for-production
```

### 3. SSL/HTTPS Required

OAuth providers require HTTPS in production. Use:
- Vercel (automatic HTTPS)
- Netlify (automatic HTTPS)
- Your own SSL certificate

---

## ❓ Troubleshooting

### Google OAuth Issues

- **"redirect_uri_mismatch"**: Check authorized redirect URIs
- **"invalid_client"**: Verify client ID and secret
- **"access_denied"**: User cancelled or app not verified

### Facebook OAuth Issues

- **"App Not Set Up"**: Enable Facebook Login product
- **"Invalid OAuth Redirect URI"**: Check Facebook Login settings
- **"App Not Public"**: Make app public in Facebook dashboard

### Apple OAuth Issues

- **"invalid_client"**: Check Service ID configuration
- **"invalid_request"**: Verify redirect URI matches exactly
- **Private key issues**: Ensure `.p8` file content is correct

---

## 📚 Additional Resources

- [NextAuth.js Documentation](https://next-auth.js.org/)
- [Google OAuth Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login Documentation](https://developers.facebook.com/docs/facebook-login)
- [Sign in with Apple Documentation](https://developer.apple.com/sign-in-with-apple/)

---

## ✅ Checklist

- [ ] NextAuth.js installed
- [ ] Environment variables configured
- [ ] Google OAuth credentials created
- [ ] Facebook app configured
- [ ] Apple Sign In set up (optional)
- [ ] PHP backend updated
- [ ] OAuth tested locally
- [ ] Production domains configured
- [ ] SSL/HTTPS enabled in production

---

*Last updated: February 5, 2026*
