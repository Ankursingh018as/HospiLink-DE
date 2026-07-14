import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { appointmentAPI } from '../services/api';
import Sidebar from '../components/Sidebar';
import '../styles/original-dashboard.css';

const PatientDashboard = () => {
  const { user } = useAuth();
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchAppointments();
  }, []);

  const fetchAppointments = async () => {
    try {
      setLoading(true);
      const response = await appointmentAPI.getAll({ patientId: user._id });
      setAppointments(response.data.appointments || []);
    } catch (err) {
      console.error('Error fetching appointments:', err);
      setError('Failed to load appointments');
    } finally {
      setLoading(false);
    }
  };

  const totalAppointments = appointments.length;
  const pendingAppointments = appointments.filter(a => a.status === 'pending').length;
  const confirmedAppointments = appointments.filter(a => a.status === 'confirmed').length;
  const completedAppointments = appointments.filter(a => a.status === 'completed').length;

  return (
    <div className="dashboard-container">
      <Sidebar />
      
      <main className="main-content">
        <header className="dashboard-header">
          <h1>Welcome, {user?.firstName}!</h1>
          <div className="user-info">
            <span className="user-role">
              <i className="fas fa-user"></i> Patient
            </span>
          </div>
        </header>

        <section className="content-section">
          <div className="section-header">
            <h2>Dashboard Overview</h2>
          </div>
          
          <div className="stats-grid-enhanced">
            <div className="stat-card-enhanced gradient-blue">
              <div className="stat-icon-enhanced">
                <i className="fas fa-calendar-check"></i>
              </div>
              <div className="stat-content-enhanced">
                <h3>{totalAppointments}</h3>
                <p>Total Appointments</p>
              </div>
            </div>

            <div className="stat-card-enhanced gradient-orange">
              <div className="stat-icon-enhanced">
                <i className="fas fa-clock"></i>
              </div>
              <div className="stat-content-enhanced">
                <h3>{pendingAppointments}</h3>
                <p>Pending</p>
              </div>
            </div>

            <div className="stat-card-enhanced gradient-green">
              <div className="stat-icon-enhanced">
                <i className="fas fa-check-circle"></i>
              </div>
              <div className="stat-content-enhanced">
                <h3>{confirmedAppointments}</h3>
                <p>Confirmed</p>
              </div>
            </div>

            <div className="stat-card-enhanced gradient-purple">
              <div className="stat-icon-enhanced">
                <i className="fas fa-clipboard-check"></i>
              </div>
              <div className="stat-content-enhanced">
                <h3>{completedAppointments}</h3>
                <p>Completed</p>
              </div>
            </div>
          </div>
        </section>

        <section className="content-section">
          <div className="section-header">
            <h2>My Appointments</h2>
            <span className="badge-count">{appointments.length}</span>
          </div>

          {loading ? (
            <div className="loading-container">
              <div className="spinner-border"></div>
              <p>Loading appointments...</p>
            </div>
          ) : appointments.length === 0 ? (
            <div className="empty-state">
              <i className="fas fa-calendar-times empty-icon"></i>
              <h3>No Appointments Yet</h3>
              <p>Book your first appointment to get started.</p>
            </div>
          ) : (
            <div className="appointments-list">
              {appointments.map((appointment) => (
                <div key={appointment._id} className="appointment-card-original">
                  <div className="appointment-header-original">
                    <span>Appointment</span>
                    <span className="badge">{appointment.status}</span>
                  </div>
                  <div className="appointment-body-original">
                    <p><strong>Date:</strong> {new Date(appointment.appointmentDate).toLocaleString()}</p>
                    <p><strong>Symptoms:</strong> {appointment.symptoms}</p>
                    {appointment.assignedDoctor && (
                      <p><strong>Doctor:</strong> Dr. {appointment.assignedDoctor.firstName} {appointment.assignedDoctor.lastName}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>
      </main>
    </div>
  );
};

export default PatientDashboard;
