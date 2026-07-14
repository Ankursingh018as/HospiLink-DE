import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { appointmentAPI } from '../services/api';
import Sidebar from '../components/Sidebar';
import '../styles/original-dashboard.css';

const DoctorDashboard = () => {
  const { user } = useAuth();
  const [appointments, setAppointments] = useState([]);
  const [unclaimedAppointments, setUnclaimedAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all'); // all, today, pending
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchAppointments();
  }, [filter]);

  const fetchAppointments = async () => {
    try {
      setLoading(true);
      setError('');
      
      // Fetch doctor's appointments
      const myAppointmentsResponse = await appointmentAPI.getAll({ doctorId: user._id });
      setAppointments(myAppointmentsResponse.data.appointments || []);

      // Fetch unclaimed appointments
      const unclaimedResponse = await appointmentAPI.getAll({ status: 'pending' });
      const unclaimed = (unclaimedResponse.data.appointments || []).filter(
        apt => !apt.assignedDoctor
      );
      setUnclaimedAppointments(unclaimed);
    } catch (err) {
      console.error('Error fetching appointments:', err);
      setError('Failed to load appointments');
    } finally {
      setLoading(false);
    }
  };

  const handleClaimAppointment = async (appointmentId) => {
    try {
      setLoading(true);
      await appointmentAPI.claim(appointmentId);
      setSuccess('Appointment claimed successfully!');
      fetchAppointments();
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to claim appointment');
      setTimeout(() => setError(''), 3000);
    } finally {
      setLoading(false);
    }
  };

  const getPriorityBadgeClass = (priority) => {
    switch (priority) {
      case 'high': return 'badge-danger';
      case 'medium': return 'badge-warning';
      case 'low': return 'badge-success';
      default: return 'badge-secondary';
    }
  };

  const getPriorityIcon = (priority) => {
    switch (priority) {
      case 'high': return '🔴';
      case 'medium': return '🟡';
      case 'low': return '🟢';
      default: return '⚪';
    }
  };

  const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'confirmed': return 'badge-success';
      case 'pending': return 'badge-warning';
      case 'completed': return 'badge-info';
      case 'cancelled': return 'badge-danger';
      default: return 'badge-secondary';
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  // Sort appointments by priority (high -> medium -> low) and date
  const sortedAppointments = [...appointments].sort((a, b) => {
    const priorityOrder = { high: 0, medium: 1, low: 2 };
    const priorityCompare = priorityOrder[a.priorityLevel] - priorityOrder[b.priorityLevel];
    if (priorityCompare !== 0) return priorityCompare;
    return new Date(a.appointmentDate) - new Date(b.appointmentDate);
  });

  const sortedUnclaimedAppointments = [...unclaimedAppointments].sort((a, b) => {
    const priorityOrder = { high: 0, medium: 1, low: 2 };
    return priorityOrder[a.priorityLevel] - priorityOrder[b.priorityLevel];
  });

  // Filter appointments
  const filteredAppointments = sortedAppointments.filter(apt => {
    if (filter === 'today') {
      const today = new Date().toDateString();
      return new Date(apt.appointmentDate).toDateString() === today;
    }
    if (filter === 'pending') {
      return apt.status === 'pending' || apt.status === 'confirmed';
    }
    return true;
  });

  const todayAppointments = appointments.filter(apt => {
    const today = new Date().toDateString();
    return new Date(apt.appointmentDate).toDateString() === today;
  });

  return (
    <div className="dashboard-container">
      <Sidebar />
      
      <main className="main-content">
        <header className="dashboard-header">
        <div className="header-content">
          <div>
            <h1>Dr. {user?.firstName} {user?.lastName} 👨‍⚕️</h1>
            <p>Manage your appointments and patient care</p>
          </div>
          <div className="filter-buttons">
            <button 
              className={`filter-btn ${filter === 'all' ? 'active' : ''}`}
              onClick={() => setFilter('all')}
            >
              All
            </button>
            <button 
              className={`filter-btn ${filter === 'today' ? 'active' : ''}`}
              onClick={() => setFilter('today')}
            >
              Today
            </button>
            <button 
              className={`filter-btn ${filter === 'pending' ? 'active' : ''}`}
              onClick={() => setFilter('pending')}
            >
              Pending
            </button>
          </div>
        </div>
      </header>

      {/* Alerts */}
      {success && (
        <div className="alert alert-success">
          <span>✅</span> {success}
        </div>
      )}
      {error && (
        <div className="alert alert-danger">
          <span>⚠️</span> {error}
        </div>
      )}

      {/* Stats Cards */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }}>
            📅
          </div>
          <div className="stat-content">
            <h3>{todayAppointments.length}</h3>
            <p>Today's Appointments</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' }}>
            ⏰
          </div>
          <div className="stat-content">
            <h3>{appointments.filter(a => a.status === 'pending' || a.status === 'confirmed').length}</h3>
            <p>Pending Appointments</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' }}>
            🔴
          </div>
          <div className="stat-content">
            <h3>{appointments.filter(a => a.priorityLevel === 'high').length}</h3>
            <p>High Priority Cases</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' }}>
            🆕
          </div>
          <div className="stat-content">
            <h3>{unclaimedAppointments.length}</h3>
            <p>Unclaimed Appointments</p>
          </div>
        </div>
      </div>

      {/* Unclaimed Appointments Section */}
      {unclaimedAppointments.length > 0 && (
        <div className="content-section" style={{ marginBottom: '30px' }}>
          <div className="section-header">
            <h2>🆕 Unclaimed Appointments (Priority Queue)</h2>
            <p className="section-subtitle">AI-prioritized appointments waiting for doctor assignment</p>
          </div>

          <div className="unclaimed-grid">
            {sortedUnclaimedAppointments.map((appointment) => (
              <div key={appointment._id} className="unclaimed-card">
                <div className="priority-indicator">
                  {getPriorityIcon(appointment.priorityLevel)}
                  <span className={`badge ${getPriorityBadgeClass(appointment.priorityLevel)}`}>
                    {appointment.priorityLevel} priority
                  </span>
                  {appointment.priorityScore && (
                    <span className="priority-score">Score: {appointment.priorityScore}</span>
                  )}
                </div>

                <div className="unclaimed-content">
                  <div className="patient-info">
                    <h3>👤 {appointment.patient?.firstName} {appointment.patient?.lastName}</h3>
                    <p className="patient-details">
                      {appointment.patient?.age && `${appointment.patient.age}y`}
                      {appointment.patient?.gender && ` • ${appointment.patient.gender}`}
                    </p>
                  </div>

                  <div className="appointment-details">
                    <div className="detail-row">
                      <span className="detail-icon">📅</span>
                      <span>{formatDate(appointment.appointmentDate)}</span>
                    </div>
                    <div className="detail-row">
                      <span className="detail-icon">💊</span>
                      <span className="symptoms">{appointment.symptoms}</span>
                    </div>
                    {appointment.notes && (
                      <div className="detail-row">
                        <span className="detail-icon">📝</span>
                        <span>{appointment.notes}</span>
                      </div>
                    )}
                  </div>

                  <button 
                    className="btn btn-primary btn-claim"
                    onClick={() => handleClaimAppointment(appointment._id)}
                    disabled={loading}
                  >
                    <span>✋</span> Claim Appointment
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* My Appointments Section */}
      <div className="content-section">
        <div className="section-header">
          <h2>📋 My Appointments ({filteredAppointments.length})</h2>
          <p className="section-subtitle">Sorted by priority: High → Medium → Low</p>
        </div>

        {loading ? (
          <div className="loading-spinner">
            <div className="spinner"></div>
            <p>Loading appointments...</p>
          </div>
        ) : filteredAppointments.length === 0 ? (
          <div className="empty-state">
            <span className="empty-icon">📅</span>
            <h3>No Appointments Found</h3>
            <p>
              {filter === 'today' && 'No appointments scheduled for today'}
              {filter === 'pending' && 'No pending appointments'}
              {filter === 'all' && 'No appointments assigned yet'}
            </p>
          </div>
        ) : (
          <div className="appointments-table">
            {filteredAppointments.map((appointment) => (
              <div key={appointment._id} className="appointment-row">
                <div className="appointment-priority">
                  {getPriorityIcon(appointment.priorityLevel)}
                  <span className={`badge ${getPriorityBadgeClass(appointment.priorityLevel)}`}>
                    {appointment.priorityLevel}
                  </span>
                </div>

                <div className="appointment-patient">
                  <h4>👤 {appointment.patient?.firstName} {appointment.patient?.lastName}</h4>
                  <p className="text-muted">
                    {appointment.patient?.email} • {appointment.patient?.phone}
                  </p>
                </div>

                <div className="appointment-datetime">
                  <div className="datetime-item">
                    <span className="datetime-icon">📅</span>
                    <span>{new Date(appointment.appointmentDate).toLocaleDateString()}</span>
                  </div>
                  <div className="datetime-item">
                    <span className="datetime-icon">🕐</span>
                    <span>{formatTime(appointment.appointmentDate)}</span>
                  </div>
                </div>

                <div className="appointment-info">
                  <div className="info-item">
                    <strong>Symptoms:</strong>
                    <span>{appointment.symptoms}</span>
                  </div>
                  {appointment.diagnosis && (
                    <div className="info-item">
                      <strong>Diagnosis:</strong>
                      <span>{appointment.diagnosis}</span>
                    </div>
                  )}
                </div>

                <div className="appointment-status">
                  <span className={`badge ${getStatusBadgeClass(appointment.status)}`}>
                    {appointment.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
      </main>
    </div>
  );
};

export default DoctorDashboard;
