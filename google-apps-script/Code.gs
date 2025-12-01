/**
 * FitLife Tracker - Google Apps Script Backend
 *
 * SETUP INSTRUCTIONS:
 * 1. Create a new Google Sheet
 * 2. Go to Extensions > Apps Script
 * 3. Paste this entire code
 * 4. Click Deploy > New Deployment > Web App
 * 5. Set "Execute as" = Me, "Who has access" = Anyone
 * 6. Copy the deployment URL and add to your HTML file
 */

// ==================== CONFIGURATION ====================

// IMPORTANT: If using standalone script, paste your Google Sheet ID here
// Find it in the Sheet URL: https://docs.google.com/spreadsheets/d/YOUR_SHEET_ID_HERE/edit
const SPREADSHEET_ID = ''; // Leave empty if script is bound to a sheet

const SHEET_NAMES = {
  FOODS: 'Foods',
  MEAL_LOG: 'MealLog',
  GOALS: 'Goals',
  WEIGHT_LOG: 'WeightLog',
  DAILY_SUMMARY: 'DailySummary'
};

// Helper to get spreadsheet (works for both bound and standalone scripts)
function getSpreadsheet() {
  if (SPREADSHEET_ID && SPREADSHEET_ID.length > 0) {
    return SpreadsheetApp.openById(SPREADSHEET_ID);
  }
  return SpreadsheetApp.getActiveSpreadsheet();
}

// ==================== WEB APP HANDLERS ====================

function doGet(e) {
  const action = e.parameter.action;
  let result;

  try {
    switch(action) {
      case 'getFoods':
        result = getFoods(e.parameter.search, e.parameter.category);
        break;
      case 'getDaily':
        result = getDailyLog(e.parameter.date);
        break;
      case 'getGoals':
        result = getGoals();
        break;
      case 'getWeightHistory':
        result = getWeightHistory();
        break;
      case 'getAllFoods':
        result = getAllFoods();
        break;
      default:
        result = { error: 'Unknown action' };
    }
  } catch(error) {
    result = { error: error.toString() };
  }

  return ContentService
    .createTextOutput(JSON.stringify(result))
    .setMimeType(ContentService.MimeType.JSON);
}

function doPost(e) {
  const data = JSON.parse(e.postData.contents);
  const action = data.action;
  let result;

  try {
    switch(action) {
      case 'logMeal':
        result = logMeal(data);
        break;
      case 'deleteMealItem':
        result = deleteMealItem(data.date, data.rowIndex);
        break;
      case 'logWeight':
        result = logWeight(data.date, data.weight, data.notes);
        break;
      case 'updateGoals':
        result = updateGoals(data.goals);
        break;
      case 'addCustomFood':
        result = addCustomFood(data.food);
        break;
      case 'clearDay':
        result = clearDayLog(data.date);
        break;
      default:
        result = { error: 'Unknown action' };
    }
  } catch(error) {
    result = { error: error.toString() };
  }

  return ContentService
    .createTextOutput(JSON.stringify(result))
    .setMimeType(ContentService.MimeType.JSON);
}

// ==================== FOOD DATABASE ====================

function getAllFoods() {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.FOODS);
  const data = sheet.getDataRange().getValues();
  const headers = data[0];

  const foods = [];
  for (let i = 1; i < data.length; i++) {
    const row = data[i];
    if (row[0]) { // Has ID
      foods.push({
        id: row[0],
        name: row[1],
        category: row[2],
        calories: row[3],
        protein: row[4],
        carbs: row[5],
        fat: row[6],
        fiber: row[7],
        serving: row[8],
        unit: row[9]
      });
    }
  }

  return { success: true, foods: foods };
}

function getFoods(search, category) {
  const allFoods = getAllFoods().foods;
  let filtered = allFoods;

  if (search && search.trim()) {
    const searchLower = search.toLowerCase();
    filtered = filtered.filter(f =>
      f.name.toLowerCase().includes(searchLower)
    );
  }

  if (category && category !== 'all') {
    filtered = filtered.filter(f => f.category === category);
  }

  return { success: true, foods: filtered };
}

