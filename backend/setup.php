<?php
/**
 * FitLife Tracker - Database Setup Script
 * Run once to create tables
 *
 * SECURITY: This script should be deleted after initial setup
 * or protected by IP whitelist
 */

// Security: Multiple layers of protection
$secret_key = getenv('SETUP_KEY') ?: 'fitlife_setup_2025_' . date('Ymd');

// IP Whitelist - Only allow from these IPs (add your IP here)
$allowed_ips = [
    '127.0.0.1',
    '::1',
    // Add your IP address here for remote setup
];

$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$is_cli = php_sapi_name() === 'cli';

// Check 1: Must provide correct key
if (!$is_cli && ($_GET['key'] ?? '') !== $secret_key) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Access denied - invalid key']));
}

// Check 2: Must be from allowed IP (unless CLI)
if (!$is_cli && !in_array($client_ip, $allowed_ips)) {
    // Log the attempt
    error_log("Setup access attempt from unauthorized IP: $client_ip");
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Access denied - IP not whitelisted. Your IP: ' . $client_ip]));
}

require_once 'config.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    // Create tables
    $sql = "
    -- Foods table
    CREATE TABLE IF NOT EXISTS foods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        calories DECIMAL(10,2) DEFAULT 0,
        protein DECIMAL(10,2) DEFAULT 0,
        carbs DECIMAL(10,2) DEFAULT 0,
        fat DECIMAL(10,2) DEFAULT 0,
        fiber DECIMAL(10,2) DEFAULT 0,
        serving DECIMAL(10,2) DEFAULT 1,
        unit VARCHAR(50) DEFAULT 'serving',
        is_custom TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Meal log table
    CREATE TABLE IF NOT EXISTS meal_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        meal_type ENUM('breakfast', 'lunch', 'dinner', 'snacks', 'preworkout', 'postworkout') NOT NULL,
        food_id INT,
        food_name VARCHAR(255) NOT NULL,
        quantity DECIMAL(10,2) DEFAULT 1,
        unit VARCHAR(50) DEFAULT 'serving',
        calories DECIMAL(10,2) DEFAULT 0,
        protein DECIMAL(10,2) DEFAULT 0,
        carbs DECIMAL(10,2) DEFAULT 0,
        fat DECIMAL(10,2) DEFAULT 0,
        fiber DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (date),
        INDEX idx_meal_type (meal_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Goals table
    CREATE TABLE IF NOT EXISTS goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        calories INT DEFAULT 1450,
        protein INT DEFAULT 105,
        carbs INT DEFAULT 140,
        fat INT DEFAULT 50,
        fiber INT DEFAULT 28,
        target_weight DECIMAL(5,2) DEFAULT 65,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Weight log table
    CREATE TABLE IF NOT EXISTS weight_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL UNIQUE,
        weight DECIMAL(5,2) NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Exercise log table
    CREATE TABLE IF NOT EXISTS exercise_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        exercise_index INT NOT NULL,
        completed TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_exercise (date, exercise_index)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- PR log table
    CREATE TABLE IF NOT EXISTS pr_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exercise_name VARCHAR(100) NOT NULL UNIQUE,
        value DECIMAL(10,2) NOT NULL,
        pr_type ENUM('reps', 'weight', 'time') DEFAULT 'reps',
        achieved_date VARCHAR(20),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Day types table
    CREATE TABLE IF NOT EXISTS day_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL UNIQUE,
        type ENUM('normal', 'light', 'cheat') DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Water log table
    CREATE TABLE IF NOT EXISTS water_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL UNIQUE,
        glasses INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Meal combos table
    CREATE TABLE IF NOT EXISTS meal_combos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        emoji VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
        category ENUM('breakfast', 'lunch', 'dinner', 'snacks', 'cheat') DEFAULT 'breakfast',
        tag VARCHAR(50),
        damage_level ENUM('low', 'medium', 'high') DEFAULT NULL,
        items JSON,
        total_calories INT DEFAULT 0,
        total_protein INT DEFAULT 0,
        total_carbs INT DEFAULT 0,
        total_fat INT DEFAULT 0,
        total_fiber INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- App settings table (for edit password, etc.)
    CREATE TABLE IF NOT EXISTS app_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Workout schedules table (7-day weekly schedule)
    CREATE TABLE IF NOT EXISTS workout_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_of_week TINYINT NOT NULL UNIQUE,
        workout_type VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        emoji VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
        color VARCHAR(50) DEFAULT 'gray',
        INDEX idx_day (day_of_week)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Workout exercises table (exercises for each workout type)
    CREATE TABLE IF NOT EXISTS workout_exercises (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workout_type VARCHAR(50) NOT NULL,
        exercise_order INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        sets VARCHAR(50),
        reps VARCHAR(50),
        rest VARCHAR(50),
        notes TEXT,
        calories INT DEFAULT 0,
        is_challenge TINYINT(1) DEFAULT 0,
        pr_type ENUM('reps', 'time', 'weight') DEFAULT NULL,
        pr_unit VARCHAR(20) DEFAULT NULL,
        is_rest TINYINT(1) DEFAULT 0,
        is_optional TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_workout_type (workout_type),
        INDEX idx_order (workout_type, exercise_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Exercise library (master list of all exercises)
    CREATE TABLE IF NOT EXISTS exercise_library (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        type VARCHAR(50),
        primary_muscles VARCHAR(255),
        secondary_muscles VARCHAR(255),
        equipment VARCHAR(255),
        difficulty VARCHAR(50),
        calories_per_30min INT DEFAULT 0,
        sets_recommended VARCHAR(50),
        reps_recommended VARCHAR(50),
        rest_seconds INT DEFAULT 60,
        instructions TEXT,
        tips TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_name (name),
        INDEX idx_difficulty (difficulty)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $created = 0;

    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, 'CREATE TABLE') !== false) {
            $db->exec($statement);
            $created++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Created $created tables",
        'tables' => ['foods', 'meal_log', 'goals', 'weight_log', 'exercise_log', 'pr_log', 'day_types', 'water_log', 'meal_combos', 'app_settings', 'workout_schedules', 'workout_exercises', 'exercise_library']
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Setup failed: ' . $e->getMessage()
    ]);
}
