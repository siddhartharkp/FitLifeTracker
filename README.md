# FitLife Tracker

A comprehensive meal and fitness tracking web application with MySQL backend.

## Features

- **Meal Tracking**: Log breakfast, lunch, dinner, snacks, and post-workout meals
- **Macro Monitoring**: Track calories, protein, carbs, fat, and fiber with visual progress rings
- **Food Database**: 160+ Indian foods with accurate nutritional data
- **Quick Combos**: Pre-configured meal combinations for fast logging
- **Custom Foods**: Add your own foods to the database
- **Exercise Tracking**: Daily workout checkboxes with PR logging
- **Weight Tracking**: Monitor weight with history chart
- **Water Tracking**: Daily water intake monitoring
- **Day Types**: Normal, Light Day, and Cheat Day modes
- **Week Navigation**: Browse and edit past days
- **Cloud Sync**: All data synced to MySQL database
- **Offline Support**: localStorage fallback when offline

## Project Structure

```
FitLife Tracker/
├── index.html              # Main application (single-page app)
├── backend/
│   ├── api.php             # REST API endpoints
│   ├── config.php          # Database config, CORS, security
│   ├── schema.sql          # MySQL database schema
│   ├── import_foods.php    # Food database import script
│   ├── food-database.csv   # 160+ Indian foods data
│   ├── README.md           # Backend setup guide
│   ├── logs/               # Error logs (protected)
│   └── cache/              # Rate limiting cache (protected)
├── archive/
│   └── fitlife-tracker-v1.html  # Old version backup
└── FitLife-Complete-Guide.docx  # Meal plan reference
```

## Quick Start

### Prerequisites

- PHP 7.4+ with PDO MySQL extension
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx) or local development server

### 1. Database Setup

```bash
# Connect to MySQL
mysql -u root -p

# Create database
CREATE DATABASE fitlife_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (optional)
CREATE USER 'fitlife_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON fitlife_tracker.* TO 'fitlife_user'@'localhost';
FLUSH PRIVILEGES;

# Import schema
USE fitlife_tracker;
SOURCE backend/schema.sql;
```

### 2. Configure Backend

Edit `backend/config.php`:

```php
// Option 1: Environment variables (recommended)
// Set these in your server environment or .env file
// DB_HOST, DB_NAME, DB_USER, DB_PASS

// Option 2: Direct configuration (development only)
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitlife_tracker');
define('DB_USER', 'fitlife_user');
define('DB_PASS', 'your_secure_password');
```

Add your domain to CORS whitelist:

```php
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1',
    'https://yourdomain.com',  // Add your domain
];
```

### 3. Import Food Database

```bash
# Option 1: CLI (recommended)
cd backend
php import_foods.php

# Option 2: Browser with secret key
# Visit: https://yourdomain.com/backend/import_foods.php?key=fitlife_import_2025_secure_key
```

### 4. Configure Frontend

Edit `index.html` and update the API URL:

```javascript
const API_URL = 'https://yourdomain.com/backend/api.php';
```

### 5. Deploy

Upload to your web server:
- `index.html` → document root
- `backend/` → document root or subdirectory

## API Endpoints

| Action | Method | Description |
|--------|--------|-------------|
| `health` | GET | Health check endpoint |
| `logMeal` | POST | Log a meal item |
| `getDaily` | GET | Get meals for a date |
| `deleteMeal` | POST | Delete a meal by ID |
| `clearDay` | POST | Clear all meals for a day |
| `getAllFoods` | GET | Get entire food database |
| `searchFoods` | GET | Search foods by name/category |
| `addCustomFood` | POST | Add custom food |
| `getGoals` | GET | Get daily goals |
| `updateGoals` | POST | Update goals |
| `logWeight` | POST | Log weight |
| `getWeightHistory` | GET | Get weight history |
| `logExercise` | POST | Log exercise completion |
| `getExerciseLog` | GET | Get exercise log |
| `logPR` | POST | Log personal record |
| `getPRLog` | GET | Get all PRs |
| `setDayType` | POST | Set day type |
| `getDayTypes` | GET | Get all day types |
| `logWater` | POST | Log water intake |
| `getWater` | GET | Get water for a day |
| `saveMealCombo` | POST | Save custom combo |
| `getMealCombos` | GET | Get custom combos |

## Security Features

- **CORS Protection**: Origin whitelist with development fallback
- **Rate Limiting**: 100 requests/minute per IP
- **Input Validation**: All inputs validated and sanitized
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Prevention**: Output encoding and sanitization
- **Security Headers**: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection
- **Error Logging**: Secure logging with protected directory
- **Import Protection**: One-time import with secret key

## Development

### Local Development

```bash
# Start PHP built-in server
cd "/path/to/FitLife Tracker"
php -S localhost:8000

# Open in browser
open http://localhost:8000
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database host | localhost |
| `DB_NAME` | Database name | fitlife_tracker |
| `DB_USER` | Database user | - |
| `DB_PASS` | Database password | - |
| `ENV` | Environment (development/production) | - |
| `IMPORT_SECRET` | Import script secret key | fitlife_import_2025_secure_key |

## Troubleshooting

### CORS Errors
Add your domain to `$allowed_origins` in `config.php`

### 500 Server Error
- Check PHP error logs
- Verify database credentials
- Ensure PDO MySQL extension is enabled

### Rate Limited (429)
Wait 60 seconds or adjust rate limit in `config.php`

### Import Script Blocked
- Use CLI: `php import_foods.php`
- Or provide secret key in URL
- Delete `import.lock` to re-run

## Tech Stack

- **Frontend**: Vanilla JavaScript, Tailwind CSS, Chart.js
- **Backend**: PHP 7.4+, PDO
- **Database**: MySQL/MariaDB
- **Storage**: localStorage (offline fallback)

## License

Private project - All rights reserved

## Author

Siddhartha
