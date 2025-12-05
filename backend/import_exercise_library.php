<?php
/**
 * FitLife Tracker - Exercise Library Import Script
 * Imports exercises from CSV into the exercise_library table
 * Run ONCE after setup.php creates the table
 */

// Security check
$secret_key = 'fitlife_import_exercises_2025';
if (($_GET['key'] ?? '') !== $secret_key) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Access denied']));
}

require_once 'config.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    // Exercise data from CSV (excluding comment lines starting with #)
    $exercises = [
        // CHEST EXERCISES
        ['Barbell Bench Press', 'Chest', 'Compound', 'Pectoralis Major', 'Triceps; Anterior Deltoid', 'Barbell; Bench', 'Intermediate', 108, '4', '8-12', 90, 'Lie on bench grip bar slightly wider than shoulders. Lower to chest then press up.', 'Keep feet flat; arch lower back slightly; don\'t bounce bar off chest'],
        ['Incline Barbell Bench Press', 'Chest', 'Compound', 'Upper Chest', 'Triceps; Anterior Deltoid', 'Barbell; Incline Bench', 'Intermediate', 108, '4', '8-12', 90, 'Set bench to 30-45 degrees. Lower bar to upper chest then press up.', 'Targets upper chest fibers; use slightly narrower grip than flat bench'],
        ['Decline Barbell Bench Press', 'Chest', 'Compound', 'Lower Chest', 'Triceps; Anterior Deltoid', 'Barbell; Decline Bench', 'Intermediate', 108, '3', '8-12', 90, 'Secure legs at top of decline bench. Lower bar to lower chest then press up.', 'Targets lower chest fibers; use a spotter for safety'],
        ['Dumbbell Bench Press', 'Chest', 'Compound', 'Pectoralis Major', 'Triceps; Anterior Deltoid', 'Dumbbells; Bench', 'Beginner', 108, '4', '8-12', 90, 'Lie on bench with dumbbells at chest level. Press up and bring together at top.', 'Greater range of motion than barbell; allows more natural arm path'],
        ['Incline Dumbbell Press', 'Chest', 'Compound', 'Upper Chest', 'Triceps; Anterior Deltoid', 'Dumbbells; Incline Bench', 'Beginner', 108, '4', '8-12', 90, 'Set bench to 30-45 degrees. Press dumbbells up from shoulder level.', 'Don\'t go too steep (>45) or shoulders take over'],
        ['Dumbbell Flyes', 'Chest', 'Isolation', 'Pectoralis Major', 'Anterior Deltoid', 'Dumbbells; Bench', 'Beginner', 90, '3', '10-15', 60, 'Lie on bench arms extended. Lower dumbbells in arc until chest stretch then squeeze back up.', 'Keep slight bend in elbows; don\'t go too deep to protect shoulders'],
        ['Cable Crossover', 'Chest', 'Isolation', 'Pectoralis Major', 'Anterior Deltoid', 'Cable Machine', 'Intermediate', 90, '3', '12-15', 60, 'Stand between cable stacks. Bring handles together in front of chest in arc motion.', 'Lean slightly forward; squeeze chest at bottom; vary height for different angles'],
        ['Push-ups (Standard)', 'Chest', 'Compound', 'Pectoralis Major', 'Triceps; Anterior Deltoid; Core', 'Bodyweight', 'Beginner', 108, '3', '10-20', 45, 'Start in plank position hands shoulder-width. Lower chest to ground then push up.', 'Keep body straight; don\'t let hips sag; elbows at 45 degrees'],
        ['Diamond Push-ups', 'Chest', 'Compound', 'Inner Chest; Triceps', 'Anterior Deltoid', 'Bodyweight', 'Intermediate', 108, '3', '8-15', 45, 'Form diamond shape with hands under chest. Lower and push up.', 'More triceps emphasis; harder than standard push-ups'],
        ['Wide Push-ups', 'Chest', 'Compound', 'Outer Chest', 'Triceps; Anterior Deltoid', 'Bodyweight', 'Beginner', 108, '3', '10-20', 45, 'Place hands wider than shoulder-width. Lower and push up.', 'Emphasizes chest stretch; easier than standard push-ups'],
        ['Decline Push-ups', 'Chest', 'Compound', 'Upper Chest; Shoulders', 'Triceps', 'Bodyweight', 'Intermediate', 108, '3', '10-15', 45, 'Place feet on elevated surface hands on ground. Lower chest and push up.', 'Targets upper chest; higher elevation = harder'],
        ['Chest Dips', 'Chest', 'Compound', 'Lower Chest', 'Triceps; Anterior Deltoid', 'Dip Bars', 'Intermediate', 126, '3', '8-12', 60, 'Lean forward on dip bars. Lower until upper arms parallel then press up.', 'Lean forward for chest focus; upright for triceps'],

        // BACK EXERCISES
        ['Barbell Deadlift', 'Back', 'Compound', 'Erector Spinae; Glutes', 'Hamstrings; Lats; Traps; Forearms', 'Barbell', 'Advanced', 180, '4', '5-8', 120, 'Stand with feet hip-width bar over mid-foot. Hinge and grip bar. Drive through heels to stand.', 'Keep back flat; bar close to body; don\'t round lower back'],
        ['Romanian Deadlift', 'Back', 'Compound', 'Hamstrings; Glutes', 'Erector Spinae', 'Barbell; Dumbbells', 'Intermediate', 162, '3', '8-12', 90, 'Hold bar at hips. Push hips back lowering bar along legs until hamstring stretch.', 'Keep slight knee bend; feel hamstring stretch; don\'t round back'],
        ['Barbell Row (Bent Over)', 'Back', 'Compound', 'Lats; Rhomboids', 'Biceps; Rear Deltoid; Erector Spinae', 'Barbell', 'Intermediate', 126, '4', '8-12', 90, 'Hinge forward 45 degrees. Pull bar to lower chest squeezing back.', 'Keep back flat; pull with elbows not hands; squeeze at top'],
        ['One-Arm Dumbbell Row', 'Back', 'Compound', 'Lats', 'Biceps; Rhomboids; Rear Deltoid', 'Dumbbell; Bench', 'Beginner', 108, '3', '10-12 each', 60, 'Place one hand and knee on bench. Row dumbbell to hip.', 'Keep back flat; pull to hip not chest; don\'t rotate torso'],
        ['Seated Cable Row', 'Back', 'Compound', 'Lats; Rhomboids', 'Biceps; Rear Deltoid', 'Cable Machine', 'Beginner', 126, '4', '10-12', 60, 'Sit with feet on platform. Pull handle to abdomen squeezing back.', 'Keep torso still; don\'t lean back too far; squeeze shoulder blades'],
        ['Lat Pulldown', 'Back', 'Compound', 'Lats', 'Biceps; Rhomboids; Rear Deltoid', 'Cable Machine', 'Beginner', 126, '4', '10-12', 60, 'Grip bar wide. Pull down to upper chest squeezing lats.', 'Lean back slightly; pull elbows to sides; don\'t use momentum'],
        ['Pull-ups', 'Back', 'Compound', 'Lats; Rhomboids', 'Biceps; Rear Deltoid; Core', 'Pull-up Bar', 'Advanced', 126, '3', '6-12', 90, 'Hang with overhand grip. Pull chin above bar then lower with control.', 'Full range of motion; avoid kipping; add weight when easy'],
        ['Chin-ups', 'Back', 'Compound', 'Lats; Biceps', 'Rhomboids; Rear Deltoid', 'Pull-up Bar', 'Intermediate', 126, '3', '6-12', 90, 'Hang with underhand grip. Pull chin above bar.', 'More biceps emphasis than pull-ups; slightly easier'],
        ['Face Pulls', 'Back', 'Isolation', 'Rear Deltoid; Rhomboids', 'Rotator Cuff; Traps', 'Cable Machine; Resistance Band', 'Beginner', 90, '3', '15-20', 45, 'Pull rope to face with elbows high. Spread rope at end position.', 'Excellent for posture and shoulder health; high reps'],
        ['Hyperextensions', 'Back', 'Isolation', 'Erector Spinae', 'Glutes; Hamstrings', 'Hyperextension Bench', 'Beginner', 90, '3', '12-15', 45, 'Position hips on pad. Lower torso then raise until body straight.', 'Don\'t hyperextend; squeeze glutes at top; add weight for progression'],

        // SHOULDER EXERCISES
        ['Overhead Press (Barbell)', 'Shoulders', 'Compound', 'Anterior Deltoid; Lateral Deltoid', 'Triceps; Upper Chest; Core', 'Barbell', 'Intermediate', 126, '4', '6-10', 90, 'Stand with bar at shoulders. Press overhead until arms locked.', 'Keep core tight; don\'t lean back excessively; full lockout'],
        ['Seated Dumbbell Press', 'Shoulders', 'Compound', 'Anterior Deltoid; Lateral Deltoid', 'Triceps', 'Dumbbells; Bench', 'Beginner', 126, '4', '8-12', 90, 'Sit with back support dumbbells at shoulders. Press overhead.', 'More stable than standing; allows heavier weight'],
        ['Arnold Press', 'Shoulders', 'Compound', 'Anterior Deltoid; Lateral Deltoid', 'Triceps', 'Dumbbells', 'Intermediate', 126, '3', '10-12', 60, 'Start with palms facing you. Rotate and press overhead.', 'Named after Arnold Schwarzenegger; hits all three delt heads'],
        ['Lateral Raises', 'Shoulders', 'Isolation', 'Lateral Deltoid', 'Traps', 'Dumbbells; Cable', 'Beginner', 90, '4', '12-15', 45, 'Stand with dumbbells at sides. Raise arms to shoulder level.', 'Lead with elbows; slight bend in arms; don\'t swing'],
        ['Front Raises', 'Shoulders', 'Isolation', 'Anterior Deltoid', 'Upper Chest', 'Dumbbells; Barbell; Cable', 'Beginner', 90, '3', '12-15', 45, 'Raise weight in front to shoulder level.', 'Alternate arms or both together; control the weight'],
        ['Rear Delt Flyes', 'Shoulders', 'Isolation', 'Posterior Deltoid', 'Rhomboids; Traps', 'Dumbbells; Cable; Machine', 'Beginner', 90, '4', '12-15', 45, 'Bend over or use machine. Raise arms to sides squeezing rear delts.', 'Often neglected; important for shoulder balance and posture'],
        ['Shrugs', 'Shoulders', 'Isolation', 'Trapezius', 'Rhomboids', 'Barbell; Dumbbells', 'Beginner', 90, '4', '12-15', 45, 'Hold weight at sides. Shrug shoulders up toward ears.', 'Don\'t roll shoulders; just up and down; pause at top'],

        // BICEPS EXERCISES
        ['Barbell Curl', 'Biceps', 'Isolation', 'Biceps Brachii', 'Brachialis; Forearms', 'Barbell', 'Beginner', 90, '4', '8-12', 60, 'Stand with bar at thighs. Curl to shoulders keeping elbows fixed.', 'Keep elbows at sides; don\'t swing body; full range of motion'],
        ['Dumbbell Curl', 'Biceps', 'Isolation', 'Biceps Brachii', 'Brachialis; Forearms', 'Dumbbells', 'Beginner', 90, '3', '10-12', 45, 'Curl dumbbells with palms up. Can alternate or together.', 'Allows supination for full bicep contraction'],
        ['Hammer Curl', 'Biceps', 'Isolation', 'Brachialis; Brachioradialis', 'Biceps Brachii', 'Dumbbells', 'Beginner', 90, '3', '10-12', 45, 'Curl with palms facing each other (neutral grip).', 'Targets brachialis for arm thickness; also hits forearms'],
        ['Incline Dumbbell Curl', 'Biceps', 'Isolation', 'Biceps Brachii (Long Head)', 'Brachialis', 'Dumbbells; Incline Bench', 'Intermediate', 90, '3', '10-12', 60, 'Lie back on incline bench. Curl from stretched position.', 'Greater stretch on biceps; targets long head'],
        ['Preacher Curl', 'Biceps', 'Isolation', 'Biceps Brachii (Short Head)', 'Brachialis', 'Preacher Bench; EZ-Bar/Dumbbells', 'Beginner', 90, '3', '10-12', 60, 'Rest upper arms on preacher pad. Curl weight up.', 'Eliminates momentum; isolates biceps; targets short head'],
        ['Concentration Curl', 'Biceps', 'Isolation', 'Biceps Brachii', 'Brachialis', 'Dumbbell', 'Beginner', 90, '3', '10-12 each', 45, 'Sit and brace elbow against inner thigh. Curl dumbbell up.', 'Peak contraction focus; great for mind-muscle connection'],

        // TRICEPS EXERCISES
        ['Close-Grip Bench Press', 'Triceps', 'Compound', 'Triceps Brachii', 'Chest; Anterior Deltoid', 'Barbell; Bench', 'Intermediate', 108, '4', '8-10', 90, 'Grip bar with hands inside shoulder width. Press up focusing on triceps.', 'Heavier weight possible than isolation exercises'],
        ['Skull Crushers', 'Triceps', 'Isolation', 'Triceps Brachii (Long Head)', 'Forearms', 'EZ-Bar; Dumbbells; Bench', 'Intermediate', 90, '3', '10-12', 60, 'Lie on bench with weight above chest. Lower to forehead then extend.', 'Keep elbows pointed up; control the descent'],
        ['Overhead Tricep Extension', 'Triceps', 'Isolation', 'Triceps Brachii (Long Head)', 'Forearms', 'Dumbbell; Cable; EZ-Bar', 'Beginner', 90, '3', '10-12', 60, 'Hold weight overhead. Lower behind head then extend.', 'Stretches long head; keep elbows close to head'],
        ['Tricep Pushdown', 'Triceps', 'Isolation', 'Triceps Brachii (Lateral Head)', 'Forearms', 'Cable Machine', 'Beginner', 90, '4', '12-15', 45, 'Push rope or bar down until arms straight. Squeeze at bottom.', 'Keep elbows at sides; don\'t lean forward'],
        ['Tricep Kickbacks', 'Triceps', 'Isolation', 'Triceps Brachii', 'Posterior Deltoid', 'Dumbbells', 'Beginner', 90, '3', '12-15 each', 45, 'Hinge forward arm bent. Extend arm back squeezing tricep.', 'Keep upper arm still; full extension; lighter weight'],
        ['Dips (Tricep Focus)', 'Triceps', 'Compound', 'Triceps Brachii', 'Chest; Anterior Deltoid', 'Dip Bars', 'Intermediate', 126, '3', '8-12', 60, 'Keep body upright on dip bars. Lower and press up.', 'Upright position targets triceps more than chest'],

        // LEG EXERCISES
        ['Barbell Back Squat', 'Legs', 'Compound', 'Quadriceps; Glutes', 'Hamstrings; Erector Spinae; Core', 'Barbell; Squat Rack', 'Intermediate', 180, '4', '6-10', 120, 'Bar on upper back. Squat until thighs parallel then drive up.', 'Keep chest up; knees track over toes; full depth'],
        ['Front Squat', 'Legs', 'Compound', 'Quadriceps', 'Glutes; Core; Upper Back', 'Barbell; Squat Rack', 'Advanced', 180, '4', '6-10', 120, 'Bar rests on front delts. Squat keeping torso upright.', 'More quad emphasis; requires good mobility; elbows high'],
        ['Goblet Squat', 'Legs', 'Compound', 'Quadriceps; Glutes', 'Core; Hamstrings', 'Dumbbell; Kettlebell', 'Beginner', 162, '3', '10-15', 60, 'Hold weight at chest. Squat down keeping chest up.', 'Great for learning squat form; beginner-friendly'],
        ['Leg Press', 'Legs', 'Compound', 'Quadriceps; Glutes', 'Hamstrings', 'Leg Press Machine', 'Beginner', 162, '4', '10-15', 90, 'Position feet shoulder-width on platform. Press up then lower with control.', 'Foot position changes emphasis; don\'t lock knees; control descent'],
        ['Lunges (Walking)', 'Legs', 'Compound', 'Quadriceps; Glutes', 'Hamstrings; Core', 'Dumbbells; Barbell (Optional)', 'Beginner', 162, '3', '10-12 each', 60, 'Step forward into lunge then step through to next lunge.', 'Keep torso upright; front knee over ankle'],
        ['Bulgarian Split Squat', 'Legs', 'Compound', 'Quadriceps; Glutes', 'Hamstrings; Core', 'Dumbbells; Bench', 'Intermediate', 162, '3', '8-12 each', 60, 'Rear foot elevated on bench. Lower into single leg squat.', 'Great for unilateral leg strength; challenging balance'],
        ['Leg Extension', 'Legs', 'Isolation', 'Quadriceps', 'None', 'Leg Extension Machine', 'Beginner', 108, '3', '12-15', 45, 'Extend legs until straight then lower with control.', 'Good for quad isolation; don\'t lock knees aggressively'],
        ['Leg Curl (Lying)', 'Legs', 'Isolation', 'Hamstrings', 'Calves', 'Leg Curl Machine', 'Beginner', 108, '3', '12-15', 45, 'Curl weight up by bending knees then lower with control.', 'Keep hips down; squeeze at top; control negative'],
        ['Hip Thrust', 'Legs', 'Compound', 'Glutes', 'Hamstrings; Core', 'Barbell; Bench', 'Beginner', 144, '4', '10-15', 60, 'Upper back on bench bar across hips. Drive hips up squeezing glutes.', 'Best glute exercise; pause at top; full hip extension'],
        ['Standing Calf Raise', 'Legs', 'Isolation', 'Gastrocnemius', 'Soleus', 'Calf Raise Machine; Smith Machine', 'Beginner', 90, '4', '12-20', 30, 'Rise up on toes squeezing calves then lower with control.', 'Full range of motion; pause at top; slow negative'],

        // CORE EXERCISES
        ['Plank', 'Core', 'Isometric', 'Rectus Abdominis; Transverse Abdominis', 'Obliques; Shoulders; Glutes', 'Bodyweight', 'Beginner', 72, '3', '30-60 sec', 30, 'Hold push-up position on forearms. Keep body straight.', 'Don\'t let hips sag or pike; breathe normally'],
        ['Side Plank', 'Core', 'Isometric', 'Obliques', 'Transverse Abdominis; Glutes', 'Bodyweight', 'Beginner', 72, '3', '30-45 sec each', 30, 'Support body on one forearm. Keep hips up.', 'Stack or stagger feet; don\'t rotate'],
        ['Hanging Leg Raise', 'Core', 'Compound', 'Rectus Abdominis (Lower)', 'Hip Flexors; Obliques', 'Pull-up Bar', 'Advanced', 108, '3', '10-15', 60, 'Hang from bar. Raise legs until parallel or higher.', 'Control the swing; don\'t use momentum'],
        ['Cable Crunch', 'Core', 'Isolation', 'Rectus Abdominis', 'Obliques', 'Cable Machine', 'Beginner', 90, '3', '15-20', 45, 'Kneel facing cable. Crunch down rounding spine.', 'Keep hips still; focus on spinal flexion'],
        ['Ab Wheel Rollout', 'Core', 'Compound', 'Rectus Abdominis; Transverse Abdominis', 'Obliques; Lats; Shoulders', 'Ab Wheel', 'Advanced', 108, '3', '8-12', 60, 'Kneel with wheel on ground. Roll out extending body then roll back.', 'Very challenging; start with short range'],
        ['Russian Twist', 'Core', 'Compound', 'Obliques', 'Rectus Abdominis; Hip Flexors', 'Medicine Ball; Dumbbell (Optional)', 'Beginner', 90, '3', '15-20 each', 45, 'Sit with feet up. Rotate torso touching weight to each side.', 'Keep chest up; full rotation; feet can touch ground for easier'],
        ['Bicycle Crunch', 'Core', 'Compound', 'Obliques; Rectus Abdominis', 'Hip Flexors', 'Bodyweight', 'Beginner', 90, '3', '15-20 each', 45, 'Lie on back. Bring opposite elbow to knee alternating sides.', 'Don\'t pull on neck; rotate from core'],
        ['Dead Bug', 'Core', 'Compound', 'Transverse Abdominis; Core Stability', 'Hip Flexors; Rectus Abdominis', 'Bodyweight', 'Beginner', 72, '3', '10-12 each', 30, 'Lie on back arms up knees at 90. Lower opposite arm and leg.', 'Keep lower back pressed to floor; slow and controlled'],
        ['Mountain Climbers', 'Core', 'Compound', 'Core; Hip Flexors', 'Shoulders; Cardio', 'Bodyweight', 'Beginner', 180, '3', '20-30', 45, 'Start in plank. Drive knees toward chest alternating quickly.', 'Keep hips low; great for conditioning'],

        // CARDIO EXERCISES
        ['Running (Moderate)', 'Cardio', 'Aerobic', 'Quadriceps; Glutes', 'Hamstrings; Calves; Core', 'Treadmill; None', 'Beginner', 360, '1', '20-60 min', 0, 'Maintain steady pace at conversational intensity.', 'Good shoes; proper form; build up gradually'],
        ['Sprinting', 'Cardio', 'Anaerobic', 'Quadriceps; Glutes; Hamstrings', 'Calves; Core', 'Track; None', 'Advanced', 600, '8-10', '20-30 sec', 90, 'Maximum effort short bursts with rest between.', 'Thorough warm-up; great for fat loss'],
        ['Cycling (Moderate)', 'Cardio', 'Aerobic', 'Quadriceps; Glutes', 'Hamstrings; Calves', 'Stationary Bike', 'Beginner', 252, '1', '20-45 min', 0, 'Maintain moderate resistance and pace.', 'Low impact on joints; adjust seat height properly'],
        ['Rowing Machine', 'Cardio', 'Compound', 'Lats; Rhomboids; Quads', 'Biceps; Glutes; Core', 'Rowing Machine', 'Beginner', 252, '1', '15-30 min', 0, 'Drive with legs then pull with back and arms.', 'Full body cardio; maintain good posture'],
        ['Jump Rope', 'Cardio', 'Aerobic', 'Calves; Shoulders', 'Core; Coordination', 'Jump Rope', 'Intermediate', 335, '3', '3-5 min', 60, 'Skip rope continuously or in intervals.', 'Great conditioning; builds coordination'],
        ['Burpees', 'Cardio', 'Compound', 'Full Body', 'Core; Shoulders; Legs', 'Bodyweight', 'Intermediate', 306, '3', '10-20', 60, 'Squat thrust with push-up and jump.', 'Brutal but effective; scale as needed'],
        ['Kettlebell Swings', 'Cardio', 'Compound', 'Glutes; Hamstrings', 'Core; Shoulders; Back', 'Kettlebell', 'Intermediate', 270, '3', '15-25', 45, 'Hip hinge explosively swinging kettlebell to chest height.', 'Power from hips not arms; great conditioning'],

        // FULL BODY EXERCISES
        ['Thrusters', 'Full Body', 'Compound', 'Quadriceps; Shoulders', 'Glutes; Core; Triceps', 'Barbell; Dumbbells', 'Intermediate', 270, '3', '10-15', 90, 'Front squat directly into overhead press.', 'Brutal conditioning exercise; common in CrossFit'],
        ['Farmer\'s Carry', 'Full Body', 'Compound', 'Forearms; Core', 'Traps; Legs; Full Body', 'Dumbbells; Kettlebells', 'Beginner', 180, '3', '40-60 sec', 60, 'Walk with heavy weights in each hand.', 'Great for grip; posture; and conditioning'],
        ['Medicine Ball Slams', 'Full Body', 'Compound', 'Core; Shoulders', 'Lats; Legs', 'Medicine Ball', 'Beginner', 216, '3', '12-20', 45, 'Raise ball overhead and slam to ground explosively.', 'Great for power and stress relief'],
    ];

    // Clear existing library (for re-import)
    $db->exec("DELETE FROM exercise_library");

    // Insert exercises
    $stmt = $db->prepare("INSERT INTO exercise_library
        (name, category, type, primary_muscles, secondary_muscles, equipment, difficulty, calories_per_30min, sets_recommended, reps_recommended, rest_seconds, instructions, tips)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $count = 0;
    foreach ($exercises as $ex) {
        $stmt->execute($ex);
        $count++;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Exercise library imported successfully!',
        'exercises_imported' => $count
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Import failed: ' . $e->getMessage()
    ]);
}
