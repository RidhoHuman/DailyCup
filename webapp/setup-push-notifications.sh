#!/bin/bash

# Quick Setup Script for Push Notifications
# Run this after implementing Phase 12.6

echo "🔔 Setting up Push Notifications..."

# Step 1: Install dependencies
echo "📦 Installing dependencies..."
cd frontend
npm install web-push --save-dev

cd ../backend
composer require minishlink/web-push

# Step 2: Generate VAPID keys
echo "🔑 Generating VAPID keys..."
cd ../frontend
node scripts/generate-vapid-keys.js > vapid-keys.txt

echo "✅ VAPID keys saved to vapid-keys.txt"
echo ""
echo "⚠️  NEXT STEPS:"
echo "1. Copy VAPID keys from vapid-keys.txt to:"
echo "   - frontend/.env.local (NEXT_PUBLIC_VAPID_PUBLIC_KEY)"
echo "   - backend/.env (VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT)"
echo ""
echo "2. Run database migration:"
echo "   mysql -u root -p dailycup < backend/sql/push_notifications.sql"
echo ""
echo "3. Restart development server"
echo ""
echo "4. Test at: http://localhost:3000/settings/notifications"
echo ""
echo "📚 See docs/PUSH_NOTIFICATIONS_SETUP.md for full guide"
