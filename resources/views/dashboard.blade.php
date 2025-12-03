@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container">
        <header class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; ">
                <div style="flex: 1; text-align: center;">
                    <h1>Laporan Absensi Harian AGHRIS</h1>
                </div>
            </div>
        </header>

        <!-- Filter Card -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">Cari Data</h2>
            <form id="filterForm" class="filter-form">
                @csrf
                <div class="form-group" style="display: none;">
                    <label for="ptpn">PTPN</label>
                    <input type="text" id="ptpn" name="ptpn" value="AMCO" placeholder="Masukkan PTPN">
                </div>

                <div class="form-group">
                    <label for="psa">PSA</label>
                    <select id="psa" name="psa"></select>
                </div>

                <div class="form-group">
                    <label for="regional">Regional</label>
                    <select id="regional" name="regional"></select>
                </div>

                <div class="form-group">
                    <label for="dari_tanggal">Dari Tanggal</label>
                    <input type="date" id="dari_tanggal" name="dari_tanggal">
                </div>

                <div class="form-group">
                    <label for="sampai_tanggal">Sampai Tanggal</label>
                    <input type="date" id="sampai_tanggal" name="sampai_tanggal">
                </div>

                <div class="form-group" style="display: none;">
                    <label for="user">User ID</label>
                    <input type="text" id="user" name="user" value="12345678" placeholder="Masukkan User ID">
                </div>
            </form>

            <div class="btn-container">
                <button type="button" id="fetchBtn" class="btn">🔍 Tampilkan Data</button>
                <button type="button" id="resetBtn" class="btn btn--secondary">🔄 Reset Filter</button>
            </div>

            <!-- Export Buttons -->
            <div class="export-container" id="exportContainer" style="display: none;">
                <button type="button" id="exportExcelBtn" class="btn btn--export">
                    📥 Export Excel
                </button>
                <button type="button" id="exportPdfBtn" class="btn btn--export">
                    📄 Export PDF
                </button>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">Hasil Data Absensi</h2>

            <!-- Loading State -->
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>Memuat data...</p>
            </div>

            <!-- Error State -->
            <div id="errorMessage" class="error-message">
                <h3>❌ Terjadi Kesalahan</h3>
                <p id="errorText"></p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state">
                <h3>📭 Tidak Ada Data</h3>
                <p>Tidak ada data absensi yang ditemukan untuk filter yang dipilih.</p>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div id="messageBox"></div>
                </div>
            </div>

            <!-- Search Field -->
            <div id="searchContainer" class="search-container" style="display: none;">
                <div class="search-box">
                    <input type="text" id="searchInput"
                        placeholder="🔍 Cari data... (ketik nama, tanggal, atau informasi lainnya)" class="search-input">
                    <span id="searchResultCount" class="search-result-count"></span>
                </div>
            </div>

            <!-- Data Table -->
            <div id="tableContainer" class="table-container" style="display: none;">
                <table id="dataTable">
                    <thead>
                        <tr id="tableHeader"></tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div id="paginationContainer" style="display: none; margin-top: 1.5rem;">
                <div class="pagination-controls">
                    <button id="prevPage" class="pagination-btn">
                        <span>←</span> Previous
                    </button>
                    <div class="pagination-info">
                        <span>Page <strong id="currentPage">1</strong> of <strong id="totalPages">1</strong></span>
                        <span class="page-separator">|</span>
                        <span>Showing <strong id="showingStart">0</strong>-<strong id="showingEnd">0</strong> of <strong
                                id="totalRecordsInPagination">0</strong></span>
                    </div>
                    <button id="nextPage" class="pagination-btn">
                        Next <span>→</span>
                    </button>
                </div>
            </div>

            <!-- Data Info -->
            <div id="dataInfo" style="margin-top: 1rem; color: var(--text-muted); display: none;">
                <p>Total data: <strong id="totalRecords">0</strong> record(s)</p>
            </div>
        </div>

        <footer class="footer">
            <p>&copy; 2025 PTPN I. All rights reserved.</p>
        </footer>
    </div>
@endsection

