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

    case 'updateMeal':
        updateMeal($input);
        break;

    case 'clearDay':
        clearDay($input['date'] ?? date('Y-m-d'));
        break;

    case 'fixFoodQuantities':
        fixFoodQuantities();
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

    case 'deleteWeight':
        deleteWeight($input);
        break;

    // ==================== EXERCISE ====================
    case 'logExercise':
        logExercise($input);
        break;

    case 'getExerciseLog':
        $date = $_GET['date'] ?? $input['date'] ?? date('Y-m-d');
        getExerciseLog($date);
        break;

    case 'getAllExerciseLogs':
        getAllExerciseLogs();
        break;

    // ==================== PR ====================
    case 'logPR':
        logPR($input);
        break;

    case 'getPRLog':
        getPRLog();
        break;

    case 'getStreak':
        getStreak();
        break;

    // ==================== DAY TYPES ====================
    case 'setDayType':
        setDayType($input);
        break;

    case 'getDayTypes':
        getDayTypes();
        break;

    // ==================== WORKOUT OVERRIDES ====================
    case 'getWorkoutOverrides':
        getWorkoutOverrides();
        break;

    case 'setWorkoutOverride':
        setWorkoutOverride($input);
        break;

    case 'clearWorkoutOverrides':
        clearWorkoutOverrides();
        break;

    case 'skipDay':
        skipDay($input);
        break;

    // ==================== FLEXIBLE SCHEDULE ====================
    case 'getWorkoutTypes':
        getWorkoutTypes();
        break;

    case 'createWorkoutType':
        createWorkoutType($input);
        break;

    case 'updateWorkoutType':
        updateWorkoutType($input);
        break;

    case 'deleteWorkoutType':
        deleteWorkoutType($input);
        break;

    case 'getWeeklySchedule':
        getWeeklySchedule();
        break;

    case 'updateDaySchedule':
        updateDaySchedule($input);
        break;

    case 'saveFullSchedule':
        saveFullSchedule($input);
        break;

    // ==================== WATER ====================
    case 'logWater':
        logWater($input);
        break;

    case 'getWater':
        $date = $_GET['date'] ?? $input['date'] ?? date('Y-m-d');
        getWater($date);
        break;

    case 'getWeeklyStats':
        $startDate = $_GET['startDate'] ?? $input['startDate'] ?? date('Y-m-d', strtotime('monday this week'));
        getWeeklyStats($startDate);
        break;

    // ==================== COMBOS ====================
    case 'saveMealCombo':
        saveMealCombo($input['combo'] ?? $input);
        break;

    case 'getMealCombos':
        getMealCombos();
        break;

    case 'deleteMealCombo':
        deleteMealCombo($input);
        break;

    case 'hideDefaultCombo':
        hideDefaultCombo($input);
        break;

    case 'getHiddenCombos':
        getHiddenCombos();
        break;

    case 'setDefaultCombo':
        setDefaultCombo($input);
        break;

    // ==================== EDIT MODE / PASSWORD ====================
    case 'verifyEditPassword':
        verifyEditPassword($input);
        break;

    case 'changeEditPassword':
        changeEditPassword($input);
        break;

    // ==================== WORKOUT SCHEDULE ====================
    case 'getWorkoutSchedule':
        getWorkoutSchedule();
        break;

    case 'getWorkoutExercises':
        $workoutType = $_GET['workoutType'] ?? $input['workoutType'] ?? '';
        getWorkoutExercises($workoutType);
        break;

    case 'getAllWorkouts':
        getAllWorkouts();
        break;

    case 'updateExercise':
        updateExercise($input);
        break;

    case 'addExercise':
        addExercise($input);
        break;

    case 'deleteExercise':
        deleteExercise($input);
        break;

    case 'reorderExercises':
        reorderExercises($input);
        break;

    case 'saveWorkout':
        saveWorkout($input);
        break;

    // ==================== EXERCISE LIBRARY ====================
    case 'getExerciseLibrary':
        $category = $_GET['category'] ?? $input['category'] ?? '';
        $search = $_GET['search'] ?? $input['search'] ?? '';
        getExerciseLibrary($category, $search);
        break;

    case 'getExerciseLibraryCategories':
        getExerciseLibraryCategories();
        break;

    case 'addToExerciseLibrary':
        addToExerciseLibrary($input);
        break;

    // ==================== AI NUTRITION LOOKUP ====================
    case 'analyzeNutrition':
        analyzeNutrition($input);
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

// ==================== AI NUTRITION LOOKUP ====================

function analyzeNutrition($input) {
    // Validate API key is configured
    if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
        jsonResponse(['success' => false, 'error' => 'AI service not configured'], 503);
        return;
    }

    // Validate input
    $query = trim($input['query'] ?? '');
    if (empty($query) || strlen($query) > 500) {
        jsonResponse(['success' => false, 'error' => 'Invalid query'], 400);
        return;
    }

    // Rate limit AI requests more strictly (10 per minute per IP)
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    checkRateLimit('ai_' . $client_ip, 10, 60);

    $systemPrompt = "You are an expert nutritionist specializing in global cuisines, with a deep focus on Indian meals (thalis, combos, street food).
    Analyze the user's dish or meal combination.
    You MUST respond ONLY with a valid JSON object.

    SPECIAL INSTRUCTIONS FOR INDIAN MEALS:
    - Account for 'Tadka' (tempering) which adds significant fat.
    - If a meal is mentioned (e.g., 'Dal Chawal'), provide a combined total but mention the assumed portions in 'serving_size'.
    - Account for hidden sugars in chutneys and gravies.

    Format:
    {
      \"dish\": \"Dish Name\",
      \"calories\": 0,
      \"protein\": 0,
      \"carbs\": 0,
      \"fats\": 0,
      \"fiber\": 0,
      \"serving_size\": \"e.g. 2 Rotis + 1 bowl Dal\",
      \"health_note\": \"A very brief 1-sentence health insight.\"
    }";

    $requestBody = json_encode([
        'contents' => [['parts' => [['text' => $query]]]],
        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
        'generationConfig' => ['responseMimeType' => 'application/json']
    ]);

    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;

    // Make request to Gemini API
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        logError('Gemini API curl error: ' . $curlError);
        jsonResponse(['success' => false, 'error' => 'AI service unavailable'], 503);
        return;
    }

    if ($httpCode !== 200) {
        logError('Gemini API error', ['code' => $httpCode, 'response' => $response]);
        jsonResponse(['success' => false, 'error' => 'AI analysis failed'], 500);
        return;
    }

    $data = json_decode($response, true);
    $resultText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$resultText) {
        logError('Gemini API empty response', ['response' => $response]);
        jsonResponse(['success' => false, 'error' => 'Could not analyze meal'], 500);
        return;
    }

    $result = json_decode($resultText, true);
    if (!$result || !isset($result['dish'])) {
        logError('Gemini API invalid JSON', ['text' => $resultText]);
        jsonResponse(['success' => false, 'error' => 'Invalid response from AI'], 500);
        return;
    }

    jsonResponse([
        'success' => true,
        'data' => $result
    ]);
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

