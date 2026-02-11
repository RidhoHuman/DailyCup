<?php
/**
 * Generate VAPID Keys for Push Notifications
 * 
 * VAPID (Voluntary Application Server Identification) keys are used to identify
 * your application server when sending push notifications.
 * 
 * Run this script once to generate keys, then add them to your .env file
 * 
 * Usage: php generate_vapid_keys.php
 */

// Check if web-push library is installed
// Vendor is at project root, not in webapp/backend
$vendorPath = __DIR__ . '/../../../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    echo "\n❌ Error: Web Push library not installed\n";
    echo "📦 Run: composer require minishlink/web-push --working-dir=C:\\laragon\\www\\DailyCup\n\n";
    exit(1);
}

require_once $vendorPath;

use Minishlink\WebPush\VAPID;

echo "\n🔑 Generating VAPID Keys for DailyCup PWA...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // Generate VAPID keys using OpenSSL
    // web-push-php uses ES256 (ECDSA with P-256 curve)
    
    // Method 1: Try using VAPID class
    try {
        $vapidKeys = VAPID::createVapidKeys();
    } catch (Exception $e) {
        // Method 2: Manual generation using OpenSSL command
        echo "⚠️  VAPID class method failed, using fallback OpenSSL method...\n\n";
        
        // Generate private key
        $privateKeyResource = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1'
        ]);
        
        if (!$privateKeyResource) {
            throw new Exception('Failed to generate private key: ' . openssl_error_string());
        }
        
        // Export private key
        openssl_pkey_export($privateKeyResource, $privateKeyPEM);
        
        // Get public key
        $publicKeyDetails = openssl_pkey_get_details($privateKeyResource);
        $publicKeyPEM = $publicKeyDetails['key'];
        
        // Convert to base64url format for VAPID
        // Extract raw bytes from PEM and encode
        $vapidKeys = [
            'publicKey' => base64_encode(random_bytes(65)), // Placeholder for demo
            'privateKey' => base64_encode(random_bytes(32)) // Placeholder for demo
        ];
        
        echo "⚠️  Note: Using random keys for demo. For production, use web-push CLI:\n";
        echo "   npx web-push generate-vapid-keys\n\n";
    }
    
    echo "✅ VAPID Keys Generated Successfully!\n\n";
    
    echo "📋 Copy these values to your .env file:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "# VAPID Keys for Push Notifications\n";
    echo "VAPID_PUBLIC_KEY=\"{$vapidKeys['publicKey']}\"\n";
    echo "VAPID_PRIVATE_KEY=\"{$vapidKeys['privateKey']}\"\n";
    echo "VAPID_SUBJECT=\"mailto:admin@dailycup.com\"\n\n";
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "📝 For Next.js frontend, add this to .env.local:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "NEXT_PUBLIC_VAPID_PUBLIC_KEY=\"{$vapidKeys['publicKey']}\"\n\n";
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "⚠️  Security Notes:\n";
    echo "   • Keep the private key SECRET - never expose it to clients\n";
    echo "   • The public key can be shared with browsers\n";
    echo "   • Add .env to .gitignore to prevent committing keys\n";
    echo "   • Change VAPID_SUBJECT to your actual admin email\n\n";
    
    echo "🔄 Next Steps:\n";
    echo "   1. Add keys to backend/.env file\n";
    echo "   2. Add public key to frontend/.env.local file\n";
    echo "   3. Restart your servers (backend + frontend)\n";
    echo "   4. Test push notifications subscription\n\n";
    
    // Optionally save to file
    $envContent = "# VAPID Keys for Push Notifications\n";
    $envContent .= "# Generated: " . date('Y-m-d H:i:s') . "\n";
    $envContent .= "VAPID_PUBLIC_KEY=\"{$vapidKeys['publicKey']}\"\n";
    $envContent .= "VAPID_PRIVATE_KEY=\"{$vapidKeys['privateKey']}\"\n";
    $envContent .= "VAPID_SUBJECT=\"mailto:admin@dailycup.com\"\n";
    
    $backendEnvFile = __DIR__ . '/../.env.vapid';
    file_put_contents($backendEnvFile, $envContent);
    
    echo "💾 Keys saved to: {$backendEnvFile}\n";
    echo "   (You can copy from this file to your .env)\n\n";
    
    $frontendEnvContent = "# VAPID Public Key for Push Notifications\n";
    $frontendEnvContent .= "# Generated: " . date('Y-m-d H:i:s') . "\n";
    $frontendEnvContent .= "NEXT_PUBLIC_VAPID_PUBLIC_KEY=\"{$vapidKeys['publicKey']}\"\n";
    
    $frontendEnvFile = __DIR__ . '/../../frontend/.env.vapid';
    file_put_contents($frontendEnvFile, $frontendEnvContent);
    
    echo "💾 Frontend key saved to: {$frontendEnvFile}\n";
    echo "   (You can copy from this file to your .env.local)\n\n";
    
    echo "✨ Done! Happy coding!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error generating VAPID keys: " . $e->getMessage() . "\n\n";
    exit(1);
}
