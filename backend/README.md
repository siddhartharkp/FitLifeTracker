# FitLife Tracker - MySQL Backend Setup

## Quick Setup Guide for Bluehost

### Step 1: Create MySQL Database

1. Log into Bluehost cPanel
2. Go to **Databases > MySQL Databases**
3. Create a new database: `fitlife_tracker` (or any name)
4. Create a MySQL user and password
5. Add the user to the database with **ALL PRIVILEGES**

### Step 2: Run the Schema

1. Go to **phpMyAdmin** in cPanel
2. Select your database
3. Click **Import** tab
4. Upload `schema.sql` and run it

### Step 3: Configure the API

1. Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_cpanel_username_fitlife_tracker');
define('DB_USER', 'your_cpanel_username_dbuser');
define('DB_PASS', 'your_password');
```

### Step 4: Upload Files

Upload these files to your server (e.g., `/public_html/fitlife-api/`):
- `config.php`
- `api.php`
- `import_foods.php`
- `food-database.csv` (copy from google-apps-script folder)

### Step 5: Import Food Database

Visit: `https://yourdomain.com/fitlife-api/import_foods.php`

This will import all foods into the database.

### Step 6: Update Frontend

In `index.html`, change the API_URL:
```javascript
const API_URL = 'https://yourdomain.com/fitlife-api/api.php';
```

### Step 7: Test

Open the app and try adding a meal. You should see:
- "Syncing..." (blue)
- "Saved to cloud" (green) - almost instantly!

---

## API Endpoints

### Meals
- `POST logMeal` - Log a meal item
- `GET getDaily?date=YYYY-MM-DD` - Get meals for a day
- `POST deleteMeal` - Delete a meal by ID
- `POST clearDay` - Clear all meals for a day

### Foods
- `GET getAllFoods` - Get entire food database
- `GET searchFoods?search=term&category=cat` - Search foods
- `POST addCustomFood` - Add a custom food

### Goals
- `GET getGoals` - Get daily goals
- `POST updateGoals` - Update goals

### Weight
- `POST logWeight` - Log weight
- `GET getWeightHistory` - Get weight history

### Exercise
- `POST logExercise` - Log exercise completion
- `GET getExerciseLog?date=YYYY-MM-DD` - Get exercise log

### PRs
- `POST logPR` - Log personal record
- `GET getPRLog` - Get all PRs

### Day Types
- `POST setDayType` - Set day type (normal/light/cheat)
- `GET getDayTypes` - Get all day types

### Water
- `POST logWater` - Log water intake
- `GET getWater?date=YYYY-MM-DD` - Get water for a day

### Combos
- `POST saveMealCombo` - Save a custom combo
- `GET getMealCombos` - Get custom combos

---

## Troubleshooting

### CORS Errors
Edit `config.php` and add your domain to `$allowed_origins`

### 500 Server Error
- Check PHP error logs in cPanel
- Verify database credentials in `config.php`
- Make sure PDO MySQL extension is enabled

### Slow Queries
Add these indexes if not already created:
```sql
ALTER TABLE meal_log ADD INDEX idx_date (date);
ALTER TABLE foods ADD INDEX idx_name (name);
```
