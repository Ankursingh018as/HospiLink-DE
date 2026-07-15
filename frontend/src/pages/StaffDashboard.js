import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { bedAPI, patientAPI } from '../services/api';
import Sidebar from '../components/Sidebar';
import '../styles/original-dashboard.css';

const StaffDashboard = () => {
  const { user } = useAuth();
  const [beds, setBeds] = useState([]);
  const [patients, setPatients] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showAdmitModal, setShowAdmitModal] = useState(false);
  const [showBedModal, setShowBedModal] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [bedFilter, setBedFilter] = useState('all'); // all, available, occupied

  const [admitFormData, setAdmitFormData] = useState({
    patientId: '',
    bedId: '',
    admissionReason: '',
    symptoms: '',
    notes: ''
  });

  const [bedFormData, setBedFormData] = useState({
    bedNumber: '',
    ward: '',
    floor: ''
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [bedsResponse, patientsResponse] = await Promise.all([
        bedAPI.getAll(),
        patientAPI.getAll()
      ]);
      
      setBeds(bedsResponse.data.beds || []);
      setPatients(patientsResponse.data.patients || []);
    } catch (err) {
      console.error('Error fetching data:', err);
      setError('Failed to load data');
    } finally {
      setLoading(false);
    }
  };

  const handleAdmitPatient = async (e) => {
    e.preventDefault();
    try {
      setLoading(true);
      await patientAPI.admit(admitFormData);
      setSuccess('Patient admitted successfully!');
      setShowAdmitModal(false);
      setAdmitFormData({
        patientId: '',
        bedId: '',
        admissionReason: '',
        symptoms: '',
        notes: ''
      });
      fetchData();
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to admit patient');
    } finally {
      setLoading(false);
    }
  };

  const handleAddBed = async (e) => {
    e.preventDefault();
    try {
      setLoading(true);
      await bedAPI.create(bedFormData);
      setSuccess('Bed added successfully!');
      setShowBedModal(false);
      setBedFormData({ bedNumber: '', ward: '', floor: '' });
      fetchData();
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to add bed');
    } finally {
      setLoading(false);
    }
  };

  const handleReleaseBed = async (bedId) => {
    if (!window.confirm('Are you sure you want to release this bed?')) return;
    
    try {
      setLoading(true);
      await bedAPI.release(bedId);
      setSuccess('Bed released successfully!');
      fetchData();
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to release bed');
    } finally {
      setLoading(false);
    }
  };

  const filteredBeds = beds.filter(bed => {
    if (bedFilter === 'available') return bed.status === 'available';
    if (bedFilter === 'occupied') return bed.status === 'occupied';
    return true;
  });

  const availableBeds = beds.filter(b => b.status === 'available');
  const occupiedBeds = beds.filter(b => b.status === 'occupied');
  const maintenanceBeds = beds.filter(b => b.status === 'maintenance');

  return (
    <div className="dashboard-container">
      <Sidebar />
      
      <main className="main-content">
        <header className="dashboard-header">
        <div className="header-content">
          <div>
            <h1>Staff Dashboard - {user?.firstName} <i className="ri-nurse-line"></i></h1>
            <p>Manage beds and patient admissions</p>
          </div>
          <div className="header-actions">
            <button 
              className="btn btn-primary"
              onClick={() => setShowAdmitModal(true)}
            >
              <span><i className="ri-hospital-line" style={{ marginRight: '6px' }}></i></span> Admit Patient
            </button>
            <button 
              className="btn btn-secondary"
              onClick={() => setShowBedModal(true)}
            >
              <span><i className="ri-hotel-bed-line" style={{ marginRight: '6px' }}></i></span> Add Bed
            </button>
          </div>
        </div>
      </header>

      {/* Alerts */}
      {success && (
        <div className="alert alert-success">
          <i className="ri-checkbox-circle-line" style={{ marginRight: '8px' }}></i> {success}
        </div>
      )}
      {error && (
        <div className="alert alert-danger">
          <i className="ri-alert-line" style={{ marginRight: '8px' }}></i> {error}
        </div>
      )}

      {/* Stats Cards */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' }}>
            <i className="ri-checkbox-circle-line"></i>
          </div>
          <div className="stat-content">
            <h3>{availableBeds.length}</h3>
            <p>Available Beds</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' }}>
            <i className="ri-group-line"></i>
          </div>
          <div className="stat-content">
            <h3>{occupiedBeds.length}</h3>
            <p>Occupied Beds</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }}>
            <i className="ri-hotel-bed-line"></i>
          </div>
          <div className="stat-content">
            <h3>{beds.length}</h3>
            <p>Total Beds</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon" style={{ background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' }}>
            <i className="ri-settings-4-line"></i>
          </div>
          <div className="stat-content">
            <h3>{maintenanceBeds.length}</h3>
            <p>Under Maintenance</p>
          </div>
        </div>
      </div>

      {/* Bed Management Section */}
      <div className="content-section">
        <div className="section-header">
          <h2><i className="ri-hotel-bed-line" style={{ marginRight: '8px' }}></i> Bed Management</h2>
          <div className="filter-buttons">
            <button 
              className={`filter-btn ${bedFilter === 'all' ? 'active' : ''}`}
              onClick={() => setBedFilter('all')}
            >
              All ({beds.length})
            </button>
            <button 
              className={`filter-btn ${bedFilter === 'available' ? 'active' : ''}`}
              onClick={() => setBedFilter('available')}
            >
              Available ({availableBeds.length})
            </button>
            <button 
              className={`filter-btn ${bedFilter === 'occupied' ? 'active' : ''}`}
              onClick={() => setBedFilter('occupied')}
            >
              Occupied ({occupiedBeds.length})
            </button>
          </div>
        </div>

        {loading ? (
          <div className="loading-spinner">
            <div className="spinner"></div>
            <p>Loading beds...</p>
          </div>
        ) : filteredBeds.length === 0 ? (
          <div className="empty-state">
            <span className="empty-icon"><i className="ri-hotel-bed-line"></i></span>
            <h3>No Beds Found</h3>
            <p>Add beds to start managing them</p>
          </div>
        ) : (
          <div className="beds-grid">
            {filteredBeds.map((bed) => (
              <div key={bed._id} className={`bed-card ${bed.status}`}>
                <div className="bed-header">
                  <div className="bed-number">
                    <span className="bed-icon"><i className="ri-hotel-bed-line"></i></span>
                    <div>
                      <h3>Bed {bed.bedNumber}</h3>
                      <p className="text-muted">{bed.ward} • Floor {bed.floor}</p>
                    </div>
                  </div>
                  <span className={`badge badge-${bed.status === 'available' ? 'success' : bed.status === 'occupied' ? 'danger' : 'warning'}`}>
                    {bed.status}
                  </span>
                </div>

                {bed.currentPatient && (
                  <div className="bed-patient">
                    <div className="patient-details">
                      <span className="patient-icon"><i className="ri-user-line"></i></span>
                      <div>
                        <strong>{bed.currentPatient.firstName} {bed.currentPatient.lastName}</strong>
                        <p className="text-muted">{bed.currentPatient.email}</p>
                      </div>
                    </div>
                    <button 
                      className="btn btn-sm btn-danger"
                      onClick={() => handleReleaseBed(bed._id)}
                      disabled={loading}
                    >
                      Release Bed
                    </button>
                  </div>
                )}

                {bed.lastOccupiedDate && (
                  <div className="bed-info">
                    <span className="info-label">Last Occupied:</span>
                    <span>{new Date(bed.lastOccupiedDate).toLocaleDateString()}</span>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Admit Patient Modal */}
      {showAdmitModal && (
        <div className="modal-overlay" onClick={() => setShowAdmitModal(false)}>
          <div className="modal-content large-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2><i className="ri-hospital-line" style={{ marginRight: '8px' }}></i> Admit Patient</h2>
              <button className="modal-close" onClick={() => setShowAdmitModal(false)}>×</button>
            </div>

            <form onSubmit={handleAdmitPatient} className="modal-form">
              <div className="form-group">
                <label htmlFor="patientId">
                  <span className="icon"><i className="ri-user-line"></i></span>
                  Select Patient
                </label>
                <select
                  id="patientId"
                  value={admitFormData.patientId}
                  onChange={(e) => setAdmitFormData({...admitFormData, patientId: e.target.value})}
                  required
                >
                  <option value="">-- Select Patient --</option>
                  {patients.map(patient => (
                    <option key={patient._id} value={patient._id}>
                      {patient.firstName} {patient.lastName} - {patient.email}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="bedId">
                  <span className="icon"><i className="ri-hotel-bed-line"></i></span>
                  Assign Bed
                </label>
                <select
                  id="bedId"
                  value={admitFormData.bedId}
                  onChange={(e) => setAdmitFormData({...admitFormData, bedId: e.target.value})}
                  required
                >
                  <option value="">-- Select Bed --</option>
                  {availableBeds.map(bed => (
                    <option key={bed._id} value={bed._id}>
                      Bed {bed.bedNumber} - {bed.ward} (Floor {bed.floor})
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="admissionReason">
                  <span className="icon"><i className="ri-file-list-3-line"></i></span>
                  Admission Reason
                </label>
                <textarea
                  id="admissionReason"
                  value={admitFormData.admissionReason}
                  onChange={(e) => setAdmitFormData({...admitFormData, admissionReason: e.target.value})}
                  placeholder="Reason for admission..."
                  rows="3"
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="symptoms">
                  <span className="icon"><i className="ri-capsule-line"></i></span>
                  Symptoms
                </label>
                <textarea
                  id="symptoms"
                  value={admitFormData.symptoms}
                  onChange={(e) => setAdmitFormData({...admitFormData, symptoms: e.target.value})}
                  placeholder="Patient symptoms..."
                  rows="3"
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="notes">
                  <span className="icon"><i className="ri-file-list-3-line"></i></span>
                  Additional Notes
                </label>
                <textarea
                  id="notes"
                  value={admitFormData.notes}
                  onChange={(e) => setAdmitFormData({...admitFormData, notes: e.target.value})}
                  placeholder="Any additional information..."
                  rows="2"
                />
              </div>

              <div className="modal-footer">
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => setShowAdmitModal(false)}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="btn btn-primary"
                  disabled={loading}
                >
                  {loading ? 'Admitting...' : 'Admit Patient'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Add Bed Modal */}
      {showBedModal && (
        <div className="modal-overlay" onClick={() => setShowBedModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2><i className="ri-hotel-bed-line" style={{ marginRight: '8px' }}></i> Add New Bed</h2>
              <button className="modal-close" onClick={() => setShowBedModal(false)}>×</button>
            </div>

            <form onSubmit={handleAddBed} className="modal-form">
              <div className="form-group">
                <label htmlFor="bedNumber">
                  <span className="icon"><i className="ri-hashtag"></i></span>
                  Bed Number
                </label>
                <input
                  type="text"
                  id="bedNumber"
                  value={bedFormData.bedNumber}
                  onChange={(e) => setBedFormData({...bedFormData, bedNumber: e.target.value})}
                  placeholder="e.g., 101, A-201"
                  required
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label htmlFor="ward">
                    <span className="icon"><i className="ri-hospital-line"></i></span>
                    Ward
                  </label>
                  <input
                    type="text"
                    id="ward"
                    value={bedFormData.ward}
                    onChange={(e) => setBedFormData({...bedFormData, ward: e.target.value})}
                    placeholder="e.g., ICU, General"
                    required
                  />
                </div>

                <div className="form-group">
                  <label htmlFor="floor">
                    <span className="icon"><i className="ri-building-4-line"></i></span>
                    Floor
                  </label>
                  <input
                    type="number"
                    id="floor"
                    value={bedFormData.floor}
                    onChange={(e) => setBedFormData({...bedFormData, floor: e.target.value})}
                    placeholder="Floor number"
                    min="1"
                    required
                  />
                </div>
              </div>

              <div className="modal-footer">
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => setShowBedModal(false)}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="btn btn-primary"
                  disabled={loading}
                >
                  {loading ? 'Adding...' : 'Add Bed'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
      </main>
    </div>
  );
};

export default StaffDashboard;
