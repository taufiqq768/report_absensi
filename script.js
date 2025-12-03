// API Configuration
const API_CONFIG = {
    tokenUrl: 'http://hcis.holding-perkebunan.com/api/generate_token_api',
    dataUrl: 'http://hcis.holding-perkebunan.com/api/absensi/get-n1'
};

// DOM Elements
const elements = {
    fetchBtn: document.getElementById('fetchBtn'),
    resetBtn: document.getElementById('resetBtn'),
    logoutBtn: document.getElementById('logoutBtn'),
    filterForm: document.getElementById('filterForm'),
    loading: document.getElementById('loading'),
    errorMessage: document.getElementById('errorMessage'),
    errorText: document.getElementById('errorText'),
    emptyState: document.getElementById('emptyState'),
    tableContainer: document.getElementById('tableContainer'),
    tableHeader: document.getElementById('tableHeader'),
    tableBody: document.getElementById('tableBody'),
    dataInfo: document.getElementById('dataInfo'),
    totalRecords: document.getElementById('totalRecords'),
    userInfo: document.getElementById('userInfo'),
    loggedInUser: document.getElementById('loggedInUser'),
    // Form inputs
    ptpn: document.getElementById('ptpn'),
    psa: document.getElementById('psa'),
    regional: document.getElementById('regional'),
    dariTanggal: document.getElementById('dariTanggal'),
    sampaiTanggal: document.getElementById('sampaiTanggal'),
    user: document.getElementById('user')
};

// State Management
let currentData = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication first
    checkAuthentication();
    setupEventListeners();
    displayUserInfo();
});

// Check Authentication
function checkAuthentication() {
    const credentials = getStoredCredentials();
    if (!credentials) {
        // Not logged in, redirect to login page
        window.location.href = 'login.html';
    }
}

// Get Stored Credentials
function getStoredCredentials() {
    const stored = localStorage.getItem('hcis_credentials');
    return stored ? JSON.parse(stored) : null;
}

// Display User Info
function displayUserInfo() {
    const credentials = getStoredCredentials();
    if (credentials && credentials.username) {
        elements.loggedInUser.textContent = credentials.username;
        elements.userInfo.style.display = 'block';
    }
}

// Logout Function
function logout() {
    // Clear credentials from localStorage
    localStorage.removeItem('hcis_credentials');
    // Redirect to login page
    window.location.href = 'login.html';
}

// Event Listeners
function setupEventListeners() {
    elements.fetchBtn.addEventListener('click', fetchData);
    elements.resetBtn.addEventListener('click', resetForm);
    elements.logoutBtn.addEventListener('click', logout);

    // Allow form submission with Enter key
    elements.filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchData();
    });
}

// Reset Form
function resetForm() {
    elements.ptpn.value = 'AMCO';
    elements.psa.value = 'HA00';
    elements.regional.value = 'HEAD_OFFICE';
    elements.dariTanggal.value = '2025-11-05';
    elements.sampaiTanggal.value = '2025-11-05';
    elements.user.value = '12345678';

    // Clear table
    hideAllStates();
}

// Generate Token from API
async function generateToken() {
    try {
        // Get credentials from localStorage
        const credentials = getStoredCredentials();
        if (!credentials) {
            throw new Error('Session expired. Please login again.');
        }

        const response = await fetch(API_CONFIG.tokenUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(credentials)
        });

        if (!response.ok) {
            if (response.status === 401) {
                // Invalid credentials, logout
                logout();
                throw new Error('Session expired. Please login again.');
            }
            throw new Error(`Token generation failed: ${response.status} - ${response.statusText}`);
        }

        const data = await response.json();

        // Check if token exists in response
        if (!data.token && !data.access_token) {
            throw new Error('Token tidak ditemukan dalam response API');
        }

        // Return token (handle both common response formats)
        return data.token || data.access_token;

    } catch (error) {
        console.error('Error generating token:', error);
        if (error.message.includes('Failed to fetch')) {
            throw new Error('Tidak dapat terhubung ke server. Pastikan Anda mengakses via localhost atau gunakan CORS extension.');
        }
        throw error;
    }
}