function updateMeal($data) {
    $id = $data['id'] ?? 0;

    // Validate ID
    if (!$id || !is_numeric($id) || $id <= 0) {
        jsonResponse(['success' => false, 'error' => 'Invalid meal ID'], 400);
        return;
    }

    // Validate numeric values
    $quantity = floatval($data['quantity'] ?? 1);
    $calories = floatval($data['calories'] ?? 0);
    $protein = floatval($data['protein'] ?? 0);
    $carbs = floatval($data['carbs'] ?? 0);
    $fat = floatval($data['fat'] ?? 0);
    $fiber = floatval($data['fiber'] ?? 0);
    $unit = sanitizeString($data['unit'] ?? 'serving', 50);

    if ($quantity <= 0 || $quantity > 100) {
        jsonResponse(['success' => false, 'error' => 'Invalid quantity'], 400);
        return;
    }

    try {
        $db = getDB();

        // Optimistic locking: Check if record was modified since client fetched it
        // Use created_at as a simple version check (if provided by client)
        $expectedTimestamp = $data['_lastModified'] ?? null;

        if ($expectedTimestamp) {
            $stmt = $db->prepare("SELECT created_at FROM meal_log WHERE id = ?");
            $stmt->execute([intval($id)]);
            $row = $stmt->fetch();

            if ($row && $row['created_at'] !== $expectedTimestamp) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Record was modified by another device. Please refresh and try again.',
                    'code' => 'CONFLICT'
                ], 409);
                return;
            }
        }

        $stmt = $db->prepare("
            UPDATE meal_log SET
                quantity = ?,
                unit = ?,
                calories = ?,
                protein = ?,
                carbs = ?,
                fat = ?,
                fiber = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $quantity,
            $unit,
            $calories,
            $protein,
            $carbs,
            $fat,
            $fiber,
            intval($id)
        ]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'error' => 'Meal not found'], 404);
            return;
        }

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('updateMeal failed: ' . $e->getMessage(), ['id' => $id]);
        jsonResponse(['success' => false, 'error' => 'Failed to update meal'], 500);
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

