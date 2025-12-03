<?php
/**
 * FitLife Tracker - PHP API
 * Fast MySQL backend for meal tracking
 */

require_once 'config.php';

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// For POST requests, get JSON body
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
    $action = $input['action'] ?? $action;
}

// Route to appropriate handler
switch ($action) {
    // ==================== HEALTH CHECK ====================
    case 'health':
        healthCheck();
        break;

    // ==================== MEALS ====================
    case 'logMeal':
        logMeal($input);
        break;

    case 'getDaily':
        $date = $_GET['date'] ?? $input['date'] ?? date('Y-m-d');
        getDaily($date);
        break;

    case 'deleteMeal':
    case 'deleteMealItem': // Legacy frontend support
        deleteMeal($input['id'] ?? 0);
        break;

    case 'clearDay':
        clearDay($input['date'] ?? date('Y-m-d'));
        break;

    // ==================== FOODS ====================
    case 'getAllFoods':
        getAllFoods();
        break;

    case 'searchFoods':
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? 'all';
        searchFoods($search, $category);
        break;

    case 'addCustomFood':
        addCustomFood($input['food'] ?? $input);
        break;

    // ==================== GOALS ====================
    case 'getGoals':
        getGoals();
        break;

    case 'updateGoals':
        updateGoals($input['goals'] ?? $input);
        break;

    // ==================== WEIGHT ====================
    case 'logWeight':
        logWeight($input);
        break;

    case 'getWeightHistory':
        getWeightHistory();
        break;

    // ==================== EXERCISE ====================
    case 'logExercise':
        logExercise($input);
        break;

    case 'getExerciseLog':
        $date = $_GET['date'] ?? $input['date'] ?? date('Y-m-d');
        getExerciseLog($date);
        break;

    // ==================== PR ====================
    case 'logPR':
        logPR($input);
        break;

    case 'getPRLog':
        getPRLog();
        break;

    // ==================== DAY TYPES ====================
    case 'setDayType':
        setDayType($input);
        break;

    case 'getDayTypes':
        getDayTypes();
        break;

    // ==================== WATER ====================
    case 'logWater':
        logWater($input);
        break;

    case 'getWater':
        $date = $_GET['date'] ?? $input['date'] ?? date('Y-m-d');
        getWater($date);
        break;

    // ==================== COMBOS ====================
    case 'saveMealCombo':
        saveMealCombo($input['combo'] ?? $input);
        break;

    case 'getMealCombos':
        getMealCombos();
        break;

    default:
        logError('Unknown action attempted', ['action' => $action]);
        jsonResponse(['success' => false, 'error' => 'Invalid request'], 400);
}

// ==================== HEALTH CHECK ====================

function healthCheck() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT 1");
        jsonResponse([
            'success' => true,
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => date('c')
        ]);
    } catch (Exception $e) {
        logError('Health check failed: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'status' => 'unhealthy',
            'database' => 'disconnected'
        ], 503);
    }
}

// ==================== MEAL FUNCTIONS ====================

function logMeal($data) {
    // Validate input
    $validation = validateMealData($data);
    if (!$validation['valid']) {
        jsonResponse([
            'success' => false,
            'error' => 'Validation failed',
            'details' => $validation['errors']
        ], 400);
        return;
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO meal_log (date, meal_type, food_id, food_name, quantity, unit, calories, protein, carbs, fat, fiber)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['date'],
            strtolower($data['mealType']),
            intval($data['foodId'] ?? 0) ?: null,
            sanitizeString($data['foodName']),
            floatval($data['quantity']),
            sanitizeString($data['unit'] ?? 'serving', 50),
            floatval($data['calories']),
            floatval($data['protein']),
            floatval($data['carbs']),
            floatval($data['fat']),
            floatval($data['fiber'])
        ]);

        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        logError('logMeal failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to save meal'], 500);
    }
}

