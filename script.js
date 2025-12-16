// API Configuration
const API_CONFIG = {
    baseUrl: 'http://hcis.holding-perkebunan.com',
    tokenUrl: 'http://hcis.holding-perkebunan.com/api/generate_token_api',
    dataUrl: 'http://hcis.holding-perkebunan.com/api/absensi/get-n1',
    psaOptionsUrl: 'http://hcis.holding-perkebunan.com/api/psa-options',
    divisiOptionsUrl: 'http://hcis.holding-perkebunan.com/api/divisi-options'
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
let PSA_OPTIONS = [];
let DIVISI_OPTIONS = [];

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication first
    checkAuthentication();
    setupEventListeners();
    displayUserInfo();

    // Load dropdown options after a short delay to ensure DOM is ready
    setTimeout(() => {
        loadPsaOptions();
        loadDivisiOptions();
    }, 100);
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

        // Clear any stale data from previous session
        sessionStorage.clear();
    }
}

// Logout Function (kept for backward compatibility, but not used directly)
function logout() {
    // This function is now handled by form submission in setupEventListeners
    // Clear credentials from localStorage
    localStorage.removeItem('hcis_credentials');
    // Redirect to login page
    window.location.href = 'login.html';
}

// Setup Event Listeners
function setupEventListeners() {
    elements.fetchBtn.addEventListener('click', fetchData);
    elements.resetBtn.addEventListener('click', resetForm);

    // Handle logout form submission
    const logoutForm = document.getElementById('logoutForm');
    if (logoutForm) {
        logoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            try {
                // Send logout request to backend
                const response = await fetch(API_CONFIG.baseUrl + '/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.ok) {
                    // Clear localStorage
                    localStorage.removeItem('hcis_credentials');
                    // Redirect to login
                    window.location.href = 'login.html';
                } else {
                    throw new Error('Logout failed');
                }
            } catch (error) {
                console.error('Logout error:', error);
                // Force logout on client side if server logout fails
                localStorage.removeItem('hcis_credentials');
                window.location.href = 'login.html';
            }
        });
    }

    // Allow form submission with Enter key
    elements.filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchData();
    });
}

// Reset Form
function resetForm() {
    // Clear form values
    elements.ptpn.value = '';
    elements.psa.value = '';
    elements.regional.value = '';
    elements.dariTanggal.value = '';
    elements.sampaiTanggal.value = '';
    elements.user.value = '';

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

// Helper function to populate select dropdown
function populateSelect(id, options, selected) {
    const el = document.getElementById(id);
    if (!el) return;

    let html = '<option value="">-- Pilih --</option>';
    html += options
        .map(opt => `<option value="${opt}" ${opt === selected ? 'selected' : ''}>${opt}</option>`)
        .join('');
    el.innerHTML = html;
}

// Load PSA Options from Backend (with retry logic)
async function loadPsaOptions(retries = 3) {
    try {
        const response = await Promise.race([
            fetch(API_CONFIG.psaOptionsUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }),
            // 5 second timeout
            new Promise((_, reject) =>
                setTimeout(() => reject(new Error('PSA options request timeout')), 5000)
            )
        ]);

        if (!response.ok) {
            throw new Error(`Failed to load PSA options: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data && Array.isArray(result.data)) {
            PSA_OPTIONS = result.data;
            console.log('✓ PSA Options loaded successfully:', result.data.length, 'items');
            // Populate PSA dropdown with empty default
            populateSelect('psa', PSA_OPTIONS, '');
            // Cache in sessionStorage for faster future access
            sessionStorage.setItem('psa_options_cache', JSON.stringify(result.data));
        } else {
            console.warn('⚠ No PSA options returned from server');
            populateSelect('psa', [], '');
        }
    } catch (error) {
        console.error('✗ Error loading PSA options:', error.message);
        // Retry if we have retries left
        if (retries > 0) {
            console.log(`Retrying PSA options load (${retries} retries left)...`);
            setTimeout(() => loadPsaOptions(retries - 1), 1000);
        } else {
            // Show error in dropdown
            populateSelect('psa', [], '');
            console.warn('⚠ PSA options unavailable - please ensure VPN is connected to HRIS server');
        }
    }
}

// Load Divisi Options from Backend (with retry logic)
async function loadDivisiOptions(retries = 3) {
    try {
        const response = await Promise.race([
            fetch(API_CONFIG.divisiOptionsUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }),
            // 5 second timeout
            new Promise((_, reject) =>
                setTimeout(() => reject(new Error('Divisi options request timeout')), 5000)
            )
        ]);

        if (!response.ok) {
            throw new Error(`Failed to load Divisi options: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data && Array.isArray(result.data)) {
            DIVISI_OPTIONS = result.data;
            console.log('✓ Divisi Options loaded successfully:', result.data.length, 'items');
            // Populate Divisi dropdown with empty default
            populateSelect('regional', DIVISI_OPTIONS, '');
            // Cache in sessionStorage for faster future access
            sessionStorage.setItem('divisi_options_cache', JSON.stringify(result.data));
        } else {
            console.warn('⚠ No Divisi options returned from server');
            populateSelect('regional', [], '');
        }
    } catch (error) {
        console.error('✗ Error loading Divisi options:', error.message);
        // Retry if we have retries left
        if (retries > 0) {
            console.log(`Retrying Divisi options load (${retries} retries left)...`);
            setTimeout(() => loadDivisiOptions(retries - 1), 1000);
        } else {
            // Show error in dropdown
            populateSelect('regional', [], '');
            console.warn('⚠ Divisi options unavailable - please ensure VPN is connected to HRIS server');
        }
    }
}

// Helper function to populate PSA dropdown (if needed in future)
function populatePsaDropdown(psaList) {
    if (!elements.psa) return;

    // Keep current value if it exists
    const currentValue = elements.psa.value;

    // Clear and repopulate
    elements.psa.innerHTML = '<option value="">-- Pilih PSA --</option>';

    psaList.forEach(psa => {
        const option = document.createElement('option');
        option.value = psa;
        option.textContent = psa;
        elements.psa.appendChild(option);
    });

    // Restore previous value if still available
    if (psaList.includes(currentValue)) {
        elements.psa.value = currentValue;
    }
}

// Helper function to populate Divisi dropdown (if needed in future)
function populateDivisiDropdown(divisiList) {
    if (!elements.regional) return;

    // Keep current value if it exists
    const currentValue = elements.regional.value;

    // Clear and repopulate
    elements.regional.innerHTML = '<option value="">-- Pilih Divisi --</option>';

    divisiList.forEach(divisi => {
        const option = document.createElement('option');
        option.value = divisi;
        option.textContent = divisi;
        elements.regional.appendChild(option);
    });

    // Restore previous value if still available
    if (divisiList.includes(currentValue)) {
        elements.regional.value = currentValue;
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
