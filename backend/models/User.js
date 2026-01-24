const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

const userSchema = new mongoose.Schema({
  // 🔐 Common Auth Details (All Roles)
  firstName: {
    type: String,
    required: true,
    trim: true
  },
  lastName: {
    type: String,
    required: true,
    trim: true
  },
  email: {
    type: String,
    required: true,
    unique: true,
    lowercase: true
  },
  password: {
    type: String,
    required: true,
    minlength: 6,
    select: false
  },
  phone: {
    type: String,
    required: true
  },

  role: {
    type: String,
    enum: ['patient', 'doctor', 'staff', 'admin'],
    default: 'patient'
  },

  status: {
    type: String,
    enum: ['active', 'inactive'],
    default: 'active'
  },

  // 👤 Patient-Specific Profile
  patientProfile: {
    dateOfBirth: Date,
    gender: {
      type: String,
      enum: ['Male', 'Female', 'Others']
    },
    bloodGroup: String,
    address: String,
    emergencyContact: {
      name: String,
      phone: String
    }
  },

  // 🩺 Doctor-Specific Profile
  doctorProfile: {
    specialization: String,
    department: String,
    licenseNumber: String,
    yearsOfExperience: Number,
    consultationFee: Number,
    availabilityStatus: {
      type: String,
      enum: ['available', 'on-leave', 'busy'],
      default: 'available'
    }
  },

  // 👨‍⚕️ Staff / Nurse Profile
  staffProfile: {
    staffId: String,
    department: String,
    shift: {
      type: String,
      enum: ['morning', 'evening', 'night']
    }
  },

  // 🛠 Admin Controls
  adminProfile: {
    permissions: [
      {
        type: String,
        enum: ['manage-users', 'manage-beds', 'view-reports', 'full-access']
      }
    ]
  },

  profileImage: {
    type: String,
    default: null
  }

}, { timestamps: true });

// Hash password before saving
userSchema.pre('save', async function(next) {
  if (!this.isModified('password')) {
    return next();
  }
  const salt = await bcrypt.genSalt(10);
  this.password = await bcrypt.hash(this.password, salt);
  next();
});

// Method to compare password
userSchema.methods.comparePassword = async function(candidatePassword) {
  return await bcrypt.compare(candidatePassword, this.password);
};

// Virtual for full name
userSchema.virtual('fullName').get(function() {
  return `${this.firstName} ${this.lastName}`;
});

module.exports = mongoose.model('User', userSchema);
