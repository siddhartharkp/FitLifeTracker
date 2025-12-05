<?php
/**
 * FitLife Tracker - Workout Migration Script
 * Migrates hardcoded workouts from JavaScript to database
 * Run ONCE after setup.php creates the new tables
 */

// Security check
$secret_key = 'fitlife_migrate_2025';
if (($_GET['key'] ?? '') !== $secret_key) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Access denied']));
}

require_once 'config.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    // Default edit password (you should change this!)
    $defaultPassword = 'fitlife2025';
    $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insert app settings
    $stmt = $db->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute(['edit_password_hash', $passwordHash]);

    // Weekly workout schedule (0 = Monday, 6 = Sunday)
    $schedules = [
        ['day_of_week' => 0, 'workout_type' => 'push', 'name' => 'Push + Core', 'emoji' => '💪', 'color' => 'orange'],
        ['day_of_week' => 1, 'workout_type' => 'pull', 'name' => 'Pull', 'emoji' => '🏋️', 'color' => 'blue'],
        ['day_of_week' => 2, 'workout_type' => 'legs', 'name' => 'Legs', 'emoji' => '🦵', 'color' => 'purple'],
        ['day_of_week' => 3, 'workout_type' => 'upper', 'name' => 'Upper Var.', 'emoji' => '🔄', 'color' => 'teal'],
        ['day_of_week' => 4, 'workout_type' => 'cardio', 'name' => 'Light Cardio', 'emoji' => '🏃', 'color' => 'green'],
        ['day_of_week' => 5, 'workout_type' => 'rest', 'name' => 'Rest', 'emoji' => '😴', 'color' => 'gray'],
        ['day_of_week' => 6, 'workout_type' => 'rest', 'name' => 'Rest', 'emoji' => '😴', 'color' => 'gray']
    ];

    // Insert schedules
    $stmt = $db->prepare("INSERT INTO workout_schedules (day_of_week, workout_type, name, emoji, color)
                          VALUES (?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE workout_type = VALUES(workout_type),
                          name = VALUES(name), emoji = VALUES(emoji), color = VALUES(color)");

    foreach ($schedules as $schedule) {
        $stmt->execute([
            $schedule['day_of_week'],
            $schedule['workout_type'],
            $schedule['name'],
            $schedule['emoji'],
            $schedule['color']
        ]);
    }

    // All exercises by workout type
    $exercises = [
        'push' => [
            ['name' => 'Push-ups', 'sets' => '100 total', 'reps' => 'For time', 'rest' => '—', 'notes' => 'Your challenge — beat the clock!', 'calories' => 100, 'is_challenge' => 1, 'pr_type' => 'time', 'pr_unit' => 'seconds', 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Rest & Recover', 'sets' => '—', 'reps' => '3-5 min', 'rest' => '—', 'notes' => 'Let heart rate settle', 'calories' => 0, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 1, 'is_optional' => 0],
            ['name' => 'Plank', 'sets' => '3', 'reps' => '30-45s', 'rest' => '30s', 'notes' => 'Core — keep body straight', 'calories' => 40, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Dead Bug', 'sets' => '3', 'reps' => '10 each side', 'rest' => '30s', 'notes' => 'Core stability — slow & controlled', 'calories' => 35, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Mountain Climbers', 'sets' => '3', 'reps' => '20 reps', 'rest' => '30s', 'notes' => 'Core + cardio finish', 'calories' => 75, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0]
        ],
        'pull' => [
            ['name' => 'Pull-ups', 'sets' => '30 total', 'reps' => 'For time', 'rest' => '—', 'notes' => 'Your challenge — beat the clock!', 'calories' => 80, 'is_challenge' => 1, 'pr_type' => 'time', 'pr_unit' => 'seconds', 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Rest & Recover', 'sets' => '—', 'reps' => '2-3 min', 'rest' => '—', 'notes' => 'Shake out arms', 'calories' => 0, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 1, 'is_optional' => 0],
            ['name' => 'Seated Band Rows', 'sets' => '3', 'reps' => '15-20', 'rest' => '45s', 'notes' => 'Sit on floor, loop band around feet, pull to belly', 'calories' => 35, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Bent-Over Band Rows', 'sets' => '3', 'reps' => '12-15', 'rest' => '45s', 'notes' => 'Stand on band, hinge forward, row to chest', 'calories' => 40, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Band Face Pulls', 'sets' => '3', 'reps' => '15-20', 'rest' => '30s', 'notes' => 'Pull band to face level, squeeze rear delts', 'calories' => 30, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Band Bicep Curls', 'sets' => '3', 'reps' => '15', 'rest' => '30s', 'notes' => 'Stand on band, curl with control', 'calories' => 25, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Superman Hold', 'sets' => '3', 'reps' => '20s', 'rest' => '30s', 'notes' => 'Lower back — squeeze glutes', 'calories' => 35, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0]
        ],
        'legs' => [
            ['name' => 'Squats', 'sets' => 'Max reps', 'reps' => 'in 60s', 'rest' => '—', 'notes' => 'Your challenge — beat the count!', 'calories' => 60, 'is_challenge' => 1, 'pr_type' => 'reps', 'pr_unit' => 'reps', 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Jump Squats', 'sets' => '3', 'reps' => '20', 'rest' => '45s', 'notes' => 'Explosive — land soft', 'calories' => 70, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Lunges', 'sets' => '3', 'reps' => '10 each leg', 'rest' => '45s', 'notes' => 'Keep torso upright', 'calories' => 50, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Hip Thrusts', 'sets' => '3', 'reps' => '20', 'rest' => '45s', 'notes' => 'Squeeze glutes at top', 'calories' => 40, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Single-Leg RDL', 'sets' => '3', 'reps' => '10 each', 'rest' => '45s', 'notes' => 'Hamstring focus — balance', 'calories' => 40, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Calf Raises', 'sets' => '3', 'reps' => '20', 'rest' => '30s', 'notes' => 'Slow, full range of motion', 'calories' => 40, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0]
        ],
        'upper' => [
            ['name' => 'Pike Push-ups', 'sets' => '3', 'reps' => '8-12', 'rest' => '60s', 'notes' => 'Shoulders — stop 2-3 reps before failure', 'calories' => 40, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Diamond Push-ups', 'sets' => '3', 'reps' => '10-15', 'rest' => '60s', 'notes' => 'Triceps — NOT to failure', 'calories' => 40, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Wide Push-ups', 'sets' => '2', 'reps' => '15', 'rest' => '45s', 'notes' => 'Chest stretch — easy effort', 'calories' => 30, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Tricep Dips', 'sets' => '3', 'reps' => '12', 'rest' => '45s', 'notes' => 'Chair dips — control the descent', 'calories' => 40, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Shoulder Taps', 'sets' => '3', 'reps' => '10 each', 'rest' => '30s', 'notes' => 'Plank position — minimize hip sway', 'calories' => 30, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0]
        ],
        'cardio' => [
            ['name' => 'Brisk Walking', 'sets' => '—', 'reps' => '20-30 min', 'rest' => '—', 'notes' => 'Outdoor walk — keep good pace', 'calories' => 100, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Jump Rope / Skipping', 'sets' => '3', 'reps' => '2-3 min', 'rest' => '1 min', 'notes' => 'Light effort — don\'t push hard', 'calories' => 60, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Stretching', 'sets' => '—', 'reps' => '10 min', 'rest' => '—', 'notes' => 'Focus on legs and hips', 'calories' => 25, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 0],
            ['name' => 'Deep Breathing', 'sets' => '—', 'reps' => '5 min', 'rest' => '—', 'notes' => 'Relax & recover', 'calories' => 15, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 1]
        ],
        'rest' => [
            ['name' => 'Light Walking', 'sets' => '—', 'reps' => '15-20 min', 'rest' => '—', 'notes' => 'Optional — active recovery', 'calories' => 60, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 1],
            ['name' => 'Stretching', 'sets' => '—', 'reps' => '10 min', 'rest' => '—', 'notes' => 'Focus on tight areas', 'calories' => 25, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 1],
            ['name' => 'Foam Rolling', 'sets' => '—', 'reps' => '5-10 min', 'rest' => '—', 'notes' => 'If available', 'calories' => 15, 'is_challenge' => 0, 'pr_type' => null, 'pr_unit' => null, 'is_rest' => 0, 'is_optional' => 1]
        ]
    ];

    // Clear existing exercises first (for re-migration)
    $db->exec("DELETE FROM workout_exercises");

    // Insert exercises
    $stmt = $db->prepare("INSERT INTO workout_exercises
                          (workout_type, exercise_order, name, sets, reps, rest, notes, calories, is_challenge, pr_type, pr_unit, is_rest, is_optional)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $exerciseCount = 0;
    foreach ($exercises as $workoutType => $workoutExercises) {
        $order = 1;
        foreach ($workoutExercises as $exercise) {
            $stmt->execute([
                $workoutType,
                $order,
                $exercise['name'],
                $exercise['sets'],
                $exercise['reps'],
                $exercise['rest'],
                $exercise['notes'],
                $exercise['calories'],
                $exercise['is_challenge'],
                $exercise['pr_type'],
                $exercise['pr_unit'],
                $exercise['is_rest'],
                $exercise['is_optional']
            ]);
            $order++;
            $exerciseCount++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migration complete!',
        'schedules_created' => count($schedules),
        'exercises_created' => $exerciseCount,
        'default_password' => $defaultPassword,
        'note' => 'IMPORTANT: Change the default password after first login!'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Migration failed: ' . $e->getMessage()
    ]);
}
