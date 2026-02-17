#!/usr/bin/env php#!/usr/bin/env php
















































































































/* The code you provided seems to be a PHP script that tests an email queue system. Let's break down
the code snippet you shared: */
?>echo "\n=== Test Complete ===\n";echo "  Failed: {$stats['failed']}\n";echo "  Pending: {$stats['pending']}\n";echo "  Total: {$stats['total']}\n";$stats = EmailQueue::getStats();echo "Test 5: Final queue stats\n";// Test 5: Final statsecho "\n";echo "Failed: $failed\n";echo "\nProcessed: $processed\n";}    }        echo "  ✗ Error: {$e->getMessage()}\n";        $failed++;        }            EmailQueue::markFailed($file);        if (isset($file)) {    } catch (Exception $e) {                }            echo "  ✗ Failed: {$data['to']}\n";            $failed++;            EmailQueue::markFailed($file);        } else {            echo "  ✓ Sent to: {$data['to']}\n";            $processed++;            EmailQueue::markSent($file);        if ($success) {                );            $data['htmlBody']            $data['subject'],            $data['to'],        $success = EmailService::send(                $file = $item['file'];        $data = $item['data'];    try {foreach ($pending as $item) {$failed = 0;$processed = 0;$pending = EmailQueue::getPending(10);EmailService::init();EmailService::setUseQueue(false); // Send directly nowecho "Test 4: Processing queue...\n";// Test 4: Process queueecho "\n";}    echo "  - $file → $to\n";    $to = $item['data']['to'];    $file = basename($item['file']);foreach ($pending as $item) {$pending = EmailQueue::getPending(10);echo "Test 3: Queue files\n";// Test 3: List queue filesecho "\n";echo "  Failed: {$stats['failed']}\n";echo "  Pending: {$stats['pending']}\n";echo "  Total files: {$stats['total']}\n";$stats = EmailQueue::getStats();echo "Test 2: Queue stats\n";// Test 2: Check queue statsecho "\n";}    echo "  ✓ Queued: {$test['email']}\n";    $result = EmailService::send($test['email'], $test['subject'], $test['body']);foreach ($testEmails as $test) {];    ]        'body' => '<h1>Test Email 2</h1><p>Email ini dikirim dari queue system.</p>'        'subject' => 'Test Email 2 dari Queue System',        'email' => 'ridhohuman11@gmail.com',    [    ],        'body' => '<h1>Test Email 1</h1><p>This is a test email from queue system.</p>'        'subject' => 'Test Email 1',        'email' => 'test@example.com',    [$testEmails = [EmailService::setUseQueue(true);EmailService::init();echo "Test 1: Queuing emails...\n";// Test 1: Add emails to queueecho "=== Email Queue System Test ===\n\n";require_once __DIR__ . '/email/EmailQueue.php';require_once __DIR__ . '/email/EmailService.php';require_once __DIR__ . '/config.php'; */ * Run: php backend/api/test_queue_system.php *  * Test Email Queue System/**<?php<?php
require_once __DIR__ . '/cors.php';
/**
 * Test Email Queue System
 * 
 * Run: php backend/api/test_queue_system.php
 */

/* `require_once` is a PHP function that includes and evaluates a specified file during the execution
of a script. It ensures that the file is included only once, preventing multiple inclusions of the
same file. If the file cannot be included (e.g., file not found), it will produce a warning and
continue the script execution. */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailService.php';
require_once __DIR__ . '/email/EmailQueue.php';

echo "=== Email Queue System Test ===\n\n";

// Test 1: Add emails to queue
echo "Test 1: Queuing emails...\n";
EmailService::init();
EmailService::setUseQueue(true);

$testEmails = [
    [
        'email' => 'test@example.com',
        'subject' => 'Test Email 1',
        'body' => '<h1>Test Email 1</h1><p>This is a test email from queue system.</p>'
    ],
    [
        'email' => 'ridhohuman11@gmail.com',
        'subject' => 'Test Email 2 dari Queue System',
        'body' => '<h1>Test Email 2</h1><p>Email ini dikirim dari queue system.</p>'
    ]
];

foreach ($testEmails as $test) {
    $result = EmailService::send($test['email'], $test['subject'], $test['body']);
    echo "  ✓ Queued: {$test['email']}\n";
}

echo "\n";

// Test 2: Check queue stats
echo "Test 2: Queue stats\n";
$stats = EmailQueue::getStats();
echo "  Total files: {$stats['total']}\n";
echo "  Pending: {$stats['pending']}\n";
echo "  Failed: {$stats['failed']}\n";

echo "\n";

// Test 3: List queue files
echo "Test 3: Queue files\n";
$pending = EmailQueue::getPending(10);
foreach ($pending as $item) {
    $file = basename($item['file']);
    $to = $item['data']['to'];
    echo "  - $file → $to\n";
}

echo "\n";

// Test 4: Process queue
echo "Test 4: Processing queue...\n";
EmailService::setUseQueue(false); // Send directly now
EmailService::init();

$pending = EmailQueue::getPending(10);
$processed = 0;
$failed = 0;

foreach ($pending as $item) {
    try {
        $data = $item['data'];
        $file = $item['file'];
        
        $success = EmailService::send(
            $data['to'],
            $data['subject'],
            $data['htmlBody']
        );
        
        if ($success) {
            EmailQueue::markSent($file);
            $processed++;
            echo "  ✓ Sent to: {$data['to']}\n";
        } else {
            EmailQueue::markFailed($file);
            $failed++;
            echo "  ✗ Failed: {$data['to']}\n";
        }
        
    } catch (Exception $e) {
        if (isset($file)) {
            EmailQueue::markFailed($file);
        }
        $failed++;
        echo "  ✗ Error: {$e->getMessage()}\n";
    }
}

echo "\nProcessed: $processed\n";
echo "Failed: $failed\n";

echo "\n";

// Test 5: Final stats
echo "Test 5: Final queue stats\n";
$stats = EmailQueue::getStats();
echo "  Total: {$stats['total']}\n";
echo "  Pending: {$stats['pending']}\n";
echo "  Failed: {$stats['failed']}\n";

echo "\n=== Test Complete ===\n";
?>
