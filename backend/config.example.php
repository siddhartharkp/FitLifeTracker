<?php
/**
 * FitLife Tracker - Database Configuration
 *
 * SECURITY: Move credentials to environment variables in production!
 * See .env.example for required variables
 */

// Database credentials - UPDATE THESE or use environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'fitlife_tracker');
define('DB_USER', getenv('DB_USER') ?: 'your_db_user');
define('DB_PASS', getenv('DB_PASS') ?: 'your_db_password');

// CORS settings - Add your domains here
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:3000',
    'http://localhost:5500',
    'http://localhost:8000',
    'https://yourdomain.com',
];

// Get request origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Set CORS headers - Only for allowed origins
$is_development = getenv('ENV') === 'development' || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} elseif (empty($origin) && $is_development) {
    // Development only: Allow requests without origin (same-origin, Postman, etc.)
    header("Access-Control-Allow-Origin: *");
} elseif (!empty($origin)) {
    // Log suspicious CORS attempts from unknown origins
    logError('Blocked CORS request from unauthorized origin: ' . $origin);
}
// Note: If origin is blocked, browser will reject the response due to missing CORS headers

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Handle preflight OPTIONS request (skip in CLI mode)
if (php_sapi_name() !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==================== RATE LIMITING ====================

function checkRateLimit($identifier, $max_requests = 100, $window_seconds = 60) {
    $cache_dir = __DIR__ . '/cache';
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
        // Protect cache directory
        file_put_contents($cache_dir . '/.htaccess', "Order deny,allow\nDeny from all");
    }

    $cache_file = $cache_dir . '/rate_' . md5($identifier) . '.json';
    $now = time();

    // Load existing rate limit data
    $data = ['requests' => []];
    if (file_exists($cache_file)) {
        $content = @file_get_contents($cache_file);
        if ($content) {
            $data = json_decode($content, true) ?? ['requests' => []];
        }
    }

    // Remove expired timestamps
    $data['requests'] = array_values(array_filter($data['requests'] ?? [], function($ts) use ($now, $window_seconds) {
        return $ts > ($now - $window_seconds);
    }));

    // Check if limit exceeded
    if (count($data['requests']) >= $max_requests) {
        http_response_code(429);
        header('Retry-After: ' . $window_seconds);
        echo json_encode([
            'success' => false,
            'error' => 'Rate limit exceeded. Please slow down.',
            'retry_after' => $window_seconds
        ]);
        exit();
    }

    // Add current request
    $data['requests'][] = $now;
    @file_put_contents($cache_file, json_encode($data));
}

// Apply rate limiting - 100 requests per minute per IP (skip in CLI mode)
if (php_sapi_name() !== 'cli') {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    checkRateLimit($client_ip, 100, 60);
}

// Database connection with error handling
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10
            ]);
        } catch (PDOException $e) {
            logError('Database connection failed: ' . $e->getMessage());
            http_response_code(503);
            echo json_encode(['success' => false, 'error' => 'Service temporarily unavailable']);
            exit();
        }
    }

    return $pdo;
}

// Helper function to send JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    if (!isset($data['success'])) {
        $data['success'] = ($statusCode >= 200 && $statusCode < 300);
    }
    echo json_encode($data);
    exit();
}

// Error logging function
function logError($message, $context = []) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $contextStr = !empty($context) ? json_encode($context) : '';

    $logEntry = "[$timestamp] [$ip] $message $contextStr\n";
    @error_log($logEntry, 3, $logFile);
}

// Input validation helpers
function validateDate($date) {
    if (!$date) return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateMealType($type) {
    $validTypes = ['breakfast', 'lunch', 'dinner', 'snacks', 'postworkout'];
    return in_array(strtolower($type), $validTypes);
}

function validateNumeric($value, $min = 0, $max = 100000) {
    if (!is_numeric($value)) return false;
    $num = floatval($value);
    if (!is_finite($num)) return false; // Reject INF/NAN
    return $num >= $min && $num <= $max;
}

function sanitizeString($str, $maxLength = 255) {
    if (!is_string($str)) return '';
    $str = trim($str);
    $str = substr($str, 0, $maxLength);
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Validate meal data
function validateMealData($data) {
    $errors = [];

    // Validate date
    if (!validateDate($data['date'] ?? '')) {
        $errors[] = 'Invalid date format (expected YYYY-MM-DD)';
    }

    // Validate meal type
    if (!validateMealType($data['mealType'] ?? '')) {
        $errors[] = 'Invalid meal type';
    }

    // Validate numeric values
    if (!validateNumeric($data['calories'] ?? 0, 0, 10000)) {
        $errors[] = 'Invalid calories (must be 0-10000)';
    }
    if (!validateNumeric($data['protein'] ?? 0, 0, 1000)) {
        $errors[] = 'Invalid protein (must be 0-1000)';
    }
    if (!validateNumeric($data['carbs'] ?? 0, 0, 1000)) {
        $errors[] = 'Invalid carbs (must be 0-1000)';
    }
    if (!validateNumeric($data['fat'] ?? 0, 0, 1000)) {
        $errors[] = 'Invalid fat (must be 0-1000)';
    }
    if (!validateNumeric($data['fiber'] ?? 0, 0, 500)) {
        $errors[] = 'Invalid fiber (must be 0-500)';
    }
    if (!validateNumeric($data['quantity'] ?? 1, 0.1, 100)) {
        $errors[] = 'Invalid quantity (must be 0.1-100)';
    }

    // Validate food name
    $foodName = $data['foodName'] ?? '';
    if (empty($foodName) || strlen($foodName) > 255) {
        $errors[] = 'Invalid food name (1-255 characters)';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
