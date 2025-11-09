<?php
/**
 * MyGym React Frontend Entry Point
 * Serves the built React application through XAMPP
 */

// Check if we're in development mode (React dev server running)
$isDev = isset($_SERVER['HTTP_X_VITE_DEV']) || (isset($_GET['dev']) && $_GET['dev'] === '1');

if ($isDev) {
  // Redirect to Vite dev server
  header('Location: http://localhost:5173' . $_SERVER['REQUEST_URI']);
  exit;
}

// Serve built React application
$distDir = __DIR__ . '/dist';
$indexFile = $distDir . '/index.html';

if (!file_exists($indexFile)) {
  http_response_code(503);
  echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym - Build Required</title>
  <style>
    body {
      font-family: system-ui, -apple-system, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      background: #0a0a0a;
      color: #f1f5f9;
    }
    .container {
      text-align: center;
      max-width: 600px;
      padding: 2rem;
      background: rgba(17,17,17,.8);
      border-radius: 24px;
      border: 1px solid rgba(220,38,38,.3);
    }
    h1 { color: #dc2626; margin-bottom: 1rem; }
    code {
      background: rgba(220,38,38,.1);
      padding: 0.5rem 1rem;
      border-radius: 8px;
      display: inline-block;
      margin: 1rem 0;
      color: #fca5a5;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>⚠️ Build Required</h1>
    <p>The React application needs to be built before it can be served.</p>
    <p>Run the following command in the terminal:</p>
    <code>cd frontend && npm run build</code>
    <p style="margin-top: 2rem; font-size: 0.9rem; color: #94a3b8;">
      Or use the development server: <a href="http://localhost:5173" style="color: #dc2626;">http://localhost:5173</a>
    </p>
  </div>
</body>
</html>';
  exit;
}

// Serve the index.html file
$content = file_get_contents($indexFile);

// Set proper headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $content;
