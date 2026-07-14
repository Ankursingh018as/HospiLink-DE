const mongoose = require('mongoose');

const symptomKeywordSchema = new mongoose.Schema({
  keyword: {
    type: String,
    required: true,
    unique: true,
    lowercase: true
  },
  priorityLevel: {
    type: String,
    enum: ['high', 'medium', 'low'],
    required: true
  },
  category: {
    type: String,
    default: 'general'
  },
  description: {
    type: String,
    default: null
  }
}, {
  timestamps: true
});

symptomKeywordSchema.index({ keyword: 1, priorityLevel: 1 });

module.exports = mongoose.model('SymptomKeyword', symptomKeywordSchema);
