import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { adminAPI } from '../services/api';
import Sidebar from '../components/Sidebar';
import '../styles/original-dashboard.css';

const AdminDashboard = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [users, setUsers] = useState([]);
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview'); // overview, users, logs
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchData();
  }, [activeTab]);

  const fetchData = async () => {
    try {
      setLoading(true);
      setError('');
      
      if (activeTab === 'overview' || !stats) {
        const statsResponse = await adminAPI.getStats();
        setStats(statsResponse.data);
      }
      
      if (activeTab === 'users') {
        const usersResponse = await adminAPI.getUsers();
        setUsers(usersResponse.data.users || []);
      }
      
      if (activeTab === 'logs') {
        const logsResponse = await adminAPI.getLogs();
        setLogs(logsResponse.data.logs || []);
      }
    } catch (err) {
      console.error('Error fetching data:', err);
      setError('Failed to load data');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteUser = async (userId) => {
    if (!window.confirm('Are you sure you want to delete this user?')) return;
    
    try {
      setLoading(true);
      await adminAPI.deleteUser(userId);
      setSuccess('User deleted successfully!');
      fetchData();
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to delete user');
      setTimeout(() => setError(''), 3000);
    } finally {
      setLoading(false);
    }
  };

  const getRoleBadgeClass = (role) => {
    switch (role) {
      case 'admin': return 'badge-danger';
      case 'doctor': return 'badge-info';
      case 'staff': return 'badge-warning';
      case 'nurse': return 'badge-warning';
      case 'patient': return 'badge-success';
      default: return 'badge-secondary';
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <div className="dashboard-container">
      <Sidebar />
      
      <main className="main-content">
        <header className="dashboard-header">
        <div className="header-content">
          <div>
            <h1>Admin Dashboard - {user?.firstName} 👑</h1>
            <p>System management and monitoring</p>
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

      {/* Tabs */}
      <div className="tabs">
        <button
          className={`tab ${activeTab === 'overview' ? 'active' : ''}`}
          onClick={() => setActiveTab('overview')}
        >
          📊 Overview
        </button>
        <button
          className={`tab ${activeTab === 'users' ? 'active' : ''}`}
          onClick={() => setActiveTab('users')}
        >
          👥 Users
        </button>
        <button
          className={`tab ${activeTab === 'logs' ? 'active' : ''}`}
          onClick={() => setActiveTab('logs')}
        >
          📝 Activity Logs
        </button>
      </div>

      {/* Overview Tab */}
      {activeTab === 'overview' && stats && (
        <>
          {/* Stats Grid */}
          <div className="stats-grid large">
            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }}>
                👥
              </div>
              <div className="stat-content">
                <h3>{stats.totalUsers || 0}</h3>
                <p>Total Users</p>
                <small className="stat-detail">
                  {stats.usersByRole?.patient || 0} patients • {stats.usersByRole?.doctor || 0} doctors
                </small>
              </div>
            </div>

            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' }}>
                📅
              </div>
              <div className="stat-content">
                <h3>{stats.totalAppointments || 0}</h3>
                <p>Total Appointments</p>
                <small className="stat-detail">
                  {stats.appointmentsByStatus?.pending || 0} pending • {stats.appointmentsByStatus?.confirmed || 0} confirmed
                </small>
              </div>
            </div>

            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' }}>
                🛏️
              </div>
              <div className="stat-content">
                <h3>{stats.totalBeds || 0}</h3>
                <p>Total Beds</p>
                <small className="stat-detail">
                  {stats.bedsByStatus?.available || 0} available • {stats.bedsByStatus?.occupied || 0} occupied
                </small>
              </div>
            </div>

            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' }}>
                🏥
              </div>
              <div className="stat-content">
                <h3>{stats.totalAdmissions || 0}</h3>
                <p>Active Admissions</p>
                <small className="stat-detail">
                  {stats.admissionsByStatus?.active || 0} active patients
                </small>
              </div>
            </div>
          </div>

          {/* Charts Section */}
          <div className="charts-grid">
            <div className="chart-card">
              <h3>📈 Appointments by Priority</h3>
              <div className="priority-bars">
                <div className="priority-bar-item">
                  <span className="priority-label">🔴 High Priority</span>
                  <div className="priority-bar">
                    <div 
                      className="priority-fill high"
                      style={{ width: `${(stats.appointmentsByPriority?.high || 0) / (stats.totalAppointments || 1) * 100}%` }}
                    ></div>
                  </div>
                  <span className="priority-count">{stats.appointmentsByPriority?.high || 0}</span>
                </div>
                <div className="priority-bar-item">
                  <span className="priority-label">🟡 Medium Priority</span>
                  <div className="priority-bar">
                    <div 
                      className="priority-fill medium"
                      style={{ width: `${(stats.appointmentsByPriority?.medium || 0) / (stats.totalAppointments || 1) * 100}%` }}
                    ></div>
                  </div>
                  <span className="priority-count">{stats.appointmentsByPriority?.medium || 0}</span>
                </div>
                <div className="priority-bar-item">
                  <span className="priority-label">🟢 Low Priority</span>
                  <div className="priority-bar">
                    <div 
                      className="priority-fill low"
                      style={{ width: `${(stats.appointmentsByPriority?.low || 0) / (stats.totalAppointments || 1) * 100}%` }}
                    ></div>
                  </div>
                  <span className="priority-count">{stats.appointmentsByPriority?.low || 0}</span>
                </div>
              </div>
            </div>

            <div className="chart-card">
              <h3>👥 Users by Role</h3>
              <div className="role-stats">
                <div className="role-item">
                  <span className="role-icon">👨‍⚕️</span>
                  <div className="role-info">
                    <strong>Doctors</strong>
                    <span className="role-count">{stats.usersByRole?.doctor || 0}</span>
                  </div>
                </div>
                <div className="role-item">
                  <span className="role-icon">🏥</span>
                  <div className="role-info">
                    <strong>Staff</strong>
                    <span className="role-count">{stats.usersByRole?.staff || 0}</span>
                  </div>
                </div>
                <div className="role-item">
                  <span className="role-icon">👥</span>
                  <div className="role-info">
                    <strong>Patients</strong>
                    <span className="role-count">{stats.usersByRole?.patient || 0}</span>
                  </div>
                </div>
                <div className="role-item">
                  <span className="role-icon">👑</span>
                  <div className="role-info">
                    <strong>Admins</strong>
                    <span className="role-count">{stats.usersByRole?.admin || 0}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </>
      )}

      {/* Users Tab */}
      {activeTab === 'users' && (
        <div className="content-section">
          <div className="section-header">
            <h2>👥 User Management ({users.length})</h2>
          </div>

          {loading ? (
            <div className="loading-spinner">
              <div className="spinner"></div>
              <p>Loading users...</p>
            </div>
          ) : (
            <div className="users-table">
              <table>
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((usr) => (
                    <tr key={usr._id}>
                      <td>
                        <div className="user-cell">
                          <strong>{usr.firstName} {usr.lastName}</strong>
                        </div>
                      </td>
                      <td>{usr.email}</td>
                      <td>
                        <span className={`badge ${getRoleBadgeClass(usr.role)}`}>
                          {usr.role}
                        </span>
                      </td>
                      <td>{usr.phone || '-'}</td>
                      <td>{formatDate(usr.createdAt)}</td>
                      <td>
                        <button
                          className="btn-icon btn-danger"
                          onClick={() => handleDeleteUser(usr._id)}
                          disabled={usr._id === user._id || loading}
                          title="Delete user"
                        >
                          🗑️
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* Activity Logs Tab */}
      {activeTab === 'logs' && (
        <div className="content-section">
          <div className="section-header">
            <h2>📝 Activity Logs ({logs.length})</h2>
          </div>

          {loading ? (
            <div className="loading-spinner">
              <div className="spinner"></div>
              <p>Loading logs...</p>
            </div>
          ) : logs.length === 0 ? (
            <div className="empty-state">
              <span className="empty-icon">📝</span>
              <h3>No Activity Logs</h3>
              <p>System activity will appear here</p>
            </div>
          ) : (
            <div className="logs-list">
              {logs.map((log, index) => (
                <div key={log._id || index} className="log-item">
                  <div className="log-time">{formatDate(log.timestamp)}</div>
                  <div className="log-content">
                    <strong>{log.user?.firstName} {log.user?.lastName}</strong>
                    <span className="log-action">{log.action}</span>
                    {log.details && <p className="log-details">{log.details}</p>}
                  </div>
                  <span className={`badge ${getRoleBadgeClass(log.user?.role)}`}>
                    {log.user?.role}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
      </main>
    </div>
  );
};

export default AdminDashboard;
