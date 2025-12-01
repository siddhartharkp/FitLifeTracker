# FitLife Tracker v2 - Setup Guide

## Overview
FitLife Tracker v2 now supports flexible meal logging with Google Sheets as a backend database. This guide will help you set everything up.

## Files Created

```
FitLife Tracker/
├── fitlife-tracker-v2.html      # New main app file
├── google-apps-script/
│   ├── Code.gs                  # Google Apps Script backend
│   └── food-database.csv        # 160 Indian foods with macros
├── archive/
│   └── fitlife-tracker-v1.html  # Old version (backup)
└── SETUP.md                     # This file
```

## Setup Instructions

### Step 1: Create Google Sheet

1. Go to [Google Sheets](https://sheets.google.com)
2. Create a new blank spreadsheet
3. Name it "FitLife Tracker Database"

### Step 2: Import Food Database

1. In your new Google Sheet, go to **File > Import**
2. Click **Upload** and select `food-database.csv`
3. Choose **Replace spreadsheet**
4. Click **Import data**
5. Rename the sheet tab to **Foods** (right-click the tab at bottom)

### Step 3: Set Up Google Apps Script

1. In your Google Sheet, go to **Extensions > Apps Script**
2. Delete any existing code in the editor
3. Copy the entire contents of `google-apps-script/Code.gs`
4. Paste it into the Apps Script editor
5. Click **Save** (Ctrl+S / Cmd+S)
6. Click **Run > initializeSheets** to create all required sheets
   - You'll need to authorize the script when prompted
   - Review permissions and click "Allow"

### Step 4: Deploy the Web App

1. In Apps Script, click **Deploy > New deployment**
2. Click the gear icon next to "Select type" and choose **Web app**
3. Configure:
   - Description: "FitLife Tracker API"
   - Execute as: **Me**
   - Who has access: **Anyone**
4. Click **Deploy**
5. **Copy the Web app URL** (looks like: `https://script.google.com/macros/s/...../exec`)

### Step 5: Configure the HTML File

1. Open `fitlife-tracker-v2.html` in a text editor
2. Find this line near the top of the `<script>` section:
   ```javascript
   const API_URL = 'YOUR_GOOGLE_APPS_SCRIPT_URL_HERE';
   ```
3. Replace `YOUR_GOOGLE_APPS_SCRIPT_URL_HERE` with your Web app URL from Step 4
4. Save the file

### Step 6: Open the App

1. Open `fitlife-tracker-v2.html` in your web browser
2. The app should connect to Google Sheets automatically
3. If you see "Offline Mode", check your API URL configuration

## Google Sheets Structure

After running `initializeSheets`, your spreadsheet will have these sheets:

| Sheet | Purpose |
|-------|---------|
| Foods | Food database (160+ items with macros) |
| MealLog | Daily food intake log |
| Goals | Your calorie and macro targets |
| WeightLog | Weight tracking history |
| DailySummary | Auto-calculated daily totals |

## Features

### Macro Tracking
- Real-time progress rings for Protein, Carbs, Fat, Fiber
- Calorie progress bar with daily percentage
- All values update as you log food

### Meal Structure
- **Main Meals**: Breakfast, Lunch, Dinner
- **Side Section**: Snacks, Post-Workout

### Food Logging
- Search 160+ Indian foods
- Filter by category (Protein, Grains, Dairy, etc.)
- Add custom foods
- Adjust quantities with +/- buttons

### Data Sync
- All data syncs to Google Sheets
- Access from any device
- Offline fallback to localStorage

## Customizing Goals

1. Click the **Goals** button (gear icon) in the header
2. Set your daily targets:
   - Calories
   - Protein (g)
   - Carbs (g)
   - Fat (g)
   - Fiber (g)
   - Target Weight (kg)
3. Click **Save**

## Adding Custom Foods

1. Click **+ Add** on any meal
2. Scroll down and click **+ Add Custom Food**
3. Enter food details:
   - Name (required)
   - Calories (required)
   - Protein, Carbs, Fat, Fiber (optional)
   - Serving size and unit
4. Click **Save & Add**

## Troubleshooting

### "Offline Mode" appears
- Check that your API URL is correctly configured
- Make sure you deployed the Apps Script as a Web app
- Verify the deployment URL is complete (ends with `/exec`)

### Foods not loading
- Run `initializeSheets()` in Apps Script again
- Check that the Foods sheet has data
- Ensure column headers match exactly

### Data not syncing
- Check browser console for errors (F12)
- Verify Apps Script permissions are granted
- Try redeploying the web app

## Notes

- First load may take 5-10 seconds (Apps Script cold start)
- Subsequent loads are faster
- Data persists in Google Sheets even if you clear browser data