function getDaily($date) {
    // Validate date format
    if (!validateDate($date)) {
        jsonResponse(['success' => false, 'error' => 'Invalid date format'], 400);
        return;
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT id, meal_type, food_id, food_name, quantity, unit, calories, protein, carbs, fat, fiber
            FROM meal_log
            WHERE date = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll();

        $meals = [
            'breakfast' => [],
            'lunch' => [],
            'dinner' => [],
            'snacks' => [],
            'postworkout' => []
        ];

        $totals = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0, 'fiber' => 0];

        foreach ($rows as $row) {
            $mealType = $row['meal_type'];
            $item = [
                'id' => intval($row['id']),
                'foodId' => $row['food_id'] ? intval($row['food_id']) : null,
                'foodName' => $row['food_name'],
                'quantity' => floatval($row['quantity']),
                'unit' => $row['unit'],
                'calories' => floatval($row['calories']),
                'protein' => floatval($row['protein']),
                'carbs' => floatval($row['carbs']),
                'fat' => floatval($row['fat']),
                'fiber' => floatval($row['fiber'])
            ];

            if (isset($meals[$mealType])) {
                $meals[$mealType][] = $item;
            }

            $totals['calories'] += $item['calories'];
            $totals['protein'] += $item['protein'];
            $totals['carbs'] += $item['carbs'];
            $totals['fat'] += $item['fat'];
            $totals['fiber'] += $item['fiber'];
        }

        jsonResponse(['success' => true, 'meals' => $meals, 'totals' => $totals]);
    } catch (PDOException $e) {
        logError('getDaily failed: ' . $e->getMessage(), ['date' => $date]);
        jsonResponse(['success' => false, 'error' => 'Failed to load meals'], 500);
    }
}

function deleteMeal($id) {
    // Validate ID
    if (!$id || !is_numeric($id) || $id <= 0) {
        jsonResponse(['success' => false, 'error' => 'Invalid meal ID'], 400);
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM meal_log WHERE id = ?");
        $stmt->execute([intval($id)]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'error' => 'Meal not found'], 404);
            return;
        }

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('deleteMeal failed: ' . $e->getMessage(), ['id' => $id]);
        jsonResponse(['success' => false, 'error' => 'Failed to delete meal'], 500);
    }
}

function clearDay($date) {
    // Validate date format
    if (!validateDate($date)) {
        jsonResponse(['success' => false, 'error' => 'Invalid date format'], 400);
        return;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        $stmt = $db->prepare("DELETE FROM meal_log WHERE date = ?");
        $stmt->execute([$date]);

        $db->commit();
        jsonResponse(['success' => true, 'deleted' => $stmt->rowCount()]);
    } catch (PDOException $e) {
        $db->rollBack();
        logError('clearDay failed: ' . $e->getMessage(), ['date' => $date]);
        jsonResponse(['success' => false, 'error' => 'Failed to clear day'], 500);
    }
}

// ==================== FOOD FUNCTIONS ====================

function getAllFoods() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM foods ORDER BY name ASC");
        $foods = $stmt->fetchAll();

        // Convert to expected format
        $result = array_map(function($f) {
            return [
                'id' => intval($f['id']),
                'name' => $f['name'],
                'category' => $f['category'],
                'calories' => floatval($f['calories']),
                'protein' => floatval($f['protein']),
                'carbs' => floatval($f['carbs']),
                'fat' => floatval($f['fat']),
                'fiber' => floatval($f['fiber']),
                'serving' => floatval($f['serving']),
                'unit' => $f['unit']
            ];
        }, $foods);

        jsonResponse(['success' => true, 'foods' => $result]);
    } catch (PDOException $e) {
        logError('getAllFoods failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load foods'], 500);
    }
}

function searchFoods($search, $category) {
    try {
        $db = getDB();

        // Sanitize search input
        $search = sanitizeString($search, 100);
        $category = sanitizeString($category, 50);

        $sql = "SELECT * FROM foods WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND name LIKE ?";
            $params[] = "%" . $search . "%";
        }

        if ($category && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY name ASC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $foods = $stmt->fetchAll();

        // Format response
        $result = array_map(function($f) {
            return [
                'id' => intval($f['id']),
                'name' => $f['name'],
                'category' => $f['category'],
                'calories' => floatval($f['calories']),
                'protein' => floatval($f['protein']),
                'carbs' => floatval($f['carbs']),
                'fat' => floatval($f['fat']),
                'fiber' => floatval($f['fiber']),
                'serving' => floatval($f['serving']),
                'unit' => $f['unit']
            ];
        }, $foods);

        jsonResponse(['success' => true, 'foods' => $result]);
    } catch (PDOException $e) {
        logError('searchFoods failed: ' . $e->getMessage(), ['search' => $search]);
        jsonResponse(['success' => false, 'error' => 'Search failed'], 500);
    }
}

