import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import '../styles/sidebar.css';

const Sidebar = () => {
  const { user, logout } = useAuth();
  const location = useLocation();

  const getNavItems = () => {
    if (user?.role === 'patient') {
      return [
        { path: '/patient/dashboard', icon: 'fas fa-home', label: 'Dashboard' },
        { path: '/patient/profile', icon: 'fas fa-user-edit', label: 'Edit Profile' },
        { path: '/appointment', icon: 'fas fa-plus-circle', label: 'Book Appointment' },
        { path: '/beds', icon: 'fas fa-bed', label: 'Bed Availability' },
      ];
    } else if (user?.role === 'doctor') {
      return [
        { path: '/doctor/dashboard', icon: 'fas fa-home', label: 'Dashboard' },
        { path: '/doctor/profile', icon: 'fas fa-user-edit', label: 'Edit Profile' },
        { path: '/doctor/appointments', icon: 'fas fa-calendar-check', label: 'My Appointments' },
        { path: '/doctor/patients', icon: 'fas fa-users', label: 'My Patients' },
      ];
    } else if (user?.role === 'staff' || user?.role === 'nurse') {
      return [
        { path: '/staff/dashboard', icon: 'fas fa-home', label: 'Dashboard' },
        { path: '/staff/profile', icon: 'fas fa-user-edit', label: 'Edit Profile' },
        { path: '/staff/beds', icon: 'fas fa-bed', label: 'Bed Management' },
        { path: '/staff/patients', icon: 'fas fa-users', label: 'Patients' },
        { path: '/admit', icon: 'fas fa-user-plus', label: 'Admit Patient' },
      ];
    } else if (user?.role === 'admin') {
      return [
        { path: '/admin/dashboard', icon: 'fas fa-home', label: 'Dashboard' },
        { path: '/admin/users', icon: 'fas fa-users', label: 'User Management' },
        { path: '/admin/appointments', icon: 'fas fa-calendar', label: 'All Appointments' },
        { path: '/admin/beds', icon: 'fas fa-bed', label: 'Bed Management' },
        { path: '/admin/logs', icon: 'fas fa-clipboard-list', label: 'Activity Logs' },
      ];
    }
    return [];
  };

  const navItems = getNavItems();

  return (
    <aside className="sidebar">
      <div className="logo">
        <img src="/images/logo.png" alt="HospiLink" />
      </div>
      <nav className="sidebar-nav">
        {navItems.map((item) => (
          <Link
            key={item.path}
            to={item.path}
            className={`nav-item ${location.pathname === item.path ? 'active' : ''}`}
          >
            <i className={item.icon}></i>
            <span>{item.label}</span>
          </Link>
        ))}
        <button onClick={logout} className="nav-item logout">
          <i className="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </button>
      </nav>
    </aside>
  );
};

export default Sidebar;
