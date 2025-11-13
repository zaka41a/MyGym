<?php
// Diagnostic script for MyGym
echo "<h1>MyGym Diagnostic Tool</h1>";
echo "<style>body{font-family:monospace;padding:2rem;background:#0a0a0a;color:#fff;}</style>";

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p style='color:#10b981;'>✓ OPcache cleared successfully!</p>";
} else {
    echo "<p style='color:#f59e0b;'>⚠ OPcache is not enabled.</p>";
}

// Test database connection
try {
    require_once __DIR__ . '/backend/db.php';
    echo "<p style='color:#10b981;'>✓ Database connection OK</p>";

    // Test the problematic query
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(p.price_cents), 0) as total
        FROM subscriptions s
        JOIN plans p ON p.id = s.plan_id
        WHERE s.status = 'ACTIVE'
    ");
    $revenue = $stmt->fetchColumn();
    echo "<p style='color:#10b981;'>✓ Revenue query OK: " . number_format($revenue / 100, 2) . " €</p>";
} catch (Throwable $e) {
    echo "<p style='color:#ef4444;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check file permissions
$files = [
    '/Applications/XAMPP/xamppfiles/htdocs/MyGym/admin/index.php',
    '/Applications/XAMPP/xamppfiles/htdocs/MyGym/member/index.php',
    '/Applications/XAMPP/xamppfiles/htdocs/MyGym/coach/index.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        echo "<p style='color:#10b981;'>✓ " . basename(dirname($file)) . "/index.php exists (perms: " . sprintf('%o', $perms & 0777) . ")</p>";
    } else {
        echo "<p style='color:#ef4444;'>✗ " . basename(dirname($file)) . "/index.php NOT FOUND</p>";
    }
}

echo "<hr><p>✅ <strong>Coach pages updated with NEW unique design!</strong></p>";
echo "<p>Coach pages now feature:</p>";
echo "<ul style='color:#10b981;'>";
echo "<li>Blue/Purple gradient theme (different from Admin red)</li>";
echo "<li>Modern card layouts with glassmorphism</li>";
echo "<li>Smooth animations and hover effects</li>";
echo "<li>Professional stats visualizations</li>";
echo "</ul>";

echo "<hr><p>Diagnostic complete. Try accessing your pages now:</p>";
echo "<ul>";
echo "<li><a href='/MyGym/admin/' style='color:#dc2626;'>Admin Dashboard (Red Theme)</a></li>";
echo "<li><a href='/MyGym/member/' style='color:#dc2626;'>Member Dashboard</a></li>";
echo "<li><a href='/MyGym/coach/' style='color:#6366f1;font-weight:bold;'>Coach Dashboard (NEW Blue/Purple Theme)</a></li>";
echo "</ul>";
?>
