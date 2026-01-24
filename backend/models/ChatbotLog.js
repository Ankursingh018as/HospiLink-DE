const mongoose = require('mongoose');

const chatbotLogSchema = new mongoose.Schema({
  userMessage: {
    type: String,
    required: true
  },
  botResponse: {
    type: String,
    required: true
  },
  isEmergency: {
    type: Boolean,
    default: false
  },
  ipAddress: {
    type: String,
    default: null
  },
  userId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    default: null
  },
  sessionId: {
    type: String,
    default: null
  }
}, {
  timestamps: true
});

chatbotLogSchema.index({ createdAt: -1 });
chatbotLogSchema.index({ isEmergency: 1 });

module.exports = mongoose.model('ChatbotLog', chatbotLogSchema);
