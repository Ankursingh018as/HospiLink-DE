const express = require('express');
const router = express.Router();
const { getChatbotResponse, getChatbotHistory } = require('../controllers/chatbotController');
const { protect } = require('../middleware/auth');

// Public route for chatbot
router.post('/message', getChatbotResponse);

// Protected route for history
router.get('/history', protect, getChatbotHistory);

module.exports = router;