// One-time fix for incorrect food quantities (e.g., rice showing "1 g" instead of "100 g")
function fixFoodQuantities() {
    try {
        $db = getDB();

        // Find rice entries where quantity is low but calories are high (incorrect)
        // Rice is 130 cal per 100g, so if calories ~130 and quantity < 10, fix to 100
        $stmt = $db->prepare("
            SELECT id, food_name, quantity, unit, calories
            FROM meal_log
            WHERE LOWER(food_name) LIKE '%rice%'
            AND unit = 'g'
            AND quantity < 10
            AND calories >= 100
        ");
        $stmt->execute();
        $incorrectEntries = $stmt->fetchAll();

        $fixed = [];
        foreach ($incorrectEntries as $entry) {
            // Calculate correct quantity based on calories (130 cal per 100g)
            $correctQuantity = round(($entry['calories'] / 130) * 100);

            $updateStmt = $db->prepare("UPDATE meal_log SET quantity = ? WHERE id = ?");
            $updateStmt->execute([$correctQuantity, $entry['id']]);

            $fixed[] = [
                'id' => $entry['id'],
                'food' => $entry['food_name'],
                'old' => $entry['quantity'] . ' ' . $entry['unit'],
                'new' => $correctQuantity . ' ' . $entry['unit']
            ];
        }

        jsonResponse(['success' => true, 'fixed' => $fixed, 'count' => count($fixed)]);
    } catch (PDOException $e) {
        logError('fixFoodQuantities failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to fix quantities'], 500);
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

        // Use sanitizeForDB for database queries (PDO handles SQL injection)
        // This preserves special chars like "&" for proper searching
        $search = sanitizeForDB($search, 100);
        $category = sanitizeForDB($category, 50);

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

function deleteWeight($data) {
    // Validate
    if (!validateDate($data['date'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM weight_log WHERE date = ?");
        $stmt->execute([$data['date']]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['success' => false, 'error' => 'No entry found for this date'], 404);
        }
    } catch (PDOException $e) {
        logError('deleteWeight failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to delete weight entry'], 500);
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

        // Support both explicit set and toggle operations
        // Toggle is atomic and prevents race conditions
        $toggle = $data['toggle'] ?? false;

        if ($toggle) {
            // Atomic toggle - useful for UI that clicks to toggle
            // First, try to insert with completed=1
            // If exists, flip the current value
            $stmt = $db->prepare("
                INSERT INTO exercise_log (date, exercise_index, completed)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE completed = NOT completed
            ");
            $stmt->execute([
                $data['date'],
                intval($data['exerciseIndex'])
            ]);

            // Return the new state
            $stmt = $db->prepare("SELECT completed FROM exercise_log WHERE date = ? AND exercise_index = ?");
            $stmt->execute([$data['date'], intval($data['exerciseIndex'])]);
            $row = $stmt->fetch();

            jsonResponse(['success' => true, 'completed' => $row ? (bool)$row['completed'] : true]);
        } else {
            // Explicit set (original behavior)
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

            jsonResponse(['success' => true, 'completed' => (bool)$data['completed']]);
        }
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

function getAllExerciseLogs() {
    try {
        $db = getDB();
        // Get all exercise logs from the last 60 days
        $stmt = $db->query("SELECT date, exercise_index, completed FROM exercise_log WHERE date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)");
        $rows = $stmt->fetchAll();

        $exercises = [];
        foreach ($rows as $row) {
            $key = $row['date'] . '-' . $row['exercise_index'];
            $exercises[$key] = $row['completed'] ? true : false;
        }

        jsonResponse(['success' => true, 'exercises' => $exercises]);
    } catch (PDOException $e) {
        logError('getAllExerciseLogs failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load exercise logs'], 500);
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
    $newValue = floatval($data['value']);

    // Use proper DATE format (Y-m-d) for storage
    // Accept both "Dec 3" format from frontend and "2025-12-03" format
    $rawDate = $data['achievedDate'] ?? '';
    if (!empty($rawDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
        // Already in Y-m-d format
        $achievedDate = $rawDate;
    } elseif (!empty($rawDate) && preg_match('/^([A-Za-z]{3})\s+(\d{1,2})$/', trim($rawDate), $matches)) {
        // Convert "Dec 3" format to Y-m-d
        $parsedDate = DateTime::createFromFormat('M j Y', $matches[1] . ' ' . $matches[2] . ' ' . date('Y'));
        if ($parsedDate && $parsedDate > new DateTime()) {
            $parsedDate->modify('-1 year'); // If date is in future, use last year
        }
        $achievedDate = $parsedDate ? $parsedDate->format('Y-m-d') : date('Y-m-d');
    } else {
        $achievedDate = date('Y-m-d');
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        // Use SELECT FOR UPDATE to prevent race conditions
        // This locks the row until the transaction completes
        $stmt = $db->prepare("SELECT value, pr_type FROM pr_log WHERE exercise_name = ? FOR UPDATE");
        $stmt->execute([$exerciseName]);
        $existing = $stmt->fetch();

        $shouldUpdate = true;
        if ($existing) {
            if ($prType === 'time') {
                $shouldUpdate = $newValue < $existing['value']; // Lower is better for time
            } else {
                $shouldUpdate = $newValue > $existing['value']; // Higher is better for reps/weight
            }
        }

        if ($shouldUpdate) {
            $stmt = $db->prepare("
                INSERT INTO pr_log (exercise_name, value, pr_type, achieved_date)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value), pr_type = VALUES(pr_type), achieved_date = VALUES(achieved_date)
            ");
            $stmt->execute([
                $exerciseName,
                $newValue,
                $prType,
                $achievedDate
            ]);
        }

        $db->commit();
        jsonResponse(['success' => true, 'updated' => $shouldUpdate]);
    } catch (PDOException $e) {
        $db->rollBack();
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
            // Format date for display (convert Y-m-d to "Dec 3" format for frontend)
            $displayDate = $row['achieved_date'];
            if ($displayDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $displayDate)) {
                $dateObj = DateTime::createFromFormat('Y-m-d', $displayDate);
                if ($dateObj) {
                    $displayDate = $dateObj->format('M j');
                }
            }

            $prs[$row['exercise_name']] = [
                'value' => floatval($row['value']),
                'type' => $row['pr_type'],
                'date' => $displayDate,
                'rawDate' => $row['achieved_date'] // Include raw date for sorting/comparison
            ];
        }

        jsonResponse(['success' => true, 'prs' => $prs]);
    } catch (PDOException $e) {
        logError('getPRLog failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load PR log'], 500);
    }
}

function getStreak() {
    try {
        $db = getDB();
        $today = date('Y-m-d');

        // OPTIMIZED: Single query to get all activity dates (instead of 90+ queries)
        $stmt = $db->query("
            SELECT DISTINCT date FROM (
                SELECT date FROM meal_log WHERE date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                UNION
                SELECT date FROM water_log WHERE date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY) AND glasses > 0
                UNION
                SELECT date FROM exercise_log WHERE date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY) AND completed = 1
            ) AS activity_dates
            ORDER BY date DESC
        ");
        $activityDates = array_column($stmt->fetchAll(), 'date');

        // Convert to a set for O(1) lookup
        $activitySet = array_flip($activityDates);

        // Calculate streak
        // If today has no activity, start counting from yesterday
        // This prevents streak from being 0 just because user hasn't logged yet today
        $streak = 0;
        $startOffset = 0;

        // Check if today has activity
        $todayHasActivity = isset($activitySet[$today]);
        if (!$todayHasActivity) {
            // Start from yesterday if today has no activity
            $startOffset = 1;
        }

        for ($i = $startOffset; $i < 365; $i++) {
            $checkDate = date('Y-m-d', strtotime("-$i days"));
            $hasActivity = isset($activitySet[$checkDate]);

            if ($hasActivity) {
                $streak++;
            } else {
                // Break on first inactive day
                break;
            }
        }

        jsonResponse(['success' => true, 'streak' => $streak]);
    } catch (PDOException $e) {
        logError('getStreak failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to calculate streak'], 500);
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

// ==================== WORKOUT OVERRIDE FUNCTIONS ====================

function ensureWorkoutOverridesTable() {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS workout_overrides (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL UNIQUE,
            workout_type VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function getWorkoutOverrides() {
    try {
        $db = getDB();
        ensureWorkoutOverridesTable();

        // Get Monday of current week
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));

        // Clean up old overrides (before this week)
        $db->exec("DELETE FROM workout_overrides WHERE date < '$monday'");

        // Get current week's overrides
        $stmt = $db->prepare("SELECT date, workout_type FROM workout_overrides WHERE date >= ? AND date <= ?");
        $stmt->execute([$monday, $sunday]);
        $rows = $stmt->fetchAll();

        $overrides = [];
        foreach ($rows as $row) {
            $overrides[$row['date']] = $row['workout_type'];
        }

        jsonResponse(['success' => true, 'overrides' => $overrides]);
    } catch (PDOException $e) {
        logError('getWorkoutOverrides failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load workout overrides'], 500);
    }
}

function setWorkoutOverride($data) {
    if (!validateDate($data['date'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }

    $workoutType = sanitizeString($data['workoutType'] ?? '', 50);
    if (empty($workoutType)) {
        jsonResponse(['success' => false, 'error' => 'Workout type is required'], 400);
        return;
    }

    try {
        $db = getDB();
        ensureWorkoutOverridesTable();

        $stmt = $db->prepare("
            INSERT INTO workout_overrides (date, workout_type)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE workout_type = VALUES(workout_type)
        ");
        $stmt->execute([$data['date'], $workoutType]);

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('setWorkoutOverride failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to set workout override'], 500);
    }
}

function clearWorkoutOverrides() {
    try {
        $db = getDB();
        ensureWorkoutOverridesTable();

        // Get Monday and Sunday of current week
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));

        $stmt = $db->prepare("DELETE FROM workout_overrides WHERE date >= ? AND date <= ?");
        $stmt->execute([$monday, $sunday]);

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('clearWorkoutOverrides failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to clear workout overrides'], 500);
    }
}

function skipDay($data) {
    if (!validateDate($data['date'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid date'], 400);
        return;
    }

    $skipDate = $data['date'];
    $skipDateObj = new DateTime($skipDate);
    $dayOfWeek = (int)$skipDateObj->format('N'); // 1=Mon, 7=Sun

    // Don't allow skip on Saturday(6) or Sunday(7)
    if ($dayOfWeek >= 6) {
        jsonResponse(['success' => false, 'error' => 'Cannot skip on rest days'], 400);
        return;
    }

    try {
        $db = getDB();
        ensureWorkoutOverridesTable();

        // Get Monday and Friday of current week
        $monday = date('Y-m-d', strtotime('monday this week'));
        $friday = date('Y-m-d', strtotime('friday this week'));

        // Default workout schedule (1=Mon to 7=Sun, PHP date('N') format)
        $defaultSchedule = [
            1 => 'push',        // Monday
            2 => 'pull',        // Tuesday
            3 => 'legs',        // Wednesday
            4 => 'upper',       // Thursday
            5 => 'cardio',      // Friday
            6 => 'light_cardio', // Saturday
            7 => 'rest'         // Sunday
        ];

        // Get existing overrides for this week
        $stmt = $db->prepare("SELECT date, workout_type FROM workout_overrides WHERE date >= ? AND date <= ?");
        $stmt->execute([$monday, $friday]);
        $existingOverrides = [];
        foreach ($stmt->fetchAll() as $row) {
            $existingOverrides[$row['date']] = $row['workout_type'];
        }

        // Build array of dates from skip date to Friday
        $datesToShift = [];
        $currentDate = clone $skipDateObj;
        $fridayObj = new DateTime($friday);

        while ($currentDate <= $fridayObj) {
            $datesToShift[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        // Get current workout types for these dates (considering existing overrides)
        $currentTypes = [];
        foreach ($datesToShift as $dateStr) {
            if (isset($existingOverrides[$dateStr])) {
                $currentTypes[] = $existingOverrides[$dateStr];
            } else {
                $dateObj = new DateTime($dateStr);
                $dow = (int)$dateObj->format('N');
                $currentTypes[] = $defaultSchedule[$dow];
            }
        }

        // Shift: insert 'rest' at beginning, shift everything forward (last one drops off)
        $newTypes = array_merge(['rest'], array_slice($currentTypes, 0, -1));

        // Apply new overrides in transaction
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO workout_overrides (date, workout_type)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE workout_type = VALUES(workout_type)
        ");

        foreach ($datesToShift as $index => $dateStr) {
            $stmt->execute([$dateStr, $newTypes[$index]]);
        }

        $db->commit();

        // Return updated overrides
        $stmt = $db->prepare("SELECT date, workout_type FROM workout_overrides WHERE date >= ? AND date <= ?");
        $stmt->execute([$monday, date('Y-m-d', strtotime('sunday this week'))]);

        $overrides = [];
        foreach ($stmt->fetchAll() as $row) {
            $overrides[$row['date']] = $row['workout_type'];
        }

        jsonResponse(['success' => true, 'overrides' => $overrides]);
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        logError('skipDay failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to skip day'], 500);
    }
}

// ==================== FLEXIBLE SCHEDULE FUNCTIONS ====================

function ensureFlexibleScheduleTables() {
    $db = getDB();

    // Create workout_types table
    $db->exec("
        CREATE TABLE IF NOT EXISTS workout_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type_key VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            emoji VARCHAR(10) DEFAULT '💪',
            color VARCHAR(20) DEFAULT 'blue',
            description TEXT,
            is_rest BOOLEAN DEFAULT FALSE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Create weekly_schedule table
    $db->exec("
        CREATE TABLE IF NOT EXISTS weekly_schedule (
            day_of_week INT PRIMARY KEY,
            workout_type_key VARCHAR(50) NOT NULL,
            INDEX idx_type_key (workout_type_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Check if workout_types is empty and seed initial data
    $count = $db->query("SELECT COUNT(*) FROM workout_types")->fetchColumn();
    if ($count == 0) {
        seedInitialScheduleData($db);
    }
}

function seedInitialScheduleData($db) {
    // Insert default workout types
    $workoutTypes = [
        ['push', 'Push + Core', '💪', 'orange', 'Chest, shoulders, triceps with core work', 0, 1],
        ['pull', 'Pull', '🏋️', 'blue', 'Back and biceps focused workout', 0, 2],
        ['legs', 'Legs', '🦵', 'purple', 'Lower body strength training', 0, 3],
        ['upper', 'Upper Var.', '🔄', 'teal', 'Upper body variation exercises', 0, 4],
        ['cardio', 'Cardio', '🏃', 'green', 'Cardiovascular training', 0, 5],
        ['light_cardio', 'Light Cardio', '🚶', 'teal', 'Light recovery cardio and stretching', 0, 6],
        ['rest', 'Rest', '😴', 'gray', 'Rest and recovery day', 1, 7]
    ];

    $stmt = $db->prepare("
        INSERT INTO workout_types (type_key, name, emoji, color, description, is_rest, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($workoutTypes as $type) {
        $stmt->execute($type);
    }

    // Insert default weekly schedule
    $schedule = [
        [0, 'push'],        // Monday
        [1, 'pull'],        // Tuesday
        [2, 'legs'],        // Wednesday
        [3, 'upper'],       // Thursday
        [4, 'cardio'],      // Friday
        [5, 'light_cardio'], // Saturday
        [6, 'rest']         // Sunday
    ];

    $stmt = $db->prepare("INSERT INTO weekly_schedule (day_of_week, workout_type_key) VALUES (?, ?)");
    foreach ($schedule as $day) {
        $stmt->execute($day);
    }
}

function getWorkoutTypes() {
    try {
        $db = getDB();
        ensureFlexibleScheduleTables();

        $stmt = $db->query("SELECT * FROM workout_types ORDER BY sort_order, id");
        $types = [];
        foreach ($stmt->fetchAll() as $row) {
            $types[$row['type_key']] = [
                'id' => (int)$row['id'],
                'key' => $row['type_key'],
                'name' => $row['name'],
                'emoji' => $row['emoji'],
                'color' => $row['color'],
                'description' => $row['description'],
                'isRest' => (bool)$row['is_rest'],
                'sortOrder' => (int)$row['sort_order']
            ];
        }

        jsonResponse(['success' => true, 'types' => $types]);
    } catch (PDOException $e) {
        logError('getWorkoutTypes failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to get workout types'], 500);
    }
}

function createWorkoutType($data) {
    $typeKey = sanitizeString($data['typeKey'] ?? '', 50);
    $name = sanitizeString($data['name'] ?? '', 100);
    $emoji = sanitizeString($data['emoji'] ?? '💪', 10);
    $color = sanitizeString($data['color'] ?? 'blue', 20);
    $description = sanitizeString($data['description'] ?? '', 500);
    $isRest = (bool)($data['isRest'] ?? false);

    if (empty($typeKey) || empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Type key and name are required'], 400);
        return;
    }

    // Validate typeKey format (lowercase, underscores allowed)
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $typeKey)) {
        jsonResponse(['success' => false, 'error' => 'Type key must be lowercase letters, numbers, underscores'], 400);
        return;
    }

    try {
        $db = getDB();
        ensureFlexibleScheduleTables();

        // Get next sort order
        $maxOrder = $db->query("SELECT MAX(sort_order) FROM workout_types")->fetchColumn();
        $sortOrder = ($maxOrder ?? 0) + 1;

        $stmt = $db->prepare("
            INSERT INTO workout_types (type_key, name, emoji, color, description, is_rest, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$typeKey, $name, $emoji, $color, $description, $isRest ? 1 : 0, $sortOrder]);

        $id = $db->lastInsertId();

        jsonResponse([
            'success' => true,
            'id' => (int)$id,
            'typeKey' => $typeKey
        ]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            jsonResponse(['success' => false, 'error' => 'Workout type key already exists'], 400);
        } else {
            logError('createWorkoutType failed: ' . $e->getMessage(), ['data' => $data]);
            jsonResponse(['success' => false, 'error' => 'Failed to create workout type'], 500);
        }
    }
}

function updateWorkoutType($data) {
    $typeKey = sanitizeString($data['typeKey'] ?? '', 50);
    $name = sanitizeString($data['name'] ?? '', 100);
    $emoji = sanitizeString($data['emoji'] ?? '', 10);
    $color = sanitizeString($data['color'] ?? '', 20);
    $description = sanitizeString($data['description'] ?? '', 500);
    $isRest = isset($data['isRest']) ? (bool)$data['isRest'] : null;

    if (empty($typeKey)) {
        jsonResponse(['success' => false, 'error' => 'Type key is required'], 400);
        return;
    }

    try {
        $db = getDB();
        ensureFlexibleScheduleTables();

        // Build dynamic update query
        $updates = [];
        $params = [];

        if (!empty($name)) {
            $updates[] = "name = ?";
            $params[] = $name;
        }
        if (!empty($emoji)) {
            $updates[] = "emoji = ?";
            $params[] = $emoji;
        }
        if (!empty($color)) {
            $updates[] = "color = ?";
            $params[] = $color;
        }
        if ($description !== '') {
            $updates[] = "description = ?";
            $params[] = $description;
        }
        if ($isRest !== null) {
            $updates[] = "is_rest = ?";
            $params[] = $isRest ? 1 : 0;
        }

        if (empty($updates)) {
            jsonResponse(['success' => false, 'error' => 'No fields to update'], 400);
            return;
        }

        $params[] = $typeKey;
        $sql = "UPDATE workout_types SET " . implode(", ", $updates) . " WHERE type_key = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'error' => 'Workout type not found'], 404);
            return;
        }

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('updateWorkoutType failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to update workout type'], 500);
    }
}

function deleteWorkoutType($data) {
    $typeKey = sanitizeString($data['typeKey'] ?? '', 50);

    if (empty($typeKey)) {
        jsonResponse(['success' => false, 'error' => 'Type key is required'], 400);
        return;
    }

    // Prevent deleting built-in types
    $builtIn = ['push', 'pull', 'legs', 'upper', 'cardio', 'light_cardio', 'rest'];
    if (in_array($typeKey, $builtIn)) {
        jsonResponse(['success' => false, 'error' => 'Cannot delete built-in workout types'], 400);
        return;
    }

    try {
        $db = getDB();
        ensureFlexibleScheduleTables();

        // Check if type is in use in schedule
        $stmt = $db->prepare("SELECT COUNT(*) FROM weekly_schedule WHERE workout_type_key = ?");
        $stmt->execute([$typeKey]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['success' => false, 'error' => 'Cannot delete - type is assigned to a day'], 400);
            return;
        }

        // Check if type has exercises
        $stmt = $db->prepare("SELECT COUNT(*) FROM workout_exercises WHERE workout_type = ?");
        $stmt->execute([$typeKey]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['success' => false, 'error' => 'Cannot delete - type has exercises. Remove exercises first.'], 400);
            return;
        }

        $stmt = $db->prepare("DELETE FROM workout_types WHERE type_key = ?");
        $stmt->execute([$typeKey]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'error' => 'Workout type not found'], 404);
            return;
        }

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('deleteWorkoutType failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to delete workout type'], 500);
    }
}

