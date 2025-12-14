<?php
// AI Symptom Analyzer for HospiLink
// Analyzes patient symptoms and assigns priority levels

class SymptomAnalyzer {
    private $conn;
    private $priorityWeights = [
        'high' => 100,
        'medium' => 50,
        'low' => 25
    ];
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Analyze symptoms and calculate priority
     * @param string $symptoms - Patient's symptom description
     * @return array - Priority level and score
     */
    public function analyzeSymptoms($symptoms) {
        $symptoms = strtolower(trim($symptoms));
        
        // Get all symptom keywords from database
        $keywordsQuery = "SELECT keyword, priority_level FROM symptom_keywords";
        $result = $this->conn->query($keywordsQuery);
        
        $matchedKeywords = [];
        $priorityScores = [];
        
        // Check for keyword matches
        while ($row = $result->fetch_assoc()) {
            $keyword = strtolower($row['keyword']);
            
            // Check if keyword exists in symptoms
            if (strpos($symptoms, $keyword) !== false) {
                $matchedKeywords[] = [
                    'keyword' => $keyword,
                    'priority' => $row['priority_level']
                ];
                
                // Add score based on priority level
                $priorityScores[] = $this->priorityWeights[$row['priority_level']];
            }
        }
        
        // Calculate final priority
        if (empty($priorityScores)) {
            // No keywords matched - default to medium priority
            return [
                'priority_level' => 'medium',
                'priority_score' => 50,
                'matched_keywords' => [],
                'analysis' => 'No specific symptoms detected. Please provide more details.'
            ];
        }
        
        // Get highest priority level
        $maxScore = max($priorityScores);
        
        // Determine priority level based on score
        if ($maxScore >= 100) {
            $priorityLevel = 'high';
        } elseif ($maxScore >= 50) {
            $priorityLevel = 'medium';
        } else {
            $priorityLevel = 'low';
        }
        
        // Calculate average score for more nuanced prioritization
        $avgScore = array_sum($priorityScores) / count($priorityScores);
        $finalScore = max($maxScore, $avgScore);
        
        // Generate analysis message
        $analysis = $this->generateAnalysis($priorityLevel, $matchedKeywords);
        
        return [
            'priority_level' => $priorityLevel,
            'priority_score' => round($finalScore),
            'matched_keywords' => $matchedKeywords,
            'analysis' => $analysis
        ];
    }
    
    /**
     * Generate human-readable analysis
     */
    private function generateAnalysis($priorityLevel, $matchedKeywords) {
        $keywordList = array_column($matchedKeywords, 'keyword');
        $keywordString = implode(', ', $keywordList);
        
        switch ($priorityLevel) {
            case 'high':
                return "⚠️ HIGH PRIORITY: Your symptoms ($keywordString) indicate a medical emergency. You will be prioritized for immediate attention. Please seek emergency care if symptoms worsen.";
                return "⚡ HIGH PRIORITY: Your symptoms ($keywordString) require urgent medical attention. You will be seen by a doctor as soon as possible.";
            
            case 'medium':
                return "📋 MEDIUM PRIORITY: Your symptoms ($keywordString) will be evaluated by a doctor. Expected wait time may vary based on emergency cases.";
            
            case 'low':
                return "✓ LOW PRIORITY: Your request ($keywordString) is noted. You will be scheduled based on availability. This is suitable for routine care and follow-ups.";
            
            default:
                return "Your symptoms have been recorded and will be reviewed by medical staff.";
        }
    }
    
    /**
     * Get appointment suggestions based on priority
     */
    public function getAppointmentSuggestions($priorityLevel) {
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        switch ($priorityLevel) {
            case 'high':
                return [
                    'suggested_date' => $currentDate,
                    'suggested_time' => date('H:i:s', strtotime('+1 hour')),
                    'message' => 'Emergency appointment recommended within 1 hour',
                    'wait_time' => 'Immediate to 1 hour'
                ];
            
            case 'medium':
                $suggestedDate = date('Y-m-d', strtotime('+3 days'));
                return [
                    'suggested_date' => $suggestedDate,
                    'suggested_time' => '10:00:00',
                    'message' => 'Appointment recommended within 3-5 days',
                    'wait_time' => '3-5 days'
                ];
            
            case 'low':
                $suggestedDate = date('Y-m-d', strtotime('+7 days'));
                return [
                    'suggested_date' => $suggestedDate,
                    'suggested_time' => '14:00:00',
                    'message' => 'Routine appointment can be scheduled at your convenience',
                    'wait_time' => '1-2 weeks'
                ];
            
            default:
                return [
                    'suggested_date' => date('Y-m-d', strtotime('+3 days')),
                    'suggested_time' => '10:00:00',
                    'message' => 'Standard appointment scheduling',
                    'wait_time' => '3-7 days'
                ];
        }
    }
    
    /**
     * Get available doctors based on priority and specialization
     */
    public function suggestDoctors($priorityLevel, $symptoms = '') {
        // Simple specialization matching
        $specialization = $this->detectSpecialization($symptoms);
        
        $query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as doctor_name, 
                  specialization, department 
                  FROM users 
                  WHERE role = 'doctor' AND status = 'active'";
        
        if ($specialization) {
            $query .= " AND (specialization LIKE '%$specialization%' OR department LIKE '%$specialization%')";
        }
        
        $query .= " ORDER BY user_id LIMIT 5";
        
        $result = $this->conn->query($query);
        $doctors = [];
        
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        
        return $doctors;
    }
    
    /**
     * Detect medical specialization from symptoms
     */
    private function detectSpecialization($symptoms) {
        $symptoms = strtolower($symptoms);
        
        $specializations = [
            'cardiology' => ['heart', 'chest pain', 'cardiac', 'blood pressure', 'heart attack'],
            'pediatrics' => ['child', 'baby', 'infant', 'pediatric', 'kid'],
            'orthopedics' => ['bone', 'fracture', 'joint', 'back pain', 'sprain'],
            'dermatology' => ['skin', 'rash', 'acne', 'eczema', 'dermatitis'],
            'neurology' => ['headache', 'migraine', 'seizure', 'stroke', 'brain'],
            'general' => ['fever', 'cold', 'flu', 'cough', 'common']
        ];
        
        foreach ($specializations as $specialty => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($symptoms, $keyword) !== false) {
                    return $specialty;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * Calculate overall hospital load and adjust priorities
     */
    public function getHospitalLoadFactor() {
        $today = date('Y-m-d');
        
        // Count pending appointments for today
        $query = "SELECT COUNT(*) as count FROM appointments 
                  WHERE appointment_date = '$today' 
                  AND status = 'pending'";
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        $pendingCount = $row['count'];
        
        // Calculate load factor (0-1)
        if ($pendingCount < 10) {
            return 0.2; // Low load
        } elseif ($pendingCount < 25) {
            return 0.5; // Medium load
        } elseif ($pendingCount < 50) {
            return 0.75; // High load
        } else {
            return 1.0; // Critical load
        }
    }
}
?>