@push('scripts')
    <script>
        // Inisialisasi opsi dropdown sederhana (bisa diganti fetch API nanti)
        const PSA_OPTIONS = ['HA00', 'HB00', 'HC00'];
        const REGIONAL_OPTIONS = ['HEAD_OFFICE', 'REGIONAL_1', 'REGIONAL_2'];

        function populateSelect(id, options, selected) {
            const el = document.getElementById(id);
            el.innerHTML = options
                .map(opt => `<option value="${opt}" ${opt === selected ? 'selected' : ''}>${opt}</option>`)
                .join('');
        }

        // Set default pilihan
        populateSelect('psa', PSA_OPTIONS, 'HA00');
        populateSelect('regional', REGIONAL_OPTIONS, 'HEAD_OFFICE');

        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Pagination State
        let allData = []; // Store all fetched data
        let filteredData = []; // Store filtered data from search
        let currentPageNum = 1;
        const recordsPerPage = 10; // Number of records per page

        // DOM Elements
        const elements = {
            fetchBtn: document.getElementById('fetchBtn'),
            resetBtn: document.getElementById('resetBtn'),
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
            messageBox: document.getElementById('messageBox'),
            // Pagination elements
            paginationContainer: document.getElementById('paginationContainer'),
            prevPage: document.getElementById('prevPage'),
            nextPage: document.getElementById('nextPage'),
            currentPage: document.getElementById('currentPage'),
            totalPages: document.getElementById('totalPages'),
            showingStart: document.getElementById('showingStart'),
            showingEnd: document.getElementById('showingEnd'),
            totalRecordsInPagination: document.getElementById('totalRecordsInPagination'),
            // Export elements
            exportContainer: document.getElementById('exportContainer'),
            exportExcelBtn: document.getElementById('exportExcelBtn'),
            exportPdfBtn: document.getElementById('exportPdfBtn'),
            // Search elements
            searchContainer: document.getElementById('searchContainer'),
            searchInput: document.getElementById('searchInput'),
            searchResultCount: document.getElementById('searchResultCount')
        };

        // FETCH BUTTON ACTION
        elements.fetchBtn.addEventListener('click', () => {
            showLoading();

            const formData = new FormData(elements.filterForm);
            const dataObject = Object.fromEntries(formData.entries());

            console.log("Sending Filters:", dataObject);

            fetch("{{ route('absensi.data') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify(dataObject)
            })
                .then(response => response.json())
                .then(response => {
                    console.log("API response:", response);

                    elements.fetchBtn.disabled = false;

                    if (response.status === "success") {
                        if (response.data.total_records > 0) {
                            elements.messageBox.innerHTML =
                                `<div class="alert alert-success">Data berhasil diambil</div>`;
                            generateTable(response.data.records);
                        } else {
                            elements.messageBox.innerHTML =
                                `<div class="alert alert-warning">Tidak ada data ditemukan</div>`;
                            showEmptyState();
                        }
                    } else {
                        elements.messageBox.innerHTML =
                            `<div class="alert alert-danger">❌ ${response.message}</div>`;
                        showError(response.message);
                    }
                })
                .catch(error => {
                    console.error("Fetch error:", error);
                    elements.messageBox.innerHTML =
                        `<div class="alert alert-danger">❌ Terjadi kesalahan saat memproses data</div>`;
                    showError(error.message);
                });
        });

        // GENERATE TABLE
        function generateTable(data) {
            hideAllStates();

            // Store all data for pagination
            allData = data;
            filteredData = data; // Initially, filtered data is same as all data
            currentPageNum = 1;

            const headers = Object.keys(data[0]);
            elements.tableHeader.innerHTML = headers
                .map(h => `<th>${formatHeaderName(h)}</th>`)
                .join('');

            // Clear search input
            elements.searchInput.value = '';

            // Render first page
            renderPage();

            elements.tableContainer.style.display = 'block';
            elements.dataInfo.style.display = 'block';
            elements.totalRecords.textContent = data.length;

            // Show pagination if there are more records than recordsPerPage
            if (data.length > recordsPerPage) {
                elements.paginationContainer.style.display = 'block';
            } else {
                elements.paginationContainer.style.display = 'none';
            }

            // Show export buttons when data is available
            elements.exportContainer.style.display = 'flex';

            // Show search container when data is available
            elements.searchContainer.style.display = 'block';
            updateSearchResultCount();
        }

        // FORMAT HELPERS
        function formatHeaderName(header) {
            return header.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        }

        function formatCellValue(value) {
            if (value === null || value === undefined || value === "") return "-";
            if (value.toString().match(/^\d{4}-\d{2}-\d{2}/)) {
                return new Date(value).toLocaleDateString("id-ID");
            }
            return value;
        }

        // RENDER PAGE - Display current page of data
        function renderPage() {
            if (filteredData.length === 0) {
                elements.tableBody.innerHTML = '<tr><td colspan="100" style="text-align: center; padding: 2rem; color: var(--text-muted);">Tidak ada data yang sesuai dengan pencarian</td></tr>';
                elements.paginationContainer.style.display = 'none';
                return;
            }

            const headers = Object.keys(filteredData[0]);
            const totalRecords = filteredData.length;
            const totalPagesCount = Math.ceil(totalRecords / recordsPerPage);

            // Calculate start and end indices
            const startIndex = (currentPageNum - 1) * recordsPerPage;
            const endIndex = Math.min(startIndex + recordsPerPage, totalRecords);

            // Get current page data
            const pageData = filteredData.slice(startIndex, endIndex);

            // Render table rows
            elements.tableBody.innerHTML = pageData
                .map(row => `<tr>${headers.map(h => `<td>${formatCellValue(row[h])}</td>`).join('')}</tr>`)
                .join('');

            // Update pagination info
            elements.currentPage.textContent = currentPageNum;
            elements.totalPages.textContent = totalPagesCount;
            elements.showingStart.textContent = startIndex + 1;
            elements.showingEnd.textContent = endIndex;
            elements.totalRecordsInPagination.textContent = totalRecords;

            // Update button states
            elements.prevPage.disabled = currentPageNum === 1;
            elements.nextPage.disabled = currentPageNum === totalPagesCount;

            // Show/hide pagination based on filtered data
            if (totalRecords > recordsPerPage) {
                elements.paginationContainer.style.display = 'block';
            } else {
                elements.paginationContainer.style.display = 'none';
            }
        }

        // PAGINATION EVENT HANDLERS
        elements.prevPage.addEventListener('click', () => {
            if (currentPageNum > 1) {
                currentPageNum--;
                renderPage();
            }
        });

        elements.nextPage.addEventListener('click', () => {
            const totalPagesCount = Math.ceil(filteredData.length / recordsPerPage);
            if (currentPageNum < totalPagesCount) {
                currentPageNum++;
                renderPage();
            }
        });

        // RESET BUTTON
        elements.resetBtn.addEventListener('click', () => {
            elements.filterForm.reset();
            hideAllStates();
            elements.messageBox.innerHTML = "";
            // Reset pagination
            allData = [];
            filteredData = [];
            currentPageNum = 1;
            elements.paginationContainer.style.display = 'none';
            // Hide export buttons
            elements.exportContainer.style.display = 'none';
            // Hide search container
            elements.searchContainer.style.display = 'none';
            elements.searchInput.value = '';
        });

        // EXPORT EXCEL BUTTON
        elements.exportExcelBtn.addEventListener('click', () => {
            const formData = new FormData(elements.filterForm);
            const dataObject = Object.fromEntries(formData.entries());

            // Create a form and submit it to trigger download
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('absensi.export.excel') }}";

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            // Add filter data
            Object.keys(dataObject).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = dataObject[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });

        // EXPORT PDF BUTTON
        elements.exportPdfBtn.addEventListener('click', () => {
            const formData = new FormData(elements.filterForm);
            const dataObject = Object.fromEntries(formData.entries());

            // Create a form and submit it to trigger download
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('absensi.export.pdf') }}";

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            // Add filter data
            Object.keys(dataObject).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = dataObject[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });

        // SEARCH FUNCTIONALITY
        elements.searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();

            if (searchTerm === '') {
                // If search is empty, show all data
                filteredData = allData;
            } else {
                // Filter data based on search term
                filteredData = allData.filter(row => {
                    // Search across all columns
                    return Object.values(row).some(value => {
                        if (value === null || value === undefined) return false;
                        return value.toString().toLowerCase().includes(searchTerm);
                    });
                });
            }

            // Reset to first page when searching
            currentPageNum = 1;

            // Update display
            renderPage();
            updateSearchResultCount();
        });

        // Update search result count
        function updateSearchResultCount() {
            const searchTerm = elements.searchInput.value.trim();
            if (searchTerm === '') {
                elements.searchResultCount.textContent = '';
            } else {
                const count = filteredData.length;
                const total = allData.length;
                elements.searchResultCount.textContent = `Ditemukan ${count} dari ${total} data`;
            }
        }

        // UI STATE HANDLING
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
        }

        function showEmptyState() {
            hideAllStates();
            elements.emptyState.classList.add('active');
        }
    </script>
@endpush