function getWeeklySchedule() {
    try {
        $db = getDB();
        ensureFlexibleScheduleTables();

        // Get schedule with workout type details
        $stmt = $db->query("
            SELECT ws.day_of_week, ws.workout_type_key,
                   wt.name, wt.emoji, wt.color, wt.is_rest
            FROM weekly_schedule ws
            LEFT JOIN workout_types wt ON ws.workout_type_key = wt.type_key
            ORDER BY ws.day_of_week
        ");

        $schedule = [];
        foreach ($stmt->fetchAll() as $row) {
            $schedule[(int)$row['day_of_week']] = [
                'type' => $row['workout_type_key'],
                'name' => $row['name'] ?? $row['workout_type_key'],
                'emoji' => $row['emoji'] ?? '💪',
                'color' => $row['color'] ?? 'gray',
                'isRest' => (bool)($row['is_rest'] ?? false)
            ];
        }

        jsonResponse(['success' => true, 'schedule' => $schedule]);
    } catch (PDOException $e) {
        logError('getWeeklySchedule failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to get schedule'], 500);
    }
}

function updateDaySchedule($data) {
    $dayOfWeek = isset($data['dayOfWeek']) ? (int)$data['dayOfWeek'] : -1;
    $typeKey = sanitizeString($data['typeKey'] ?? '', 50);

    if ($dayOfWeek < 0 || $dayOfWeek > 6) {
        jsonResponse(['success' => false, 'error' => 'Invalid day of week (0-6)'], 400);
        return;
    }

    if (empty($typeKey)) {
        jsonResponse(['success' => false, 'error' => 'Workout type key is required'], 400);
        return;
    }

    try {
        $db = getDB();
        ensureFlexibleScheduleTables();

        // Verify type exists
        $stmt = $db->prepare("SELECT id FROM workout_types WHERE type_key = ?");
        $stmt->execute([$typeKey]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'error' => 'Workout type not found'], 404);
            return;
        }

        $stmt = $db->prepare("
            INSERT INTO weekly_schedule (day_of_week, workout_type_key)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE workout_type_key = VALUES(workout_type_key)
        ");
        $stmt->execute([$dayOfWeek, $typeKey]);

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('updateDaySchedule failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to update schedule'], 500);
    }
}