// Fetch Data from API
async function fetchData() {
    try {
        // Show loading state
        showLoading();

        // Get form values
        const filterData = {
            filter: {
                ptpn: elements.ptpn.value.trim(),
                psa: elements.psa.value.trim(),
                regional: elements.regional.value.trim(),
                dari_tanggal: elements.dariTanggal.value,
                sampai_tanggal: elements.sampaiTanggal.value,
                user: elements.user.value.trim()
            }
        };

        // Validate form
        if (!validateForm(filterData.filter)) {
            throw new Error('Mohon lengkapi semua field filter');
        }

        // Generate fresh token
        const token = await generateToken();

        // Make API request with fresh token
        const response = await fetch(API_CONFIG.dataUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify(filterData)
        });

        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status} - ${response.statusText}`);
        }

        // Parse response
        const data = await response.json();

        // Store current data
        currentData = data;

        // Process and display data
        displayData(data);

    } catch (error) {
        console.error('Error fetching data:', error);
        showError(error.message);
    }
}

// Validate Form
function validateForm(filter) {
    return filter.ptpn && filter.psa && filter.regional &&
        filter.dari_tanggal && filter.sampai_tanggal && filter.user;
}

// Display Data
function displayData(data) {
    hideAllStates();

    // Check if data exists
    if (!data || !data.data || data.data.length === 0) {
        showEmptyState();
        return;
    }

    const records = data.data;

    // Generate table headers from first record
    generateTableHeaders(records[0]);

    // Generate table rows
    generateTableRows(records);

    // Show table and info
    elements.tableContainer.style.display = 'block';
    elements.dataInfo.style.display = 'block';
    elements.totalRecords.textContent = records.length;
}

// Generate Table Headers
function generateTableHeaders(sampleRecord) {
    elements.tableHeader.innerHTML = '';

    const headers = Object.keys(sampleRecord);

    headers.forEach(header => {
        const th = document.createElement('th');
        th.textContent = formatHeaderName(header);
        elements.tableHeader.appendChild(th);
    });
}

// Generate Table Rows
function generateTableRows(records) {
    elements.tableBody.innerHTML = '';

    records.forEach((record, index) => {
        const tr = document.createElement('tr');

        Object.values(record).forEach(value => {
            const td = document.createElement('td');
            td.textContent = formatCellValue(value);
            tr.appendChild(td);
        });

        elements.tableBody.appendChild(tr);
    });
}

// Format Header Name
function formatHeaderName(header) {
    // Convert snake_case or camelCase to Title Case
    return header
        .replace(/_/g, ' ')
        .replace(/([A-Z])/g, ' $1')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ')
        .trim();
}

// Format Cell Value
function formatCellValue(value) {
    if (value === null || value === undefined) {
        return '-';
    }

    // Format dates if applicable
    if (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}/)) {
        return formatDate(value);
    }

    return value;
}

// Format Date
function formatDate(dateString) {
    try {
        const date = new Date(dateString);
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('id-ID', options);
    } catch (error) {
        return dateString;
    }
}

// UI State Management
function hideAllStates() {
    elements.loading.classList.remove('active');
    elements.errorMessage.classList.remove('active');
    elements.emptyState.classList.remove('active');
    elements.tableContainer.style.display = 'none';
    elements.dataInfo.style.display = 'none';
}

function showLoading() {
    hideAllStates();
    elements.loading.classList.add('active');
    elements.fetchBtn.disabled = true;
}

function showError(message) {
    hideAllStates();
    elements.errorMessage.classList.add('active');
    elements.errorText.textContent = message;
    elements.fetchBtn.disabled = false;
}

function showEmptyState() {
    hideAllStates();
    elements.emptyState.classList.add('active');
    elements.fetchBtn.disabled = false;
}

// Export functionality (optional enhancement)
function exportToCSV() {
    if (!currentData || !currentData.data || currentData.data.length === 0) {
        alert('Tidak ada data untuk di-export');
        return;
    }

    const records = currentData.data;
    const headers = Object.keys(records[0]);

    // Create CSV content
    let csv = headers.join(',') + '\n';

    records.forEach(record => {
        const row = headers.map(header => {
            const value = record[header];
            // Escape commas and quotes
            return `"${String(value).replace(/"/g, '""')}"`;
        });
        csv += row.join(',') + '\n';
    });

    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `laporan_absensi_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}
