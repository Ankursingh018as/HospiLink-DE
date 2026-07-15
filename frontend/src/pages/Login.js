import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import '../styles/auth.css';

const Login = () => {
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const { login } = useAuth();
  const navigate = useNavigate();

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const result = await login(formData.email, formData.password);

      if (result.success) {
        // Redirect based on user role
        const { role } = result.user;
        switch (role) {
          case 'patient':
            navigate('/patient/dashboard');
            break;
          case 'doctor':
            navigate('/doctor/dashboard');
            break;
          case 'staff':
          case 'nurse':
            navigate('/staff/dashboard');
            break;
          case 'admin':
            navigate('/admin/dashboard');
            break;
          default:
            navigate('/');
        }
      } else {
        setError(result.message);
      }
    } catch (err) {
      setError('An unexpected error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="signin-container">
      <div className="signin-form-wrapper">
        <div className="signin-header">
          <div className="logo">
            <span className="logo-icon"><i className="ri-hospital-line"></i></span>
            <span className="logo-text">HospiLink</span>
          </div>
          <h2>Welcome Back</h2>
          <p>Sign in to your account</p>
        </div>

        <form onSubmit={handleSubmit} className="signin-form">
          {error && (
            <div className="error-message">
              <i className="ri-alert-line" style={{ marginRight: '6px' }}></i> {error}
            </div>
          )}

          <div className="form-group">
            <label htmlFor="email">
              <span className="icon"><i className="ri-mail-line"></i></span>
              Email Address
            </label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              placeholder="Enter your email"
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">
              <span className="icon"><i className="ri-lock-line"></i></span>
              Password
            </label>
            <input
              type="password"
              id="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              placeholder="Enter your password"
              required
              minLength="6"
            />
          </div>

          <div className="form-options">
            <label className="remember-me">
              <input type="checkbox" />
              <span>Remember me</span>
            </label>
            <Link to="/forgot-password" className="forgot-password">
              Forgot Password?
            </Link>
          </div>

          <button 
            type="submit" 
            className="signin-button"
            disabled={loading}
          >
            {loading ? 'Signing in...' : 'Sign In'}
          </button>

          <div className="signup-link">
            Don't have an account? <Link to="/register">Sign Up</Link>
          </div>
        </form>

        <div className="quick-login-section">
          <div className="divider">
            <span>Quick Access</span>
          </div>
          <div className="demo-accounts">
            <button 
              type="button" 
              className="demo-btn"
              onClick={() => {
                setFormData({ email: 'patient@test.com', password: 'password123' });
              }}
            >
              <span><i className="ri-user-line" style={{ marginRight: '6px' }}></i></span> Patient Demo
            </button>
            <button 
              type="button" 
              className="demo-btn"
              onClick={() => {
                setFormData({ email: 'doctor@test.com', password: 'password123' });
              }}
            >
              <span><i className="ri-stethoscope-line" style={{ marginRight: '6px' }}></i></span> Doctor Demo
            </button>
            <button 
              type="button" 
              className="demo-btn"
              onClick={() => {
                setFormData({ email: 'admin@test.com', password: 'password123' });
              }}
            >
              <span><i className="ri-settings-4-line" style={{ marginRight: '6px' }}></i></span> Admin Demo
            </button>
          </div>
        </div>
      </div>

      <div className="signin-image">
        <div className="image-overlay">
          <h1>Healthcare Made Smarter</h1>
          <p>AI-powered hospital management for better patient care</p>
          <div className="features">
            <div className="feature-item">
              <span className="feature-icon"><i className="ri-robot-line"></i></span>
              <span>AI Prioritization</span>
            </div>
            <div className="feature-item">
              <span className="feature-icon"><i className="ri-qr-code-line"></i></span>
              <span>QR Tracking</span>
            </div>
            <div className="feature-item">
              <span className="feature-icon"><i className="ri-chat-3-line"></i></span>
              <span>Smart Chatbot</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;