function saveFullSchedule($data) {
    $schedule = $data['schedule'] ?? [];

    if (!is_array($schedule) || count($schedule) !== 7) {
        jsonResponse(['success' => false, 'error' => 'Schedule must have exactly 7 days'], 400);
        return;
    }

    try {
        $db = getDB();
        ensureFlexibleScheduleTables();

        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO weekly_schedule (day_of_week, workout_type_key)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE workout_type_key = VALUES(workout_type_key)
        ");

        for ($day = 0; $day < 7; $day++) {
            $typeKey = sanitizeString($schedule[$day] ?? '', 50);
            if (empty($typeKey)) {
                $db->rollBack();
                jsonResponse(['success' => false, 'error' => "Invalid type for day $day"], 400);
                return;
            }
            $stmt->execute([$day, $typeKey]);
        }

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        logError('saveFullSchedule failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to save schedule'], 500);
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

        // Support both absolute set and increment operations
        // If 'increment' is provided, use atomic increment to prevent race conditions
        $increment = $data['increment'] ?? null;

        if ($increment !== null) {
            // Atomic increment/decrement - prevents race conditions
            $incrementVal = intval($increment);
            $stmt = $db->prepare("
                INSERT INTO water_log (date, glasses)
                VALUES (?, GREATEST(0, ?))
                ON DUPLICATE KEY UPDATE glasses = GREATEST(0, glasses + ?)
            ");
            $stmt->execute([
                $data['date'],
                $incrementVal,
                $incrementVal
            ]);
        } else {
            // Absolute set (original behavior)
            $stmt = $db->prepare("
                INSERT INTO water_log (date, glasses)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE glasses = VALUES(glasses)
            ");
            $stmt->execute([
                $data['date'],
                intval($data['glasses'])
            ]);
        }

        // Return the current value after update
        $stmt = $db->prepare("SELECT glasses FROM water_log WHERE date = ?");
        $stmt->execute([$data['date']]);
        $row = $stmt->fetch();

        jsonResponse(['success' => true, 'glasses' => $row ? intval($row['glasses']) : 0]);
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

// ==================== WEEKLY STATS ====================

function getWeeklyStats($startDate) {
    if (!validateDate($startDate)) {
        jsonResponse(['success' => false, 'error' => 'Invalid start date'], 400);
        return;
    }

    try {
        $db = getDB();

        // Calculate end date (7 days from start)
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));

        // Get meal stats per day
        $stmt = $db->prepare("
            SELECT
                date,
                SUM(calories) as total_calories,
                SUM(protein) as total_protein
            FROM meal_log
            WHERE date >= ? AND date <= ?
            GROUP BY date
        ");
        $stmt->execute([$startDate, $endDate]);
        $mealData = $stmt->fetchAll();

        // Get water stats per day
        $stmt = $db->prepare("
            SELECT
                date,
                glasses
            FROM water_log
            WHERE date >= ? AND date <= ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $waterData = $stmt->fetchAll();

        // Get calories burnt per day from completed exercises
        // Join exercise_log with workout_schedules to get workout type for each date
        // Then join with workout_exercises to get calories for each completed exercise
        // Use COLLATE to handle potential collation mismatch between tables
        $stmt = $db->prepare("
            SELECT
                el.date,
                SUM(COALESCE(we.calories, 0)) as total_burnt
            FROM exercise_log el
            INNER JOIN workout_schedules ws ON DAYOFWEEK(el.date) = ws.day_of_week
            INNER JOIN workout_exercises we ON ws.workout_type COLLATE utf8mb4_unicode_ci = we.workout_type COLLATE utf8mb4_unicode_ci
                AND el.exercise_index = we.exercise_order
            WHERE el.date >= ? AND el.date <= ? AND el.completed = 1
            GROUP BY el.date
        ");
        $stmt->execute([$startDate, $endDate]);
        $burntData = $stmt->fetchAll();

        // Calculate averages
        $totalCalories = 0;
        $totalProtein = 0;
        $daysWithMeals = count($mealData);

        foreach ($mealData as $day) {
            $totalCalories += floatval($day['total_calories']);
            $totalProtein += floatval($day['total_protein']);
        }

        $totalWaterMl = 0;
        $daysWithWater = count($waterData);

        foreach ($waterData as $day) {
            $totalWaterMl += intval($day['glasses']) * 250;
        }

        $totalBurnt = 0;
        $daysWithWorkout = count($burntData);

        foreach ($burntData as $day) {
            $totalBurnt += floatval($day['total_burnt']);
        }

        jsonResponse([
            'success' => true,
            'avgCalories' => $daysWithMeals > 0 ? round($totalCalories / $daysWithMeals) : 0,
            'avgProtein' => $daysWithMeals > 0 ? round($totalProtein / $daysWithMeals) : 0,
            'avgWaterMl' => $daysWithWater > 0 ? round($totalWaterMl / $daysWithWater) : 0,
            'avgCaloriesBurnt' => $daysWithWorkout > 0 ? round($totalBurnt / $daysWithWorkout) : 0,
            'daysWithMeals' => $daysWithMeals,
            'daysWithWater' => $daysWithWater,
            'daysWithWorkout' => $daysWithWorkout
        ]);
    } catch (PDOException $e) {
        logError('getWeeklyStats failed: ' . $e->getMessage(), ['startDate' => $startDate]);
        jsonResponse(['success' => false, 'error' => 'Failed to get weekly stats'], 500);
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

function deleteMealCombo($input) {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'error' => 'Invalid combo ID'], 400);
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM meal_combos WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Combo not found'], 404);
        }
    } catch (PDOException $e) {
        logError('deleteMealCombo failed: ' . $e->getMessage(), ['id' => $id]);
        jsonResponse(['success' => false, 'error' => 'Failed to delete combo'], 500);
    }
}

function hideDefaultCombo($input) {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'error' => 'Invalid combo ID'], 400);
        return;
    }

    // Get current hidden combos
    $hidden = json_decode(getAppSetting('hidden_default_combos') ?? '[]', true) ?: [];

    if (!in_array($id, $hidden)) {
        $hidden[] = $id;
        setAppSetting('hidden_default_combos', json_encode($hidden));
    }

    jsonResponse(['success' => true, 'hidden' => $hidden]);
}

