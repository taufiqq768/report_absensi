// API Configuration
const API_CONFIG = {
    tokenUrl: 'http://hcis.holding-perkebunan.com/api/generate_token_api'
};

// DOM Elements
const elements = {
    loginForm: document.getElementById('loginForm'),
    loginBtn: document.getElementById('loginBtn'),
    username: document.getElementById('username'),
    password: document.getElementById('password'),
    loading: document.getElementById('loading'),
    alertError: document.getElementById('alertError'),
    alertSuccess: document.getElementById('alertSuccess'),
    errorText: document.getElementById('errorText')
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Check if already logged in
    checkExistingSession();

    // Setup event listeners
    setupEventListeners();
});

// Check if user already logged in
function checkExistingSession() {
    const credentials = getStoredCredentials();
    if (credentials) {
        // User already logged in, redirect to main page
        window.location.href = 'index.html';
    }
}

// Setup Event Listeners
function setupEventListeners() {
    elements.loginForm.addEventListener('submit', handleLogin);

    // Allow Enter key to submit
    elements.password.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleLogin(e);
        }
    });
}

// Handle Login
async function handleLogin(e) {
    e.preventDefault();

    try {
        // Clear any previous session data first
        localStorage.removeItem('hcis_credentials');

        // Show loading
        showLoading();

        // Get form values
        const username = elements.username.value.trim();
        const password = elements.password.value.trim();

        // Validate
        if (!username || !password) {
            throw new Error('Username dan password harus diisi');
        }

        // Attempt to generate token (this validates credentials)
        const credentials = { username, password };
        const token = await generateToken(credentials);

        // If successful, store NEW credentials (overwriting old ones)
        storeCredentials(credentials);

        // Show success message
        showSuccess();

        // Redirect to main page after short delay
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1500);

    } catch (error) {
        console.error('Login error:', error);
        showError(error.message);
    }
}

// Generate Token (validate credentials)
async function generateToken(credentials) {
    try {
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
                throw new Error('Username atau password salah');
            }
            throw new Error(`Login gagal: ${response.status} - ${response.statusText}`);
        }

        const data = await response.json();

        // Check if token exists in response
        if (!data.token && !data.access_token) {
            throw new Error('Token tidak ditemukan dalam response');
        }

        return data.token || data.access_token;

    } catch (error) {
        if (error.message.includes('Failed to fetch')) {
            throw new Error('Tidak dapat terhubung ke server. Pastikan Anda mengakses via localhost atau gunakan CORS extension.');
        }
        throw error;
    }
}

// Store Credentials in localStorage
function storeCredentials(credentials) {
    localStorage.setItem('hcis_credentials', JSON.stringify(credentials));
}

// Get Stored Credentials
function getStoredCredentials() {
    const stored = localStorage.getItem('hcis_credentials');
    return stored ? JSON.parse(stored) : null;
}

// UI State Management
function showLoading() {
    hideAllAlerts();
    elements.loading.classList.add('active');
    elements.loginForm.style.display = 'none';
    elements.loginBtn.disabled = true;
}

function showError(message) {
    hideAllAlerts();
    elements.loading.classList.remove('active');
    elements.loginForm.style.display = 'flex';
    elements.alertError.classList.add('active');
    elements.errorText.textContent = message;
    elements.loginBtn.disabled = false;
}

function showSuccess() {
    hideAllAlerts();
    elements.loading.classList.remove('active');
    elements.loginForm.style.display = 'none';
    elements.alertSuccess.classList.add('active');
}

function hideAllAlerts() {
    elements.alertError.classList.remove('active');
    elements.alertSuccess.classList.remove('active');
}
