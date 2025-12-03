-- FitLife Tracker MySQL Schema
-- Run this in phpMyAdmin or MySQL CLI to create the database

CREATE DATABASE IF NOT EXISTS gsxzuwmy_fitlife2;
USE gsxzuwmy_fitlife2;

-- Foods table (food database)
CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
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
);

-- Meal log table
CREATE TABLE IF NOT EXISTS meal_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snacks', 'postworkout') NOT NULL,
    food_id INT DEFAULT NULL,
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
    INDEX idx_meal_type (meal_type),
    INDEX idx_date_meal (date, meal_type)
);

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
);

-- Insert default goals
INSERT INTO goals (calories, protein, carbs, fat, fiber, target_weight)
VALUES (1450, 105, 140, 50, 28, 65)
ON DUPLICATE KEY UPDATE id=id;

-- Weight log table
CREATE TABLE IF NOT EXISTS weight_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    weight DECIMAL(5,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date)
);

-- Exercise log table
CREATE TABLE IF NOT EXISTS exercise_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    exercise_index INT NOT NULL,
    completed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_exercise (date, exercise_index),
    INDEX idx_date (date)
);

-- PR (Personal Records) log table
CREATE TABLE IF NOT EXISTS pr_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exercise_name VARCHAR(255) NOT NULL UNIQUE,
    value DECIMAL(10,2) NOT NULL,
    pr_type ENUM('reps', 'time', 'weight') DEFAULT 'reps',
    achieved_date VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Day types table (normal, light, cheat)
CREATE TABLE IF NOT EXISTS day_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    type ENUM('normal', 'light', 'cheat') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date)
);

-- Water intake table
CREATE TABLE IF NOT EXISTS water_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    glasses INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date)
);

-- Meal combos table (for custom combos)
CREATE TABLE IF NOT EXISTS meal_combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    emoji VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
    category ENUM('breakfast', 'lunch', 'dinner', 'snacks', 'cheat') DEFAULT 'breakfast',
    tag VARCHAR(50),
    damage_level ENUM('low', 'medium', 'high') DEFAULT NULL,
    items JSON NOT NULL,
    total_calories INT DEFAULT 0,
    total_protein INT DEFAULT 0,
    total_carbs INT DEFAULT 0,
    total_fat INT DEFAULT 0,
    total_fiber INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