function addCustomFood(food) {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.FOODS);
  const lastRow = sheet.getLastRow();
  const newId = lastRow; // Simple auto-increment

  sheet.appendRow([
    newId,
    food.name,
    food.category || 'Custom',
    food.calories || 0,
    food.protein || 0,
    food.carbs || 0,
    food.fat || 0,
    food.fiber || 0,
    food.serving || 1,
    food.unit || 'serving'
  ]);

  return { success: true, id: newId };
}

// ==================== MEAL LOGGING ====================

function logMeal(data) {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.MEAL_LOG);

  const row = [
    data.date,
    data.mealType,
    data.foodId,
    data.foodName,
    data.quantity,
    data.unit,
    data.calories,
    data.protein,
    data.carbs,
    data.fat,
    data.fiber,
    new Date().toISOString()
  ];

  sheet.appendRow(row);

  // Update daily summary
  updateDailySummary(data.date);

  return { success: true };
}

function deleteMealItem(date, rowIndex) {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.MEAL_LOG);
  const data = sheet.getDataRange().getValues();

  // Find rows matching this date and delete the specific one
  let matchCount = 0;
  for (let i = 1; i < data.length; i++) {
    if (data[i][0] === date) {
      if (matchCount === rowIndex) {
        sheet.deleteRow(i + 1);
        updateDailySummary(date);
        return { success: true };
      }
      matchCount++;
    }
  }

  return { success: false, error: 'Item not found' };
}

function getDailyLog(date) {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.MEAL_LOG);
  const data = sheet.getDataRange().getValues();

  const meals = {
    breakfast: [],
    lunch: [],
    dinner: [],
    snacks: [],
    postworkout: []
  };

  let totals = { calories: 0, protein: 0, carbs: 0, fat: 0, fiber: 0 };

  for (let i = 1; i < data.length; i++) {
    const row = data[i];
    if (row[0] === date) {
      const item = {
        rowIndex: meals[row[1]] ? meals[row[1]].length : 0,
        foodId: row[2],
        foodName: row[3],
        quantity: row[4],
        unit: row[5],
        calories: row[6],
        protein: row[7],
        carbs: row[8],
        fat: row[9],
        fiber: row[10]
      };

      const mealType = row[1].toLowerCase().replace('-', '');
      if (meals[mealType]) {
        meals[mealType].push(item);
      }

      totals.calories += Number(row[6]) || 0;
      totals.protein += Number(row[7]) || 0;
      totals.carbs += Number(row[8]) || 0;
      totals.fat += Number(row[9]) || 0;
      totals.fiber += Number(row[10]) || 0;
    }
  }

  return { success: true, meals: meals, totals: totals };
}

function clearDayLog(date) {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.MEAL_LOG);
  const data = sheet.getDataRange().getValues();

  // Delete from bottom to top to maintain row indices
  for (let i = data.length - 1; i >= 1; i--) {
    if (data[i][0] === date) {
      sheet.deleteRow(i + 1);
    }
  }

  return { success: true };
}

// ==================== DAILY SUMMARY ====================

function updateDailySummary(date) {
  const dailyData = getDailyLog(date);
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.DAILY_SUMMARY);
  const data = sheet.getDataRange().getValues();

  // Find existing row for this date
  let rowIndex = -1;
  for (let i = 1; i < data.length; i++) {
    if (data[i][0] === date) {
      rowIndex = i + 1;
      break;
    }
  }

  const summaryRow = [
    date,
    dailyData.totals.calories,
    dailyData.totals.protein,
    dailyData.totals.carbs,
    dailyData.totals.fat,
    dailyData.totals.fiber,
    new Date().toISOString()
  ];

  if (rowIndex > 0) {
    sheet.getRange(rowIndex, 1, 1, 7).setValues([summaryRow]);
  } else {
    sheet.appendRow(summaryRow);
  }
}

// ==================== GOALS ====================