function addCustomFood($food) {
    // Validate required fields
    $name = sanitizeString($food['name'] ?? '', 255);
    if (empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Food name is required'], 400);
        return;
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO foods (name, category, calories, protein, carbs, fat, fiber, serving, unit, is_custom)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $name,
            sanitizeString($food['category'] ?? 'Custom', 50),
            floatval($food['calories'] ?? 0),
            floatval($food['protein'] ?? 0),
            floatval($food['carbs'] ?? 0),
            floatval($food['fat'] ?? 0),
            floatval($food['fiber'] ?? 0),
            floatval($food['serving'] ?? 1),
            sanitizeString($food['unit'] ?? 'serving', 50)
        ]);

        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        logError('addCustomFood failed: ' . $e->getMessage(), ['food' => $food]);
        jsonResponse(['success' => false, 'error' => 'Failed to add food'], 500);
    }
}

// ==================== GOALS FUNCTIONS ====================

function getGoals() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM goals LIMIT 1");
        $goals = $stmt->fetch();

        if (!$goals) {
            $goals = [
                'calories' => 1450,
                'protein' => 105,
                'carbs' => 140,
                'fat' => 50,
                'fiber' => 28,
                'targetWeight' => 65
            ];
        } else {
            $goals = [
                'calories' => intval($goals['calories']),
                'protein' => intval($goals['protein']),
                'carbs' => intval($goals['carbs']),
                'fat' => intval($goals['fat']),
                'fiber' => intval($goals['fiber']),
                'targetWeight' => floatval($goals['target_weight'])
            ];
        }

        jsonResponse(['success' => true, 'goals' => $goals]);
    } catch (PDOException $e) {
        logError('getGoals failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load goals'], 500);
    }
}

function updateGoals($goals) {
    // Validate numeric values
    $fields = ['calories', 'protein', 'carbs', 'fat', 'fiber'];
    foreach ($fields as $field) {
        if (isset($goals[$field]) && !validateNumeric($goals[$field], 0, 10000)) {
            jsonResponse(['success' => false, 'error' => "Invalid $field value"], 400);
            return;
        }
    }

    try {
        $db = getDB();

        // Check if goals exist
        $stmt = $db->query("SELECT id FROM goals LIMIT 1");
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $db->prepare("
                UPDATE goals SET calories = ?, protein = ?, carbs = ?, fat = ?, fiber = ?, target_weight = ?
                WHERE id = ?
            ");
            $stmt->execute([
                intval($goals['calories'] ?? 1450),
                intval($goals['protein'] ?? 105),
                intval($goals['carbs'] ?? 140),
                intval($goals['fat'] ?? 50),
                intval($goals['fiber'] ?? 28),
                floatval($goals['targetWeight'] ?? 65),
                $existing['id']
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO goals (calories, protein, carbs, fat, fiber, target_weight)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                intval($goals['calories'] ?? 1450),
                intval($goals['protein'] ?? 105),
                intval($goals['carbs'] ?? 140),
                intval($goals['fat'] ?? 50),
                intval($goals['fiber'] ?? 28),
                floatval($goals['targetWeight'] ?? 65)
            ]);
        }

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('updateGoals failed: ' . $e->getMessage(), ['goals' => $goals]);
        jsonResponse(['success' => false, 'error' => 'Failed to update goals'], 500);
    }
}

// ==================== WEIGHT FUNCTIONS ====================