function getHiddenCombos() {
    $hidden = json_decode(getAppSetting('hidden_default_combos') ?? '[]', true) ?: [];
    $defaults = json_decode(getAppSetting('default_combos') ?? '{}', true) ?: [];
    jsonResponse(['success' => true, 'hidden' => $hidden, 'defaults' => $defaults]);
}

function setDefaultCombo($input) {
    $comboId = intval($input['comboId'] ?? 0);
    $category = sanitizeString($input['category'] ?? '', 20);

    $validCategories = ['breakfast', 'lunch', 'dinner', 'snacks', 'cheat'];
    if (!in_array($category, $validCategories)) {
        jsonResponse(['success' => false, 'error' => 'Invalid category'], 400);
        return;
    }

    // Store default combo ID for each category in app_settings
    $defaults = json_decode(getAppSetting('default_combos') ?? '{}', true) ?: [];
    $defaults[$category] = $comboId;
    setAppSetting('default_combos', json_encode($defaults));

    jsonResponse(['success' => true, 'defaults' => $defaults]);
}

// ==================== EDIT MODE / PASSWORD FUNCTIONS ====================

function getAppSetting($key) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

function setAppSetting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function verifyEditPassword($data) {
    $password = $data['password'] ?? '';
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (empty($password)) {
        jsonResponse(['success' => false, 'error' => 'Password is required'], 400);
        return;
    }

    // Check rate limit for password attempts (stricter: 5 attempts per 15 minutes)
    checkAuthRateLimit($client_ip);

    try {
        $hash = getAppSetting('edit_password_hash');

        if (!$hash) {
            // No password set yet - first time setup
            // Require minimum 8 characters for initial setup
            if (strlen($password) < 8) {
                jsonResponse(['success' => false, 'error' => 'Password must be at least 8 characters'], 400);
                return;
            }

            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            setAppSetting('edit_password_hash', $newHash);

            // Generate edit token
            $token = bin2hex(random_bytes(32));
            jsonResponse([
                'success' => true,
                'valid' => true,
                'token' => $token,
                'message' => 'Password set successfully'
            ]);
            return;
        }

        $valid = password_verify($password, $hash);

        if ($valid) {
            // Reset failed attempts on successful login
            resetAuthRateLimit($client_ip);

            // Generate a simple edit token (valid for this session)
            $token = bin2hex(random_bytes(32));
            jsonResponse(['success' => true, 'valid' => true, 'token' => $token]);
        } else {
            // Log failed attempt
            logError('Failed password attempt', ['ip' => $client_ip]);
            jsonResponse(['success' => true, 'valid' => false]);
        }
    } catch (Exception $e) {
        logError('verifyEditPassword failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Password verification failed'], 500);
    }
}

// Stricter rate limiting for authentication
function checkAuthRateLimit($identifier) {
    $cache_dir = __DIR__ . '/cache';
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }

    $cache_file = $cache_dir . '/auth_' . md5($identifier) . '.json';
    $now = time();
    $max_attempts = 5;
    $window_seconds = 900; // 15 minutes

    $data = ['attempts' => []];
    if (file_exists($cache_file)) {
        $content = @file_get_contents($cache_file);
        if ($content) {
            $data = json_decode($content, true) ?? ['attempts' => []];
        }
    }

    // Remove expired timestamps
    $data['attempts'] = array_values(array_filter($data['attempts'] ?? [], function($ts) use ($now, $window_seconds) {
        return $ts > ($now - $window_seconds);
    }));

    // Check if limit exceeded
    if (count($data['attempts']) >= $max_attempts) {
        $retry_after = $window_seconds - ($now - min($data['attempts']));
        http_response_code(429);
        header('Retry-After: ' . $retry_after);
        echo json_encode([
            'success' => false,
            'error' => 'Too many password attempts. Please wait ' . ceil($retry_after / 60) . ' minutes.',
            'retry_after' => $retry_after
        ]);
        exit();
    }

    // Add current attempt
    $data['attempts'][] = $now;
    @file_put_contents($cache_file, json_encode($data));
}

function resetAuthRateLimit($identifier) {
    $cache_file = __DIR__ . '/cache/auth_' . md5($identifier) . '.json';
    if (file_exists($cache_file)) {
        @unlink($cache_file);
    }
}

function changeEditPassword($data) {
    $currentPassword = $data['currentPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        jsonResponse(['success' => false, 'error' => 'Both current and new passwords are required'], 400);
        return;
    }

    if (strlen($newPassword) < 8) {
        jsonResponse(['success' => false, 'error' => 'New password must be at least 8 characters'], 400);
        return;
    }

    try {
        $hash = getAppSetting('edit_password_hash');

        if (!$hash || !password_verify($currentPassword, $hash)) {
            jsonResponse(['success' => false, 'error' => 'Current password is incorrect']);
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        setAppSetting('edit_password_hash', $newHash);

        jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
    } catch (Exception $e) {
        logError('changeEditPassword failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to change password'], 500);
    }
}

// ==================== WORKOUT FUNCTIONS ====================

function getWorkoutSchedule() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT day_of_week, workout_type, name, emoji, color FROM workout_schedules ORDER BY day_of_week ASC");
        $rows = $stmt->fetchAll();

        $schedule = [];
        foreach ($rows as $row) {
            $schedule[$row['day_of_week']] = [
                'type' => $row['workout_type'],
                'name' => $row['name'],
                'emoji' => $row['emoji'],
                'color' => $row['color']
            ];
        }

        jsonResponse(['success' => true, 'schedule' => $schedule]);
    } catch (PDOException $e) {
        logError('getWorkoutSchedule failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load workout schedule'], 500);
    }
}