function getGoals() {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.GOALS);
  const data = sheet.getDataRange().getValues();

  if (data.length < 2) {
    // Return defaults
    return {
      success: true,
      goals: {
        calories: 1300,
        protein: 80,
        carbs: 180,
        fat: 45,
        fiber: 25,
        targetWeight: 65
      }
    };
  }

  return {
    success: true,
    goals: {
      calories: data[1][0],
      protein: data[1][1],
      carbs: data[1][2],
      fat: data[1][3],
      fiber: data[1][4],
      targetWeight: data[1][5]
    }
  };
}

function updateGoals(goals) {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.GOALS);

  // Clear and set headers if needed
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(['calories', 'protein', 'carbs', 'fat', 'fiber', 'targetWeight']);
  }

  const row = [
    goals.calories,
    goals.protein,
    goals.carbs,
    goals.fat,
    goals.fiber,
    goals.targetWeight
  ];

  if (sheet.getLastRow() >= 2) {
    sheet.getRange(2, 1, 1, 6).setValues([row]);
  } else {
    sheet.appendRow(row);
  }

  return { success: true };
}

// ==================== WEIGHT TRACKING ====================

function logWeight(date, weight, notes) {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.WEIGHT_LOG);
  const data = sheet.getDataRange().getValues();

  // Check if entry exists for this date
  let rowIndex = -1;
  for (let i = 1; i < data.length; i++) {
    if (data[i][0] === date) {
      rowIndex = i + 1;
      break;
    }
  }

  const row = [date, weight, notes || '', new Date().toISOString()];

  if (rowIndex > 0) {
    sheet.getRange(rowIndex, 1, 1, 4).setValues([row]);
  } else {
    sheet.appendRow(row);
  }

  return { success: true };
}

function getWeightHistory() {
  const sheet = getSpreadsheet().getSheetByName(SHEET_NAMES.WEIGHT_LOG);
  const data = sheet.getDataRange().getValues();

  const history = [];
  for (let i = 1; i < data.length; i++) {
    if (data[i][0]) {
      history.push({
        date: data[i][0],
        weight: data[i][1],
        notes: data[i][2]
      });
    }
  }

  // Sort by date
  history.sort((a, b) => new Date(a.date) - new Date(b.date));

  return { success: true, history: history };
}

// ==================== INITIALIZATION ====================

function initializeSheets() {
  const ss = getSpreadsheet();

  // Create Foods sheet
  let sheet = ss.getSheetByName(SHEET_NAMES.FOODS);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAMES.FOODS);
    sheet.appendRow(['id', 'name', 'category', 'calories', 'protein', 'carbs', 'fat', 'fiber', 'serving', 'unit']);
  }

  // Create MealLog sheet
  sheet = ss.getSheetByName(SHEET_NAMES.MEAL_LOG);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAMES.MEAL_LOG);
    sheet.appendRow(['date', 'mealType', 'foodId', 'foodName', 'quantity', 'unit', 'calories', 'protein', 'carbs', 'fat', 'fiber', 'timestamp']);
  }

  // Create Goals sheet
  sheet = ss.getSheetByName(SHEET_NAMES.GOALS);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAMES.GOALS);
    sheet.appendRow(['calories', 'protein', 'carbs', 'fat', 'fiber', 'targetWeight']);
    sheet.appendRow([1300, 80, 180, 45, 25, 65]);
  }

  // Create WeightLog sheet
  sheet = ss.getSheetByName(SHEET_NAMES.WEIGHT_LOG);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAMES.WEIGHT_LOG);
    sheet.appendRow(['date', 'weight', 'notes', 'timestamp']);
  }

  // Create DailySummary sheet
  sheet = ss.getSheetByName(SHEET_NAMES.DAILY_SUMMARY);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAMES.DAILY_SUMMARY);
    sheet.appendRow(['date', 'calories', 'protein', 'carbs', 'fat', 'fiber', 'updatedAt']);
  }

  return 'Sheets initialized successfully!';
}

// Run this function once to set up all sheets
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('FitLife Tracker')
    .addItem('Initialize Sheets', 'initializeSheets')
    .addToUi();
}
