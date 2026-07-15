const axios = require('axios');
const ChatbotLog = require('../models/ChatbotLog');

// @desc    Get chatbot response from Gemini AI
// @route   POST /api/chatbot/message
// @access  Public
exports.getChatbotResponse = async (req, res) => {
  try {
    const { message } = req.body;

    if (!message) {
      return res.status(400).json({
        success: false,
        error: 'Message is required'
      });
    }

    // Enhanced medical system prompt
    const systemPrompt = `You are an expert healthcare assistant with deep medical knowledge for HospiLink Hospital. 
Analyze the user's symptoms and provide structured recommendations in this format:

[DEBUG] Symptom Analysis: [Describe what the symptoms indicate]
[HOSPITAL] Possible Causes: [List most common, less common, and serious conditions]
[MEDICINE] Recommended Actions: [Provide immediate steps, self-care tips, and when to seek professional help]
[WARNING] Warning Signs: [List symptoms that require immediate medical attention]
[INFO] Our Recommendations: [Specific guidance for visiting hospital or consultation]

For emergencies (chest pain, severe bleeding, difficulty breathing, unconsciousness, severe burns, poisoning, overdose, anaphylaxis, stroke symptoms, seizures, high fever >103°F), start with:
[ALERT] EMERGENCY: This is a medical emergency! Please visit our hospital emergency department immediately or call emergency services!

Match the user's language (English, Hindi, or Hinglish).`;

    // Call Gemini API
    const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=${process.env.GEMINI_API_KEY}`;
    
    const requestData = {
      contents: [{
        parts: [{
          text: `${systemPrompt}\n\nUser Query: ${message}`
        }]
      }],
      generationConfig: {
        temperature: 0.8,
        maxOutputTokens: 1500,
        topK: 40,
        topP: 0.95
      }
    };

    const response = await axios.post(apiUrl, requestData, {
      headers: {
        'Content-Type': 'application/json'
      }
    });

    const botResponse = response.data.candidates[0].content.parts[0].text;

    // Check for emergency keywords
    const emergencyKeywords = [
      'emergency', 'call 911', 'call emergency', 'ambulance', 
      'hospital immediately', 'emergency department', 'serious', 
      '[ALERT] EMERGENCY'
    ];
    
    const isEmergency = emergencyKeywords.some(keyword => 
      botResponse.toLowerCase().includes(keyword.toLowerCase())
    );

    // Log conversation
    await ChatbotLog.create({
      userMessage: message,
      botResponse,
      isEmergency,
      ipAddress: req.ip,
      userId: req.user ? req.user._id : null
    });

    res.status(200).json({
      success: true,
      message: botResponse,
      is_emergency: isEmergency,
      suggestions: isEmergency ? [
        'Call Emergency Services',
        'Visit Emergency Department',
        'Contact Doctor'
      ] : [
        'Book Appointment',
        'Consult Doctor',
        'View Medical History'
      ]
    });

  } catch (error) {
    console.error('Chatbot error:', error.response?.data || error.message);
    res.status(500).json({
      success: false,
      error: 'Failed to get response from chatbot',
      message: error.message
    });
  }
};

// @desc    Get chatbot conversation history
// @route   GET /api/chatbot/history
// @access  Private
exports.getChatbotHistory = async (req, res) => {
  try {
    const { limit = 20, isEmergency } = req.query;

    let query = {};
    if (req.user) {
      query.userId = req.user._id;
    }
    if (isEmergency !== undefined) {
      query.isEmergency = isEmergency === 'true';
    }

    const history = await ChatbotLog.find(query)
      .sort({ createdAt: -1 })
      .limit(parseInt(limit));

    res.status(200).json({
      success: true,
      count: history.length,
      history
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};
