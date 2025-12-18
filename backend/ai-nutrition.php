<?php
/**
 * AI Nutrition Analysis Function
 * Proxies requests to Gemini API for meal analysis
 */

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

    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . GEMINI_API_KEY;

    // Make request to Gemini API using cURL
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $requestBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

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