function logWeight($data) {
    // Validate
    if (!validateDate($data['date'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }
    if (!validateNumeric($data['weight'] ?? 0, 20, 500)) {
        jsonResponse(['success' => false, 'error' => 'Invalid weight (20-500 kg)'], 400);
        return;
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO weight_log (date, weight, notes)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE weight = VALUES(weight), notes = VALUES(notes)
        ");

        $stmt->execute([
            $data['date'],
            floatval($data['weight']),
            sanitizeString($data['notes'] ?? '', 500)
        ]);

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('logWeight failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to log weight'], 500);
    }
}

function getWeightHistory() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT date, weight, notes FROM weight_log ORDER BY date ASC");
        $history = $stmt->fetchAll();

        $result = array_map(function($row) {
            return [
                'date' => $row['date'],
                'weight' => floatval($row['weight']),
                'notes' => $row['notes']
            ];
        }, $history);

        jsonResponse(['success' => true, 'history' => $result]);
    } catch (PDOException $e) {
        logError('getWeightHistory failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load weight history'], 500);
    }
}

// ==================== EXERCISE FUNCTIONS ====================

function logExercise($data) {
    // Validate
    if (!validateDate($data['date'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }
    if (!isset($data['exerciseIndex']) || !is_numeric($data['exerciseIndex'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid exercise index'], 400);
        return;
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO exercise_log (date, exercise_index, completed)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE completed = VALUES(completed)
        ");

        $stmt->execute([
            $data['date'],
            intval($data['exerciseIndex']),
            $data['completed'] ? 1 : 0
        ]);

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('logExercise failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to log exercise'], 500);
    }
}

function getExerciseLog($date) {
    if (!validateDate($date)) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT exercise_index, completed FROM exercise_log WHERE date = ?");
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll();

        $exercises = [];
        foreach ($rows as $row) {
            $key = $date . '-' . $row['exercise_index'];
            $exercises[$key] = $row['completed'] ? true : false;
        }

        jsonResponse(['success' => true, 'exercises' => $exercises]);
    } catch (PDOException $e) {
        logError('getExerciseLog failed: ' . $e->getMessage(), ['date' => $date]);
        jsonResponse(['success' => false, 'error' => 'Failed to load exercise log'], 500);
    }
}

// ==================== PR FUNCTIONS ====================

function logPR($data) {
    // Validate
    $exerciseName = sanitizeString($data['exerciseName'] ?? '', 100);
    if (empty($exerciseName)) {
        jsonResponse(['success' => false, 'error' => 'Exercise name is required'], 400);
        return;
    }
    if (!isset($data['value']) || !is_numeric($data['value'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid PR value'], 400);
        return;
    }

    $prType = in_array($data['prType'] ?? '', ['reps', 'weight', 'time']) ? $data['prType'] : 'reps';

    try {
        $db = getDB();

        // Check existing PR
        $stmt = $db->prepare("SELECT value, pr_type FROM pr_log WHERE exercise_name = ?");
        $stmt->execute([$exerciseName]);
        $existing = $stmt->fetch();

        $shouldUpdate = true;
        if ($existing) {
            if ($prType === 'time') {
                $shouldUpdate = $data['value'] < $existing['value']; // Lower is better for time
            } else {
                $shouldUpdate = $data['value'] > $existing['value']; // Higher is better for reps/weight
            }
        }

        if ($shouldUpdate) {
            $achievedDate = date('M j');
            $stmt = $db->prepare("
                INSERT INTO pr_log (exercise_name, value, pr_type, achieved_date)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value), pr_type = VALUES(pr_type), achieved_date = VALUES(achieved_date)
            ");
            $stmt->execute([
                $exerciseName,
                floatval($data['value']),
                $prType,
                $achievedDate
            ]);
        }

        jsonResponse(['success' => true, 'updated' => $shouldUpdate]);
    } catch (PDOException $e) {
        logError('logPR failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to log PR'], 500);
    }
}

function getPRLog() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT exercise_name, value, pr_type, achieved_date FROM pr_log");
        $rows = $stmt->fetchAll();

        $prs = [];
        foreach ($rows as $row) {
            $prs[$row['exercise_name']] = [
                'value' => floatval($row['value']),
                'type' => $row['pr_type'],
                'date' => $row['achieved_date']
            ];
        }

        jsonResponse(['success' => true, 'prs' => $prs]);
    } catch (PDOException $e) {
        logError('getPRLog failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load PR log'], 500);
    }
}

// ==================== DAY TYPE FUNCTIONS ====================