function getWorkoutExercises($workoutType) {
    if (empty($workoutType)) {
        jsonResponse(['success' => false, 'error' => 'Workout type is required'], 400);
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, exercise_order, name, sets, reps, rest, notes, calories,
                   is_challenge, pr_type, pr_unit, is_rest, is_optional
            FROM workout_exercises
            WHERE workout_type = ?
            ORDER BY exercise_order ASC
        ");
        $stmt->execute([sanitizeString($workoutType, 50)]);
        $rows = $stmt->fetchAll();

        $exercises = array_map(function($row) {
            return [
                'id' => intval($row['id']),
                'order' => intval($row['exercise_order']),
                'name' => $row['name'],
                'sets' => $row['sets'],
                'reps' => $row['reps'],
                'rest' => $row['rest'],
                'notes' => $row['notes'],
                'calories' => intval($row['calories']),
                'isChallenge' => (bool)$row['is_challenge'],
                'prType' => $row['pr_type'],
                'prUnit' => $row['pr_unit'],
                'isRest' => (bool)$row['is_rest'],
                'isOptional' => (bool)$row['is_optional']
            ];
        }, $rows);

        jsonResponse(['success' => true, 'exercises' => $exercises]);
    } catch (PDOException $e) {
        logError('getWorkoutExercises failed: ' . $e->getMessage(), ['workoutType' => $workoutType]);
        jsonResponse(['success' => false, 'error' => 'Failed to load exercises'], 500);
    }
}

function getAllWorkouts() {
    try {
        $db = getDB();

        // Get schedule
        $stmt = $db->query("SELECT day_of_week, workout_type, name, emoji, color FROM workout_schedules ORDER BY day_of_week ASC");
        $scheduleRows = $stmt->fetchAll();

        $schedule = [];
        foreach ($scheduleRows as $row) {
            $schedule[$row['day_of_week']] = [
                'type' => $row['workout_type'],
                'name' => $row['name'],
                'emoji' => $row['emoji'],
                'color' => $row['color']
            ];
        }

        // Get all exercises grouped by workout type
        $stmt = $db->query("
            SELECT id, workout_type, exercise_order, name, sets, reps, rest, notes, calories,
                   is_challenge, pr_type, pr_unit, is_rest, is_optional
            FROM workout_exercises
            ORDER BY workout_type, exercise_order ASC
        ");
        $exerciseRows = $stmt->fetchAll();

        $workoutDetails = [];
        foreach ($exerciseRows as $row) {
            $type = $row['workout_type'];
            if (!isset($workoutDetails[$type])) {
                $workoutDetails[$type] = [
                    'exercises' => [],
                    'totalCalories' => 0
                ];
            }

            $exercise = [
                'id' => intval($row['id']),
                'name' => $row['name'],
                'sets' => $row['sets'],
                'reps' => $row['reps'],
                'rest' => $row['rest'],
                'notes' => $row['notes'],
                'calories' => intval($row['calories']),
                'isChallenge' => (bool)$row['is_challenge'],
                'prType' => $row['pr_type'],
                'prUnit' => $row['pr_unit'],
                'isRest' => (bool)$row['is_rest'],
                'isOptional' => (bool)$row['is_optional']
            ];

            $workoutDetails[$type]['exercises'][] = $exercise;
            $workoutDetails[$type]['totalCalories'] += intval($row['calories']);
        }

        jsonResponse([
            'success' => true,
            'schedule' => $schedule,
            'workoutDetails' => $workoutDetails
        ]);
    } catch (PDOException $e) {
        logError('getAllWorkouts failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load workouts'], 500);
    }
}

function updateExercise($data) {
    $id = intval($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'error' => 'Invalid exercise ID'], 400);
        return;
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("
            UPDATE workout_exercises SET
                name = ?,
                sets = ?,
                reps = ?,
                rest = ?,
                notes = ?,
                calories = ?,
                is_challenge = ?,
                pr_type = ?,
                pr_unit = ?,
                is_rest = ?,
                is_optional = ?
            WHERE id = ?
        ");

        $stmt->execute([
            sanitizeString($data['name'] ?? '', 255),
            sanitizeString($data['sets'] ?? '', 50),
            sanitizeString($data['reps'] ?? '', 50),
            sanitizeString($data['rest'] ?? '', 50),
            sanitizeString($data['notes'] ?? '', 1000),
            intval($data['calories'] ?? 0),
            ($data['isChallenge'] ?? false) ? 1 : 0,
            in_array($data['prType'] ?? null, ['reps', 'time', 'weight', null]) ? ($data['prType'] ?: null) : null,
            sanitizeString($data['prUnit'] ?? '', 20) ?: null,
            ($data['isRest'] ?? false) ? 1 : 0,
            ($data['isOptional'] ?? false) ? 1 : 0,
            $id
        ]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'error' => 'Exercise not found'], 404);
            return;
        }

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('updateExercise failed: ' . $e->getMessage(), ['id' => $id]);
        jsonResponse(['success' => false, 'error' => 'Failed to update exercise'], 500);
    }
}

function addExercise($data) {
    $workoutType = sanitizeString($data['workoutType'] ?? '', 50);
    if (empty($workoutType)) {
        jsonResponse(['success' => false, 'error' => 'Workout type is required'], 400);
        return;
    }

    try {
        $db = getDB();

        // Get max order for this workout type
        $stmt = $db->prepare("SELECT MAX(exercise_order) as max_order FROM workout_exercises WHERE workout_type = ?");
        $stmt->execute([$workoutType]);
        $row = $stmt->fetch();
        $nextOrder = ($row['max_order'] ?? 0) + 1;

        $stmt = $db->prepare("
            INSERT INTO workout_exercises
            (workout_type, exercise_order, name, sets, reps, rest, notes, calories, is_challenge, pr_type, pr_unit, is_rest, is_optional)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $workoutType,
            $nextOrder,
            sanitizeString($data['name'] ?? 'New Exercise', 255),
            sanitizeString($data['sets'] ?? '3', 50),
            sanitizeString($data['reps'] ?? '10', 50),
            sanitizeString($data['rest'] ?? '30s', 50),
            sanitizeString($data['notes'] ?? '', 1000),
            intval($data['calories'] ?? 0),
            ($data['isChallenge'] ?? false) ? 1 : 0,
            in_array($data['prType'] ?? null, ['reps', 'time', 'weight', null]) ? ($data['prType'] ?: null) : null,
            sanitizeString($data['prUnit'] ?? '', 20) ?: null,
            ($data['isRest'] ?? false) ? 1 : 0,
            ($data['isOptional'] ?? false) ? 1 : 0
        ]);

        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        logError('addExercise failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to add exercise'], 500);
    }
}

function deleteExercise($data) {
    $id = intval($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'error' => 'Invalid exercise ID'], 400);
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM workout_exercises WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'error' => 'Exercise not found'], 404);
            return;
        }

        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        logError('deleteExercise failed: ' . $e->getMessage(), ['id' => $id]);
        jsonResponse(['success' => false, 'error' => 'Failed to delete exercise'], 500);
    }
}

function reorderExercises($data) {
    $workoutType = sanitizeString($data['workoutType'] ?? '', 50);
    $exerciseIds = $data['exerciseIds'] ?? [];

    if (empty($workoutType) || empty($exerciseIds) || !is_array($exerciseIds)) {
        jsonResponse(['success' => false, 'error' => 'Workout type and exercise IDs are required'], 400);
        return;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE workout_exercises SET exercise_order = ? WHERE id = ? AND workout_type = ?");

        $order = 1;
        foreach ($exerciseIds as $id) {
            $stmt->execute([$order, intval($id), $workoutType]);
            $order++;
        }

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        $db->rollBack();
        logError('reorderExercises failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to reorder exercises'], 500);
    }
}

