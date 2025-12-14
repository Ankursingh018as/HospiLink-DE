<?php
/**
 * AI-Powered Medical Priority Analyzer
 * Uses Google Gemini API to intelligently analyze symptoms and assign priority scores
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

class AIPrioritizer {
    private $apiKey;
    private $apiEndpoint;
    
    public function __construct() {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->apiEndpoint = env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent');
        
        if (empty($this->apiKey)) {
            error_log('Warning: GEMINI_API_KEY not set in .env file');
        }
    }
    
    /**
     * Analyze symptoms using Google Gemini AI
     * Returns priority score (0-100) and detailed medical assessment
     */
    public function analyzeSymptomsWithAI($symptoms, $patientAge = null, $existingConditions = null) {
        $prompt = $this->buildMedicalPrompt($symptoms, $patientAge, $existingConditions);
        
        $response = $this->callGeminiAPI($prompt);
        
        if ($response === false) {
            // Fallback to basic keyword analysis
            return $this->fallbackAnalysis($symptoms);
        }
        
        return $this->parseAIResponse($response);
    }
    
    /**
     * Build comprehensive medical assessment prompt
     */
    private function buildMedicalPrompt($symptoms, $age, $conditions) {
        $prompt = "You are a medical triage AI assistant. Analyze the following patient case and provide a priority assessment.\n\n";
        
        $prompt .= "PATIENT SYMPTOMS:\n$symptoms\n\n";
        
        if ($age) {
            $prompt .= "PATIENT AGE: $age years\n\n";
        }
        
        if ($conditions) {
            $prompt .= "EXISTING CONDITIONS: $conditions\n\n";
        }
        
        $prompt .= "TASK: Analyze the urgency and severity of these symptoms. Provide your response in EXACTLY this JSON format:\n";
        $prompt .= "{\n";
        $prompt .= '  "priority_score": <number 0-100>,'."\n";
        $prompt .= '  "priority_level": "<critical|high|medium|low>",'."\n";
        $prompt .= '  "urgency_reason": "<brief explanation>",'."\n";
        $prompt .= '  "suspected_conditions": ["<condition1>", "<condition2>"],'."\n";
        $prompt .= '  "recommended_specialist": "<specialist type>",'."\n";
        $prompt .= '  "warning_signs": ["<sign1>", "<sign2>"],'."\n";
        $prompt .= '  "time_sensitivity": "<immediate|urgent|routine>"'."\n";
        $prompt .= "}\n\n";
        
        $prompt .= "PRIORITY SCORING GUIDELINES:\n";
        $prompt .= "90-100 (CRITICAL): Life-threatening conditions requiring immediate attention (heart attack, stroke, severe bleeding, respiratory failure, loss of consciousness)\n";
        $prompt .= "70-89 (HIGH): Serious conditions requiring urgent care within hours (severe pain, high fever, chest pain, severe injuries, acute infections)\n";
        $prompt .= "40-69 (MEDIUM): Significant symptoms requiring care within 24-48 hours (moderate pain, persistent fever, infections, chronic condition flare-ups)\n";
        $prompt .= "0-39 (LOW): Routine care, non-urgent symptoms (minor aches, routine checkups, mild symptoms, preventive care)\n\n";
        
        $prompt .= "Respond ONLY with the JSON object. No additional text.";
        
        return $prompt;
    }
    
    /**
     * Call Google Gemini API
     */
    private function callGeminiAPI($prompt) {
        $url = $this->apiEndpoint . '?key=' . $this->apiKey;
        
        $requestBody = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topK' => 32,
                'topP' => 1,
                'maxOutputTokens' => 1024
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_NONE'
                ]
            ]
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log("Gemini API Error: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Gemini API HTTP Error: $httpCode - $response");
            return false;
        }
        
        return $response;
    }
    
    /**
     * Parse AI response and extract structured data
     */
    private function parseAIResponse($response) {
        $data = json_decode($response, true);
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $this->getDefaultResponse();
        }
        
        $aiText = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Extract JSON from response
        $jsonStart = strpos($aiText, '{');
        $jsonEnd = strrpos($aiText, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            return $this->getDefaultResponse();
        }
        
        $jsonText = substr($aiText, $jsonStart, $jsonEnd - $jsonStart + 1);
        $analysis = json_decode($jsonText, true);
        
        if (!$analysis || !isset($analysis['priority_score'])) {
            return $this->getDefaultResponse();
        }
        
        // Validate and sanitize
        return [
            'priority_score' => max(0, min(100, intval($analysis['priority_score']))),
            'priority_level' => $this->validatePriorityLevel($analysis['priority_level']),
            'urgency_reason' => htmlspecialchars($analysis['urgency_reason'] ?? 'Standard assessment'),
            'suspected_conditions' => array_slice($analysis['suspected_conditions'] ?? [], 0, 5),
            'recommended_specialist' => htmlspecialchars($analysis['recommended_specialist'] ?? 'General Physician'),
            'warning_signs' => array_slice($analysis['warning_signs'] ?? [], 0, 5),
            'time_sensitivity' => $this->validateTimeSensitivity($analysis['time_sensitivity'] ?? 'routine'),
            'ai_analyzed' => true,
            'analysis_timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Validate priority level
     */
    private function validatePriorityLevel($level) {
        $validLevels = ['critical', 'high', 'medium', 'low'];
        $level = strtolower($level);
        return in_array($level, $validLevels) ? $level : 'medium';
    }
    
    /**
     * Validate time sensitivity
     */
    private function validateTimeSensitivity($time) {
        $validTimes = ['immediate', 'urgent', 'routine'];
        $time = strtolower($time);
        return in_array($time, $validTimes) ? $time : 'routine';
    }
    
    /**
     * Fallback analysis using keyword matching
     */
    private function fallbackAnalysis($symptoms) {
        $symptoms = strtolower($symptoms);
        
        // Critical keywords
        $criticalKeywords = [
            'heart attack', 'stroke', 'unconscious', 'severe bleeding', 'not breathing',
            'seizure', 'chest pain radiating', 'difficulty breathing', 'severe head injury',
            'suicide', 'overdose', 'severe allergic reaction', 'anaphylaxis'
        ];
        
        // High priority keywords
        $highKeywords = [
            'chest pain', 'severe pain', 'high fever', 'vomiting blood', 'severe headache',
            'broken bone', 'deep cut', 'severe burn', 'poisoning', 'severe abdominal pain'
        ];
        
        // Medium priority keywords
        $mediumKeywords = [
            'fever', 'vomiting', 'diarrhea', 'rash', 'cough', 'headache', 'sprain',
            'minor cut', 'infection', 'earache', 'sore throat'
        ];
        
        foreach ($criticalKeywords as $keyword) {
            if (stripos($symptoms, $keyword) !== false) {
                return [
                    'priority_score' => 95,
                    'priority_level' => 'critical',
                    'urgency_reason' => 'Critical symptoms detected requiring immediate attention',
                    'suspected_conditions' => ['Emergency condition'],
                    'recommended_specialist' => 'Emergency Medicine',
                    'warning_signs' => ['Seek immediate emergency care'],
                    'time_sensitivity' => 'immediate',
                    'ai_analyzed' => false
                ];
            }
        }
        
        foreach ($highKeywords as $keyword) {
            if (stripos($symptoms, $keyword) !== false) {
                return [
                    'priority_score' => 75,
                    'priority_level' => 'high',
                    'urgency_reason' => 'Urgent symptoms requiring prompt medical attention',
                    'suspected_conditions' => ['Acute condition'],
                    'recommended_specialist' => 'General Physician',
                    'warning_signs' => ['Monitor symptoms closely'],
                    'time_sensitivity' => 'urgent',
                    'ai_analyzed' => false
                ];
            }
        }
        
        foreach ($mediumKeywords as $keyword) {
            if (stripos($symptoms, $keyword) !== false) {
                return [
                    'priority_score' => 50,
                    'priority_level' => 'medium',
                    'urgency_reason' => 'Moderate symptoms requiring medical evaluation',
                    'suspected_conditions' => ['Common condition'],
                    'recommended_specialist' => 'General Physician',
                    'warning_signs' => ['Schedule appointment within 24-48 hours'],
                    'time_sensitivity' => 'routine',
                    'ai_analyzed' => false
                ];
            }
        }
        
        return $this->getDefaultResponse();
    }
    
    /**
     * Default response for unknown symptoms
     */
    private function getDefaultResponse() {
        return [
            'priority_score' => 30,
            'priority_level' => 'low',
            'urgency_reason' => 'Routine medical evaluation recommended',
            'suspected_conditions' => ['General assessment needed'],
            'recommended_specialist' => 'General Physician',
            'warning_signs' => ['Schedule routine appointment'],
            'time_sensitivity' => 'routine',
            'ai_analyzed' => false
        ];
    }
    
    /**
     * Get comprehensive patient assessment
     */
    public function getDetailedAssessment($symptoms, $age = null, $conditions = null) {
        $analysis = $this->analyzeSymptomsWithAI($symptoms, $age, $conditions);
        
        // Add additional context
        $analysis['assessment_summary'] = $this->generateSummary($analysis);
        $analysis['next_steps'] = $this->generateNextSteps($analysis);
        
        return $analysis;
    }
    
    /**
     * Generate human-readable summary
     */
    private function generateSummary($analysis) {
        $level = ucfirst($analysis['priority_level']);
        $summary = "Priority: {$level} (Score: {$analysis['priority_score']}/100)\n\n";
        $summary .= "Assessment: {$analysis['urgency_reason']}\n\n";
        
        if (!empty($analysis['suspected_conditions'])) {
            $summary .= "Possible conditions: " . implode(', ', $analysis['suspected_conditions']) . "\n\n";
        }
        
        $summary .= "Recommended specialist: {$analysis['recommended_specialist']}\n";
        $summary .= "Time sensitivity: " . ucfirst($analysis['time_sensitivity']);
        
        return $summary;
    }
    
    /**
     * Generate recommended next steps
     */
    private function generateNextSteps($analysis) {
        switch ($analysis['time_sensitivity']) {
            case 'immediate':
                return [
                    'Call emergency services (ambulance) immediately',
                    'Do not drive yourself to hospital',
                    'Stay calm and follow emergency operator instructions',
                    'Prepare list of current medications'
                ];
            
            case 'urgent':
                return [
                    'Seek medical attention within the next 2-4 hours',
                    'Visit emergency department or urgent care',
                    'Monitor symptoms for any worsening',
                    'Bring list of medications and medical history'
                ];
            
            default:
                return [
                    'Schedule appointment with ' . $analysis['recommended_specialist'],
                    'Note any changes in symptoms',
                    'Prepare questions for your doctor',
                    'Bring medical records if available'
                ];
        }
    }
}
?>
