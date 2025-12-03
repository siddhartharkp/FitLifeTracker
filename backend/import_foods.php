<?php
/**
 * FitLife Tracker - Food Database Import Script
 *
 * SECURITY: This script can only be run from CLI or with a secret key
 * Usage: php import_foods.php
 * Or: https://yourdomain.com/backend/import_foods.php?key=YOUR_SECRET_KEY
 */

// Security check - CLI or secret key required
$secret_key = getenv('IMPORT_SECRET') ?: 'fitlife_import_2025_secure_key';
$provided_key = $_GET['key'] ?? '';

if (php_sapi_name() !== 'cli' && $provided_key !== $secret_key) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Access denied. Use CLI or provide valid key.']));
}

// Check if import was already completed (one-time use)
$lock_file = __DIR__ . '/import.lock';
if (file_exists($lock_file)) {
    $lock_time = file_get_contents($lock_file);
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'error' => 'Import already completed on: ' . $lock_time,
        'hint' => 'Delete import.lock file to re-run'
    ]));
}

require_once 'config.php';

// Path to CSV file (update if needed)
$csvFile = __DIR__ . '/food-database.csv';

// Check if CSV exists
if (!file_exists($csvFile)) {
    die("Error: food-database.csv not found in backend folder. Please copy it from google-apps-script folder.\n");
}

$db = getDB();

// Read and parse CSV
$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Error: Could not open food-database.csv\n");
}

// Skip header row
$header = fgetcsv($handle, 0, ',', '"', '\\');

// Prepare insert statement
$stmt = $db->prepare("
    INSERT INTO foods (id, name, category, calories, protein, carbs, fat, fiber, serving, unit)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        category = VALUES(category),
        calories = VALUES(calories),
        protein = VALUES(protein),
        carbs = VALUES(carbs),
        fat = VALUES(fat),
        fiber = VALUES(fiber),
        serving = VALUES(serving),
        unit = VALUES(unit)
");

$count = 0;
$errors = [];

while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
    if (count($row) < 10) continue; // Skip incomplete rows

    try {
        $stmt->execute([
            intval($row[0]),    // id
            $row[1],             // name
            $row[2],             // category
            floatval($row[3]),   // calories
            floatval($row[4]),   // protein
            floatval($row[5]),   // carbs
            floatval($row[6]),   // fat
            floatval($row[7]),   // fiber
            floatval($row[8]),   // serving
            $row[9]              // unit
        ]);
        $count++;
    } catch (PDOException $e) {
        $errors[] = "Row {$row[0]}: " . $e->getMessage();
    }
}

fclose($handle);

// Output results
$output = [
    'success' => true,
    'imported' => $count,
    'errors' => $errors
];

header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT);

// Create lock file to prevent re-running
file_put_contents($lock_file, date('Y-m-d H:i:s'));

echo "\n\n";
echo "=== Import Complete ===\n";
echo "Imported: $count foods\n";
if (count($errors) > 0) {
    echo "Errors: " . count($errors) . "\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
}
echo "\nLock file created. Delete import.lock to re-run this script.\n";
