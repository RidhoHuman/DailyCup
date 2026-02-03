#!/bin/bash
# Test CORS Configuration
# This script tests CORS headers for DailyCup API endpoints

echo "========================================="
echo "DailyCup CORS Testing Script"
echo "========================================="
echo ""

# Configuration
NGROK_URL="${1:-https://decagonal-subpolygonally-brecken.ngrok-free.dev}"
API_PATH="/DailyCup/webapp/backend/api"
ORIGIN="https://dailycup.vercel.app"

echo "Testing CORS for:"
echo "  Backend: $NGROK_URL$API_PATH"
echo "  Origin: $ORIGIN"
echo ""

# Function to test endpoint
test_endpoint() {
    local endpoint=$1
    local method=${2:-POST}
    
    echo "----------------------------------------"
    echo "Testing: $endpoint"
    echo "Method: $method"
    echo ""
    
    # Test OPTIONS (preflight)
    echo "1. Preflight (OPTIONS) Request:"
    curl -i -X OPTIONS \
        -H "Origin: $ORIGIN" \
        -H "Access-Control-Request-Method: $method" \
        -H "Access-Control-Request-Headers: Content-Type, Authorization" \
        "$NGROK_URL$API_PATH/$endpoint" 2>&1 | head -20
    
    echo ""
    echo "2. Actual Request:"
    
    # Test actual request
    if [ "$endpoint" = "login.php" ]; then
        curl -i -X POST \
            -H "Origin: $ORIGIN" \
            -H "Content-Type: application/json" \
            -d '{"email":"test@example.com","password":"test123"}' \
            "$NGROK_URL$API_PATH/$endpoint" 2>&1 | head -30
    elif [ "$endpoint" = "products.php" ]; then
        curl -i -X GET \
            -H "Origin: $ORIGIN" \
            "$NGROK_URL$API_PATH/$endpoint" 2>&1 | head -30
    else
        curl -i -X $method \
            -H "Origin: $ORIGIN" \
            "$NGROK_URL$API_PATH/$endpoint" 2>&1 | head -30
    fi
    
    echo ""
    echo ""
}

# Test endpoints
echo "========================================"
echo "Starting CORS Tests..."
echo "========================================"
echo ""

test_endpoint "login.php" "POST"
test_endpoint "products.php" "GET"
test_endpoint "register.php" "POST"

echo "========================================"
echo "CORS Testing Complete"
echo "========================================"
echo ""
echo "Expected Headers:"
echo "  ✓ Access-Control-Allow-Origin: $ORIGIN or *"
echo "  ✓ Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH"
echo "  ✓ Access-Control-Allow-Headers: Content-Type, Authorization, ..."
echo "  ✓ HTTP Status: 204 for OPTIONS, 200 for actual requests"
echo ""
echo "If you see these headers, CORS is configured correctly!"
echo ""
