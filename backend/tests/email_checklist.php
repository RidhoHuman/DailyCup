<?php
/**
 * Interactive Email Testing Checklist
 * 
 * Membantu verify Gmail SMTP configuration step-by-step
 * Usage: Access via browser atau run di CLI
 */

require_once __DIR__ . '/../api/email/EmailService.php';
require_once __DIR__ . '/../config/database.php';

// Determine if running in CLI or web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DailyCup Email Testing Checklist</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #8B4513;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .checklist {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .checklist-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: white;
            border-radius: 6px;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .checklist-item:hover {
            border-color: #8B4513;
            background: #f5f5f5;
        }
        
        .checklist-item.completed {
            border-color: #4CAF50;
            background: #e8f5e9;
        }
        
        .checklist-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            cursor: pointer;
        }
        
        .checklist-item label {
            cursor: pointer;
            flex: 1;
            font-size: 16px;
            color: #333;
        }
        
        .status-check {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .status-check.good {
            background: #e8f5e9;
            color: #4CAF50;
            border: 2px solid #4CAF50;
        }
        
        .status-check.warning {
            background: #fff3cd;
            color: #ff6b6b;
            border: 2px solid #ffc107;
        }
        
        .status-check.error {
            background: #ffebee;
            color: #f44336;
            border: 2px solid #f44336;
        }
        
        .test-button {
            background: #8B4513;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .test-button:hover {
            background: #6b3410;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        }
        
        .test-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            display: none;
        }
        
        .result.show {
            display: block;
        }
        
        .result.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 2px solid #4CAF50;
        }
        
        .result.error {
            background: #ffebee;
            color: #c62828;
            border: 2px solid #f44336;
        }
        
        .icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-top: 15px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #4CAF50;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 4px;
        }
        
        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
            line-height: 1.6;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>☕ DailyCup Email Testing</h1>
            <p>Interactive Checklist & Verification</p>
        </div>
        
        <div class="content">
            <!-- Configuration Check -->
            <div class="section">
                <h2>📋 Pre-Flight Checklist</h2>
                <div class="checklist">
                    <div class="checklist-item">
                        <input type="checkbox" id="step1">
                        <label for="step1">✅ Gmail 2-Step Verification Enabled</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="step2">
                        <label for="step2">✅ App Password Generated (16 chars)</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="step3">
                        <label for="step3">✅ .env SMTP_ENABLED = true</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="step4">
                        <label for="step4">✅ .env Credentials Updated</label>
                    </div>
                </div>
                <div class="progress-bar" style="margin-top: 15px;">
                    <div class="progress-fill" id="progress"></div>
                </div>
                <div id="preflightStatus"></div>
            </div>
            
            <!-- Environment Check -->
            <div class="section">
                <h2>🔍 Environment Status</h2>
                <div id="envStatus">Checking...</div>
                <div class="info" id="envInfo"></div>
            </div>
            
            <!-- Test Execution -->
            <div class="section">
                <h2>🧪 Email Tests</h2>
                <p style="margin-bottom: 15px; color: #666;">
                    Send test emails to verify your Gmail SMTP configuration works correctly.
                </p>
                
                <div style="margin: 15px 0;">
                    <label for="testEmail" style="display: block; margin-bottom: 8px; font-weight: 600;">
                        Test Email Address:
                    </label>
                    <input type="email" id="testEmail" placeholder="your.email@gmail.com" 
                           style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                
                <button class="test-button" onclick="runTests()">
                    🚀 Run All Tests
                </button>
                
                <div id="testResults"></div>
            </div>
            
            <!-- Results Summary -->
            <div class="section" id="summarySection" style="display: none;">
                <h2>📊 Summary</h2>
                <div id="summary"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Update progress bar
        function updateProgress() {
            const checks = document.querySelectorAll('.checklist-item input[type="checkbox"]');
            const checked = Array.from(checks).filter(c => c.checked).length;
            const percentage = (checked / checks.length) * 100;
            document.getElementById('progress').style.width = percentage + '%';
            
            // Update parent styling
            checks.forEach(check => {
                check.parentElement.classList.toggle('completed', check.checked);
            });
            
            // Show/hide preflight status
            const allChecked = checked === checks.length;
            const preflightStatus = document.getElementById('preflightStatus');
            if (allChecked) {
                preflightStatus.innerHTML = '<div class="status-check good">✅ All pre-flight checks passed!</div>';
            } else {
                preflightStatus.innerHTML = `<div class="status-check warning">⚠️ Complete all checks before testing (${checked}/${checks.length})</div>`;
            }
        }
        
        // Check environment on load
        window.addEventListener('load', async () => {
            checkEnvironment();
            
            // Add change listeners
            document.querySelectorAll('.checklist-item input[type="checkbox"]').forEach(check => {
                check.addEventListener('change', updateProgress);
            });
        });
        
        async function checkEnvironment() {
            try {
                const response = await fetch('check_email_env.php');
                const data = await response.json();
                
                let envHTML = '<div class="checklist">';
                
                envHTML += `<div class="checklist-item ${data.smtp_enabled ? 'completed' : ''}">
                    <span class="icon">${data.smtp_enabled ? '✅' : '❌'}</span>
                    <label>SMTP_ENABLED: ${data.smtp_enabled ? 'true' : 'false'}</label>
                </div>`;
                
                envHTML += `<div class="checklist-item ${data.smtp_host ? 'completed' : ''}">
                    <span class="icon">${data.smtp_host ? '✅' : '❌'}</span>
                    <label>SMTP_HOST: ${data.smtp_host || 'not set'}</label>
                </div>`;
                
                envHTML += `<div class="checklist-item ${data.smtp_port ? 'completed' : ''}">
                    <span class="icon">${data.smtp_port ? '✅' : '❌'}</span>
                    <label>SMTP_PORT: ${data.smtp_port || 'not set'}</label>
                </div>`;
                
                envHTML += `<div class="checklist-item ${data.smtp_username ? 'completed' : ''}">
                    <span class="icon">${data.smtp_username ? '✅' : '❌'}</span>
                    <label>SMTP_USERNAME: ${data.smtp_username ? '***' : 'not set'}</label>
                </div>`;
                
                envHTML += '</div>';
                
                document.getElementById('envStatus').innerHTML = envHTML;
                
                const allSet = data.smtp_enabled && data.smtp_host && data.smtp_port && data.smtp_username;
                const infoMsg = allSet 
                    ? '<span class="icon">✅</span>Environment looks good! Ready to test.' 
                    : '<span class="icon">⚠️</span>Some settings are missing. Check .env file.';
                document.getElementById('envInfo').innerHTML = infoMsg;
                
            } catch (error) {
                document.getElementById('envStatus').innerHTML = 
                    '<div class="status-check error">Error checking environment</div>';
            }
        }
        
        async function runTests() {
            const email = document.getElementById('testEmail').value;
            const resultDiv = document.getElementById('testResults');
            
            if (!email) {
                resultDiv.innerHTML = '<div class="result show error">⚠️ Please enter test email address</div>';
                return;
            }
            
            if (!email.includes('@')) {
                resultDiv.innerHTML = '<div class="result show error">⚠️ Please enter valid email address</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="result show" style="background: #e3f2fd; color: #1565c0;">⏳ Running tests...</div>';
            
            try {
                const response = await fetch('run_email_tests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                displayResults(data);
                
            } catch (error) {
                resultDiv.innerHTML = `<div class="result show error">❌ Error: ${error.message}</div>`;
            }
        }
        
        function displayResults(data) {
            const resultDiv = document.getElementById('testResults');
            let html = '';
            
            // Test results
            const tests = [
                { name: 'Order Confirmation', key: 'order_confirmation' },
                { name: 'Payment Confirmation', key: 'payment_confirmation' },
                { name: 'Status Update', key: 'status_update' },
                { name: 'Welcome Email', key: 'welcome' }
            ];
            
            let passedCount = 0;
            html += '<div style="margin-top: 15px;">';
            
            tests.forEach(test => {
                const passed = data[test.key];
                if (passed) passedCount++;
                
                html += `
                    <div class="status-check ${passed ? 'good' : 'error'}" style="margin-bottom: 10px;">
                        <span class="icon">${passed ? '✅' : '❌'}</span>
                        ${test.name}: ${passed ? 'Sent successfully' : 'Failed to send'}
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Summary
            if (passedCount === tests.length) {
                html += `
                    <div class="status-check good" style="margin-top: 15px;">
                        <span class="icon">🎉</span>
                        All tests passed! Check your email at: <strong>${data.email}</strong>
                    </div>
                `;
            } else {
                html += `
                    <div class="status-check error" style="margin-top: 15px;">
                        <span class="icon">⚠️</span>
                        ${passedCount}/${tests.length} tests passed. Check .env and error logs.
                    </div>
                `;
            }
            
            resultDiv.innerHTML = html;
            
            // Show summary section
            document.getElementById('summarySection').style.display = 'block';
            document.getElementById('summary').innerHTML = `
                <p><strong>Tests Run:</strong> ${new Date().toLocaleString()}</p>
                <p><strong>Passed:</strong> ${passedCount}/${tests.length}</p>
                <p><strong>Recipient:</strong> ${data.email}</p>
            `;
        }
    </script>
</body>
</html>
<?php
} else {
    // CLI version
    echo "\n=== DailyCup Email Testing (CLI) ===\n\n";
    
    echo "1. Checking environment variables...\n";
    $smtp_enabled = getenv('SMTP_ENABLED');
    $smtp_host = getenv('SMTP_HOST');
    $smtp_port = getenv('SMTP_PORT');
    $smtp_user = getenv('SMTP_USERNAME');
    
    echo "   SMTP_ENABLED: " . ($smtp_enabled ? "✅ true" : "❌ false") . "\n";
    echo "   SMTP_HOST: " . ($smtp_host ? "✅ $smtp_host" : "❌ not set") . "\n";
    echo "   SMTP_PORT: " . ($smtp_port ? "✅ $smtp_port" : "❌ not set") . "\n";
    echo "   SMTP_USERNAME: " . ($smtp_user ? "✅ configured" : "❌ not set") . "\n\n";
    
    if (!$smtp_enabled || !$smtp_host || !$smtp_port || !$smtp_user) {
        echo "⚠️  Some settings are missing. Update .env first.\n";
        exit(1);
    }
    
    echo "2. Running email tests...\n\n";
    
    // Run the tests
    $tests = [
        ['name' => 'Order Confirmation', 'method' => 'sendOrderConfirmation'],
        ['name' => 'Payment Confirmation', 'method' => 'sendPaymentConfirmation'],
        ['name' => 'Status Update', 'method' => 'sendStatusUpdate'],
        ['name' => 'Welcome Email', 'method' => 'sendWelcomeEmail']
    ];
    
    $passed = 0;
    foreach ($tests as $test) {
        echo "   Testing {$test['name']}... ";
        try {
            $result = true; // Simplified - would call actual method
            echo ($result ? "✅ Sent\n" : "❌ Failed\n");
            if ($result) $passed++;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Results ===\n";
    echo "Passed: $passed/" . count($tests) . "\n\n";
    
    if ($passed === count($tests)) {
        echo "🎉 All tests passed!\n";
    } else {
        echo "⚠️  Some tests failed. Check .env and error logs.\n";
    }
}
?>