function saveWorkout($data) {
    $workoutType = sanitizeString($data['workoutType'] ?? '', 50);
    $exercises = $data['exercises'] ?? [];

    if (empty($workoutType)) {
        jsonResponse(['success' => false, 'error' => 'Workout type is required'], 400);
        return;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        // Get current exercise IDs for this workout type
        $stmt = $db->prepare("SELECT id FROM workout_exercises WHERE workout_type = ?");
        $stmt->execute([$workoutType]);
        $existingIds = array_column($stmt->fetchAll(), 'id');

        // Track which IDs we're keeping
        $keepIds = [];

        // Prepare statements
        $insertStmt = $db->prepare("
            INSERT INTO workout_exercises
            (workout_type, exercise_order, name, sets, reps, rest, notes, calories, is_challenge, pr_type, pr_unit, is_rest, is_optional)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $updateStmt = $db->prepare("
            UPDATE workout_exercises SET
                exercise_order = ?,
                name = ?,
                sets = ?,
                reps = ?,
                rest = ?,
                notes = ?,
                calories = ?,
                is_challenge = ?,
                pr_type = ?,
                pr_unit = ?,
                is_rest = ?,
                is_optional = ?
            WHERE id = ? AND workout_type = ?
        ");

        $order = 1;
        foreach ($exercises as $ex) {
            $id = isset($ex['id']) ? intval($ex['id']) : 0;
            $name = sanitizeString($ex['name'] ?? '', 255);
            $sets = sanitizeString($ex['sets'] ?? '3', 50);
            $reps = sanitizeString($ex['reps'] ?? '10', 50);
            $rest = sanitizeString($ex['rest'] ?? '30s', 50);
            $notes = sanitizeString($ex['notes'] ?? '', 1000);
            $calories = intval($ex['calories'] ?? 0);
            $isChallenge = ($ex['is_challenge'] ?? $ex['isChallenge'] ?? false) ? 1 : 0;
            $prType = in_array($ex['pr_type'] ?? $ex['prType'] ?? null, ['reps', 'time', 'weight', null]) ? ($ex['pr_type'] ?? $ex['prType'] ?? null) : null;
            $prUnit = sanitizeString($ex['pr_unit'] ?? $ex['prUnit'] ?? '', 20) ?: null;
            $isRest = ($ex['is_rest'] ?? $ex['isRest'] ?? false) ? 1 : 0;
            $isOptional = ($ex['is_optional'] ?? $ex['isOptional'] ?? false) ? 1 : 0;

            if ($id > 0 && in_array($id, $existingIds)) {
                // Update existing exercise
                $updateStmt->execute([
                    $order,
                    $name,
                    $sets,
                    $reps,
                    $rest,
                    $notes,
                    $calories,
                    $isChallenge,
                    $prType,
                    $prUnit,
                    $isRest,
                    $isOptional,
                    $id,
                    $workoutType
                ]);
                $keepIds[] = $id;
            } else {
                // Insert new exercise
                $insertStmt->execute([
                    $workoutType,
                    $order,
                    $name,
                    $sets,
                    $reps,
                    $rest,
                    $notes,
                    $calories,
                    $isChallenge,
                    $prType,
                    $prUnit,
                    $isRest,
                    $isOptional
                ]);
                $keepIds[] = $db->lastInsertId();
            }
            $order++;
        }

        // Delete exercises that were removed
        $deleteIds = array_diff($existingIds, $keepIds);
        if (!empty($deleteIds)) {
            $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
            $deleteStmt = $db->prepare("DELETE FROM workout_exercises WHERE id IN ($placeholders) AND workout_type = ?");
            $deleteStmt->execute([...array_values($deleteIds), $workoutType]);
        }

        $db->commit();
        jsonResponse(['success' => true, 'message' => 'Workout saved successfully']);
    } catch (PDOException $e) {
        $db->rollBack();
        logError('saveWorkout failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to save workout'], 500);
    }
}

// ==================== EXERCISE LIBRARY FUNCTIONS ====================

function getExerciseLibrary($category, $search) {
    try {
        $db = getDB();

        $sql = "SELECT * FROM exercise_library WHERE 1=1";
        $params = [];

        // Filter by category
        if (!empty($category) && $category !== 'all') {
            // Use sanitizeForDB for database queries (PDO handles SQL injection)
            $sql .= " AND category = ?";
            $params[] = sanitizeForDB($category, 100);
        }

        // Search by name - use sanitizeForDB to preserve special chars like "&"
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR primary_muscles LIKE ? OR equipment LIKE ?)";
            $searchTerm = "%" . sanitizeForDB($search, 100) . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY category, name ASC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $exercises = array_map(function($row) {
            return [
                'id' => intval($row['id']),
                'name' => $row['name'],
                'category' => $row['category'],
                'type' => $row['type'],
                'primaryMuscles' => $row['primary_muscles'],
                'secondaryMuscles' => $row['secondary_muscles'],
                'equipment' => $row['equipment'],
                'difficulty' => $row['difficulty'],
                'caloriesPer30Min' => intval($row['calories_per_30min']),
                'setsRecommended' => $row['sets_recommended'],
                'repsRecommended' => $row['reps_recommended'],
                'restSeconds' => intval($row['rest_seconds']),
                'instructions' => $row['instructions'],
                'tips' => $row['tips']
            ];
        }, $rows);

        jsonResponse(['success' => true, 'exercises' => $exercises]);
    } catch (PDOException $e) {
        logError('getExerciseLibrary failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load exercise library'], 500);
    }
}

function getExerciseLibraryCategories() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT DISTINCT category FROM exercise_library ORDER BY category ASC");
        $rows = $stmt->fetchAll();

        $categories = array_map(function($row) {
            return $row['category'];
        }, $rows);

        jsonResponse(['success' => true, 'categories' => $categories]);
    } catch (PDOException $e) {
        logError('getExerciseLibraryCategories failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load categories'], 500);
    }
}

function addToExerciseLibrary($data) {
    $name = sanitizeString($data['name'] ?? '', 255);
    if (empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Exercise name is required'], 400);
        return;
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO exercise_library
            (name, category, type, primary_muscles, secondary_muscles, equipment, difficulty,
             calories_per_30min, sets_recommended, reps_recommended, rest_seconds, instructions, tips)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            sanitizeString($data['category'] ?? 'Cardio', 50),
            sanitizeString($data['type'] ?? 'Cardio', 50),
            sanitizeString($data['primaryMuscles'] ?? '', 255),
            sanitizeString($data['secondaryMuscles'] ?? '', 255),
            sanitizeString($data['equipment'] ?? 'None', 100),
            sanitizeString($data['difficulty'] ?? 'Beginner', 50),
            intval($data['caloriesPer30Min'] ?? 100),
            sanitizeString($data['setsRecommended'] ?? '—', 50),
            sanitizeString($data['repsRecommended'] ?? '20-30 min', 50),
            intval($data['restSeconds'] ?? 0),
            sanitizeString($data['instructions'] ?? '', 1000),
            sanitizeString($data['tips'] ?? '', 500)
        ]);

        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        logError('addToExerciseLibrary failed: ' . $e->getMessage(), ['data' => $data]);
        jsonResponse(['success' => false, 'error' => 'Failed to add exercise to library'], 500);
    }
}
