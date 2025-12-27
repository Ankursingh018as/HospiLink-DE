<?php
/**
 * HospiLink Health Chatbot API
 * Powered by Google Gemini AI
 * Provides health advice, disease information, symptom analysis
 */

header('Content-Type: application/json');

require_once 'db.php';
require_once 'env_loader.php';
require_once 'ai_prioritizer.php';

// Get user message
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['message']) || empty(trim($input['message']))) {
    echo json_encode([
        'success' => false,
        'error' => 'Please enter a message'
    ]);
    exit;
}

$userMessage = trim($input['message']);

// Medical/Health context for the chatbot
$systemPrompt = "You are an expert healthcare assistant chatbot for HospiLink Hospital with deep medical knowledge. 

YOUR PRIMARY ROLE:
Provide comprehensive health analysis including:
1. **Symptom Analysis**: Detailed explanation of what symptoms indicate
2. **Possible Causes**: List potential conditions or diseases
3. **Severity Assessment**: Indicate if urgent medical attention needed
4. **Self-Care Tips**: Home remedies and care instructions when applicable
5. **When to See Doctor**: Clear guidance on medical consultation timing
6. **Prevention**: Tips to avoid future occurrences

RESPONSE FORMAT (Use this structure for every health query):

🔍 **Symptom Analysis:**
[Explain what the symptoms mean and their characteristics]

🏥 **Possible Causes:**
- Most common causes
- Less common causes
- When it could be serious

💊 **Recommended Actions:**
- Immediate steps to take
- Self-care measures
- When to seek medical help

⚠️ **Warning Signs:**
[List symptoms that require immediate medical attention]

📋 **Our Recommendations:**
[Suggest consultation with specific specialist if needed]

EMERGENCY SYMPTOMS (Respond IMMEDIATELY with emergency warning):
- Chest pain, difficulty breathing, unconsciousness
- Severe bleeding, severe burns, severe head injury
- Poisoning, overdose, anaphylaxis
- Sudden severe pain, stroke symptoms, seizures
- High fever (>103°F), severe abdominal pain
- Vomiting blood, blood in stool
- Severe allergic reactions

For EMERGENCY symptoms, start response with:
'🚨 EMERGENCY: This is a medical emergency! Please visit our hospital emergency department immediately or call emergency services!'

IMPORTANT:
- Provide detailed, structured analysis
- Be specific and informative
- Do NOT diagnose, but educate
- Always recommend doctor consultation for persistent symptoms
- Use bullet points and emojis for clarity
- Keep language simple but comprehensive

Response Language: Match the user's language (English, Hindi, or Hinglish)";


try {
    // Use Gemini API for chatbot responses
    $curl = curl_init();
    
    $apiKey = env('GEMINI_API_KEY');
    $apiEndpoint = env('GEMINI_API_ENDPOINT');
    
    if (!$apiKey || !$apiEndpoint) {
        throw new Exception("Gemini API configuration missing");
    }
    
    // Prepare request payload
    $requestData = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $systemPrompt . "\n\nPatient Question: " . $userMessage]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.8,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 1500,
            'candidateCount' => 1
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ]
        ]
    ];
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiEndpoint . "?key=" . $apiKey,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData)
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    if ($curlError) {
        throw new Exception("API Request Error: $curlError");
    }
    
    if ($httpCode !== 200) {
        error_log("Gemini API HTTP Error: $httpCode");
        error_log("Response: $response");
        throw new Exception("API request failed with code: $httpCode");
    }
    
    $responseData = json_decode($response, true);
    
    if (!$responseData || !isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Invalid API response format");
    }
    
    $botMessage = $responseData['candidates'][0]['content']['parts'][0]['text'];
    
    // Detect if emergency is mentioned
    $isEmergency = false;
    $emergencyKeywords = ['emergency', 'call 911', 'ambulance', 'hospital immediately', 'emergency department', 'serious'];
    foreach ($emergencyKeywords as $keyword) {
        if (stripos($botMessage, $keyword) !== false) {
            $isEmergency = true;
            break;
        }
    }
    
    // Log chat interaction
    $logQuery = "INSERT INTO chatbot_logs (user_message, bot_response, is_emergency, created_at) 
                 VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($logQuery);
    if ($stmt) {
        $stmt->bind_param("ssi", $userMessage, $botMessage, $isEmergency);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'message' => $botMessage,
        'is_emergency' => $isEmergency,
        'suggestions' => [
            'Book Appointment' => 'appointment.html',
            'View Doctors' => 'doctors.html',
            'Hospital Beds' => 'beds.html'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Chatbot Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Unable to process your request. Please try again.',
        'fallback' => 'For immediate assistance, please contact our hospital at +91-6353439877 or visit our emergency department.'
    ]);
}

$conn->close();
?>