function setDayType($data) {
    // Validate
    if (!validateDate($data['date'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }

    $validTypes = ['normal', 'light', 'cheat'];
    $type = in_array($data['type'] ?? '', $validTypes) ? $data['type'] : 'normal';

    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO day_types (date, type)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE type = VALUES(type)
        ");

        $stmt->execute([$data['date'], $type]);

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('setDayType failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to set day type'], 500);
    }
}

function getDayTypes() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT date, type FROM day_types");
        $rows = $stmt->fetchAll();

        $dayTypes = [];
        foreach ($rows as $row) {
            $dayTypes[$row['date']] = $row['type'];
        }

        jsonResponse(['success' => true, 'dayTypes' => $dayTypes]);
    } catch (PDOException $e) {
        logError('getDayTypes failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load day types'], 500);
    }
}

// ==================== WATER FUNCTIONS ====================

function logWater($data) {
    // Validate
    if (!validateDate($data['date'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }
    if (!validateNumeric($data['glasses'] ?? 0, 0, 50)) {
        jsonResponse(['success' => false, 'error' => 'Invalid glasses count (0-50)'], 400);
        return;
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO water_log (date, glasses)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE glasses = VALUES(glasses)
        ");

        $stmt->execute([
            $data['date'],
            intval($data['glasses'])
        ]);

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('logWater failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to log water'], 500);
    }
}

function getWater($date) {
    if (!validateDate($date)) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT glasses FROM water_log WHERE date = ?");
        $stmt->execute([$date]);
        $row = $stmt->fetch();

        jsonResponse(['success' => true, 'glasses' => $row ? intval($row['glasses']) : 0]);
    } catch (PDOException $e) {
        logError('getWater failed: ' . $e->getMessage(), ['date' => $date]);
        jsonResponse(['success' => false, 'error' => 'Failed to load water log'], 500);
    }
}

// ==================== COMBO FUNCTIONS ====================

function saveMealCombo($combo) {
    // Validate
    $name = sanitizeString($combo['name'] ?? '', 100);
    if (empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Combo name is required'], 400);
        return;
    }

    $validCategories = ['breakfast', 'lunch', 'dinner', 'snacks', 'cheat'];
    $category = in_array($combo['category'] ?? '', $validCategories) ? $combo['category'] : 'breakfast';

    $validTags = ['High', 'Highest', 'Light', 'Max Protein', 'Cheat', null, ''];
    $tag = in_array($combo['tag'] ?? null, $validTags) ? ($combo['tag'] ?: null) : null;

    $validDamage = ['low', 'medium', 'high', null, ''];
    $damageLevel = in_array($combo['damageLevel'] ?? null, $validDamage) ? ($combo['damageLevel'] ?: null) : null;

    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO meal_combos (name, emoji, category, tag, damage_level, items, total_calories, total_protein, total_carbs, total_fat, total_fiber)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            sanitizeString($combo['emoji'] ?? '🍽️', 10),
            $category,
            $tag,
            $damageLevel,
            json_encode($combo['items'] ?? []),
            intval($combo['totalCalories'] ?? 0),
            intval($combo['totalProtein'] ?? 0),
            intval($combo['totalCarbs'] ?? 0),
            intval($combo['totalFat'] ?? 0),
            intval($combo['totalFiber'] ?? 0)
        ]);

        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        logError('saveMealCombo failed: ' . $e->getMessage(), ['combo' => $combo]);
        jsonResponse(['success' => false, 'error' => 'Failed to save combo'], 500);
    }
}

function getMealCombos() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM meal_combos ORDER BY id ASC");
        $rows = $stmt->fetchAll();

        $combos = array_map(function($row) {
            return [
                'id' => intval($row['id']),
                'name' => $row['name'],
                'emoji' => $row['emoji'],
                'category' => $row['category'],
                'tag' => $row['tag'],
                'damageLevel' => $row['damage_level'],
                'items' => json_decode($row['items'], true),
                'totalCalories' => intval($row['total_calories']),
                'totalProtein' => intval($row['total_protein']),
                'totalCarbs' => intval($row['total_carbs']),
                'totalFat' => intval($row['total_fat']),
                'totalFiber' => intval($row['total_fiber'])
            ];
        }, $rows);

        jsonResponse(['success' => true, 'combos' => $combos]);
    } catch (PDOException $e) {
        logError('getMealCombos failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load combos'], 500);
    }
}
