/**
 * Stock Car - Main Application
 * SPA-like vehicle inventory management
 */

// ===== STATE =====
let currentPage = 'dashboard';
let allVehicles = [];
let allBranches = [];
let currentFilters = { search: '', status: '', branch_id: '', brand: '', model: '', year_min: '', year_max: '' };
let searchTimeout = null;
let currentDetailId = null;
let currentThumbIndex = 0;

// ===== NAVIGATION =====
function navigateTo(page, data) {
    currentPage = page;
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    const target = document.getElementById('page-' + page);
    if (target) {
        target.classList.add('active');
        target.style.animation = 'none';
        target.offsetHeight;
        target.style.animation = '';
    }

    // Update nav items
    document.querySelectorAll('.bottom-nav .nav-item').forEach(n => {
        n.classList.toggle('active', n.dataset.page === page);
    });
    document.querySelectorAll('#desktopNav .nav-btn').forEach(n => {
        const isActive = n.dataset.page === page;
        n.classList.toggle('active', isActive);
        if (isActive) {
            n.style.background = 'rgba(249, 115, 22, 0.15)';
            n.style.color = '#fdba74';
        } else {
            n.style.background = 'transparent';
            n.style.color = '#94a3b8';
        }
    });

    // FAB visibility
    const fab = document.getElementById('fabBtn');
    if (fab) fab.style.display = (page === 'vehicles' || page === 'dashboard') ? 'flex' : 'none';

    // Load page data
    switch (page) {
        case 'dashboard': loadDashboard(); break;
        case 'vehicles': loadVehicles(); break;
        case 'branches': loadBranches(); break;
        case 'detail': if (data) showVehicleDetail(data); break;
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===== API HELPERS =====
async function api(url, options = {}) {
    try {
        const resp = await fetch(url, options);
        const text = await resp.text();
        try {
            return JSON.parse(text);
        } catch {
            console.error('API non-JSON response:', text.substring(0, 200));
            return { success: false, error: 'เซิร์ฟเวอร์ตอบกลับผิดปกติ (HTTP ' + resp.status + ')' };
        }
    } catch (err) {
        showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
        return { success: false, error: err.message };
    }
}

// ===== TOAST =====
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const icons = { success: 'bx-check-circle', error: 'bx-error-circle', info: 'bx-info-circle' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class='bx ${icons[type] || icons.info}'></i> ${message}`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== NUMBER FORMATTING =====
function formatNumber(num) {
    return new Intl.NumberFormat('th-TH').format(num || 0);
}

function formatCurrency(num) {
    return '฿' + formatNumber(num);
}

// Escape HTML for safe attribute injection
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// Format number input with commas as user types
function formatInputComma(el) {
    let pos = el.selectionStart;
    let oldLen = el.value.length;
    let raw = el.value.replace(/[^0-9.]/g, '');
    // Handle decimal
    let parts = raw.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    el.value = parts.length > 1 ? parts[0] + '.' + parts[1] : parts[0];
    // Adjust cursor position
    let newLen = el.value.length;
    pos += (newLen - oldLen);
    el.setSelectionRange(pos, pos);
}

// Get raw number from comma-formatted string
function stripCommas(str) {
    return String(str || '0').replace(/,/g, '');
}

// ===== STATUS HELPERS =====
const STATUS_MAP = {
    available: { label: 'พร้อมขาย', icon: '🟢', css: 'status-available' },
    reserved: { label: 'จอง', icon: '🟡', css: 'status-reserved' },
    sold: { label: 'ขายแล้ว', icon: '🔴', css: 'status-sold' },
    maintenance: { label: 'ซ่อม', icon: '🔵', css: 'status-maintenance' }
};

function getStatusBadge(status) {
    const s = STATUS_MAP[status] || STATUS_MAP.available;
    return `<span class="status-badge ${s.css}">${s.icon} ${s.label}</span>`;
}

function getStatusLabel(status) {
    return (STATUS_MAP[status] || STATUS_MAP.available).label;
}

// ===== DASHBOARD =====
async function loadDashboard() {
    const data = await api('api/dashboard.php');
    if (!data.success) return;

    const s = data.stats;
    const v = data.values;

    // Stats Grid
    document.getElementById('statsGrid').innerHTML = `
        <div class="glass-card stat-card">
            <div class="stat-icon" style="background:rgba(249,115,22,0.15);color:#fb923c;"><i class='bx bxs-car'></i></div>
            <div class="stat-value gradient-text">${s.total}</div>
            <div class="stat-label">รถทั้งหมด</div>
        </div>
        <div class="glass-card stat-card" style="cursor:pointer" onclick="goToVehiclesWithStatus('available')">
            <div class="stat-icon" style="background:rgba(16,185,129,0.15);color:#34d399;"><i class='bx bxs-check-circle'></i></div>
            <div class="stat-value" style="color:#34d399;">${s.available}</div>
            <div class="stat-label">พร้อมขาย</div>
        </div>
        <div class="glass-card stat-card" style="cursor:pointer" onclick="goToVehiclesWithStatus('reserved')">
            <div class="stat-icon" style="background:rgba(245,158,11,0.15);color:#fbbf24;"><i class='bx bxs-bookmark-star'></i></div>
            <div class="stat-value" style="color:#fbbf24;">${s.reserved}</div>
            <div class="stat-label">มีคนจอง</div>
        </div>
        <div class="glass-card stat-card" style="cursor:pointer" onclick="goToVehiclesWithStatus('sold')">
            <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:#f87171;"><i class='bx bxs-badge-check'></i></div>
            <div class="stat-value" style="color:#f87171;">${s.sold}</div>
            <div class="stat-label">ขายแล้ว</div>
        </div>
    `;

    // Financial Summary
    document.getElementById('financialSummary').innerHTML = `
        <h3 class="text-base font-bold mb-4 flex items-center gap-2">
            <i class='bx bxs-wallet text-orange-400'></i> สรุปมูลค่า
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-slate-500 mb-1">ราคาขายรวม (ในสต็อก)</div>
                <div class="text-lg font-bold text-emerald-400">${formatCurrency(v.stock_selling)}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500 mb-1">ซ่อม / ไม่มีสาขา</div>
                <div class="text-lg font-bold text-blue-400">${s.maintenance} / ${s.no_branch}</div>
            </div>
        </div>
    `;

    // Branch Breakdown
    const maxCount = Math.max(...data.by_branch.map(b => parseInt(b.count) || 0), 1);
    document.getElementById('branchBreakdown').innerHTML = data.by_branch.length ? data.by_branch.map(b => `
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="branch-tag" style="background:${b.color}20;color:${b.color};">
                        <i class='bx bxs-map-pin'></i> ${b.name}
                    </span>
                </div>
                <span class="text-sm font-bold">${b.count} คัน</span>
            </div>
            <div class="w-full bg-white/5 rounded-full h-2">
                <div class="branch-bar" style="width:${(b.count/maxCount)*100}%;background:${b.color};"></div>
            </div>
            <div class="flex gap-3 mt-1 text-xs text-slate-500">
                <span>พร้อมขาย: ${b.available||0}</span>
                <span>จอง: ${b.reserved||0}</span>
                <span>ขายแล้ว: ${b.sold||0}</span>
                <span>ซ่อม: ${b.maintenance||0}</span>
            </div>
        </div>
    `).join('') : '<p class="text-slate-500 text-sm text-center py-4">ยังไม่มีสาขา</p>';

    // Recent Vehicles
    document.getElementById('recentVehicles').innerHTML = data.recent_vehicles.length ? data.recent_vehicles.map(v => `
        <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 transition-all cursor-pointer mb-2" onclick="navigateTo('detail', ${v.id})">
            ${v.thumbnail
                ? `<img src="uploads/${v.thumbnail}" class="w-14 h-14 rounded-xl object-cover flex-shrink-0" alt="">`
                : `<div class="w-14 h-14 rounded-xl flex items-center justify-center bg-orange-500/10 text-orange-400 flex-shrink-0"><i class='bx bxs-car text-2xl'></i></div>`
            }
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-sm truncate">${v.brand} ${v.model}</div>
                <div class="text-xs text-slate-500">${v.year} • ${v.license_plate || '-'}</div>
            </div>
            <div class="text-right flex-shrink-0">
                ${getStatusBadge(v.status)}
                <div class="text-xs font-semibold mt-1 text-orange-300">${formatCurrency(v.selling_price)}</div>
            </div>
        </div>
    `).join('') : '<p class="text-slate-500 text-sm text-center py-4">ยังไม่มีรถในสต็อก</p>';

    // Populate advanced search dropdowns
    populateAdvancedSearch();
}

// ===== ADVANCED SEARCH =====
async function populateAdvancedSearch() {
    try {
        // Fetch vehicles data for brands/models/years
        const vData = await api('api/vehicles.php');
        if (vData && vData.success) {
            // Brand dropdown
            const brandSel = document.getElementById('advBrand');
            if (brandSel) {
                brandSel.innerHTML = '<option value="">-- ทุกยี่ห้อ --</option>';
                (vData.brands || []).forEach(b => {
                    brandSel.innerHTML += `<option value="${escapeHtml(b)}">${b}</option>`;
                });
            }

            // Store vehicles for cascading model dropdown
            window._advVehicles = vData.vehicles || [];
            populateModelDropdown('');

            // Year dropdowns
            const years = (vData.years || []).map(y => parseInt(y)).sort((a, b) => a - b);
            const yearMinSel = document.getElementById('advYearMin');
            const yearMaxSel = document.getElementById('advYearMax');
            if (yearMinSel && yearMaxSel) {
                yearMinSel.innerHTML = '<option value="">-- ไม่ระบุ --</option>';
                yearMaxSel.innerHTML = '<option value="">-- ไม่ระบุ --</option>';
                years.forEach(y => {
                    yearMinSel.innerHTML += `<option value="${y}">${y}</option>`;
                    yearMaxSel.innerHTML += `<option value="${y}">${y}</option>`;
                });
            }
        }

        // Branch dropdown
        const bData = await api('api/branches.php');
        if (bData && bData.success) {
            const branchSel = document.getElementById('advBranch');
            if (branchSel) {
                branchSel.innerHTML = '<option value="">-- ทุกสาขา --</option>';
                bData.branches.forEach(b => {
                    branchSel.innerHTML += `<option value="${b.id}">${b.name}</option>`;
                });
            }
        }
    } catch (err) {
        console.error('populateAdvancedSearch error:', err);
    }
}

function populateModelDropdown(selectedBrand) {
    const modelSel = document.getElementById('advModel');
    if (!modelSel) return;
    modelSel.innerHTML = '<option value="">-- ทุกรุ่น --</option>';

    const vehicles = window._advVehicles || [];
    const models = [...new Set(
        vehicles
            .filter(v => !selectedBrand || v.brand === selectedBrand)
            .map(v => v.model)
    )].sort();

    models.forEach(m => {
        modelSel.innerHTML += `<option value="${escapeHtml(m)}">${m}</option>`;
    });
}

function onAdvBrandChange() {
    const brand = document.getElementById('advBrand').value;
    populateModelDropdown(brand);
}

function advancedSearch() {
    const brand = document.getElementById('advBrand')?.value || '';
    const model = document.getElementById('advModel')?.value || '';
    const yearMin = document.getElementById('advYearMin')?.value || '';
    const yearMax = document.getElementById('advYearMax')?.value || '';
    const branchId = document.getElementById('advBranch')?.value || '';
    const status = document.getElementById('advStatus')?.value || '';

    // Set filters and navigate
    currentFilters = { search: '', status, branch_id: branchId, brand, model, year_min: yearMin, year_max: yearMax };

    // Navigate to vehicles page with set filters
    currentPage = 'vehicles';
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    const target = document.getElementById('page-vehicles');
    if (target) { target.classList.add('active'); target.style.animation = 'none'; target.offsetHeight; target.style.animation = ''; }
    document.querySelectorAll('.bottom-nav .nav-item').forEach(n => n.classList.toggle('active', n.dataset.page === 'vehicles'));
    document.querySelectorAll('#desktopNav .nav-btn').forEach(n => {
        const isActive = n.dataset.page === 'vehicles';
        n.classList.toggle('active', isActive);
        n.style.background = isActive ? 'rgba(249, 115, 22, 0.15)' : 'transparent';
        n.style.color = isActive ? '#fdba74' : '#94a3b8';
    });
    document.querySelectorAll('#statusFilters .filter-chip').forEach(c => c.classList.toggle('active', c.dataset.status === status));
    loadVehicles();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===== VEHICLES =====
async function loadVehicles() {
    const params = new URLSearchParams();
    if (currentFilters.search) params.set('search', currentFilters.search);
    if (currentFilters.status) params.set('status', currentFilters.status);
    if (currentFilters.branch_id) params.set('branch_id', currentFilters.branch_id);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.model) params.set('model', currentFilters.model);
    if (currentFilters.year_min) params.set('year_min', currentFilters.year_min);
    if (currentFilters.year_max) params.set('year_max', currentFilters.year_max);

    const data = await api('api/vehicles.php?' + params.toString());
    if (!data.success) return;

    allVehicles = data.vehicles;
    document.getElementById('vehicleCount').textContent = `${data.total} คัน`;

    // Branch filter chips (use cache if available, else fetch)
    if (!allBranches.length) {
        const branchData = await api('api/branches.php');
        if (branchData.success) allBranches = branchData.branches;
    }
    if (allBranches.length) {
        document.getElementById('branchFilters').innerHTML = `
            <button class="filter-chip ${!currentFilters.branch_id ? 'active' : ''}" onclick="filterByBranch('')">ทุกสาขา</button>
            ${allBranches.map(b => `
                <button class="filter-chip ${currentFilters.branch_id == b.id ? 'active' : ''}" onclick="filterByBranch('${b.id}')" style="${currentFilters.branch_id == b.id ? `background:${b.color};border-color:${b.color};color:white;` : ''}">
                    ${b.name}
                </button>
            `).join('')}
        `;
    }

    renderVehicleGrid(allVehicles);
}

function renderVehicleGrid(vehicles) {
    const grid = document.getElementById('vehicleGrid');
    if (!vehicles.length) {
        grid.innerHTML = `
            <div class="empty-state col-span-full">
                <i class='bx bxs-car'></i>
                <p class="text-lg font-semibold mb-1">ไม่พบรถยนต์</p>
                <p class="text-sm">ลองเปลี่ยนตัวกรอง หรือเพิ่มรถใหม่</p>
            </div>`;
        return;
    }

    grid.innerHTML = vehicles.map((v, i) => {
        const img = v.images && v.images.length ? `<img src="uploads/${v.images[0].filename}" class="vehicle-image" alt="${v.brand} ${v.model}" loading="lazy">` : `<div class="no-image"><i class='bx bxs-car'></i></div>`;
        const branchTag = v.branch_name ? `<span class="branch-tag" style="background:${v.branch_color}20;color:${v.branch_color};font-size:11px;"><i class='bx bxs-map-pin'></i> ${v.branch_name}</span>` : '';

        return `
        <div class="glass-card vehicle-card cursor-pointer" onclick="navigateTo('detail', ${v.id})" style="animation-delay:${i * 0.05}s">
            <div class="relative overflow-hidden">
                ${img}
                <div class="absolute top-3 left-3">${getStatusBadge(v.status)}</div>
                ${v.images && v.images.length > 1 ? `<div class="absolute bottom-3 right-3 bg-black/60 text-white text-xs px-2 py-1 rounded-full"><i class='bx bxs-image'></i> ${v.images.length}</div>` : ''}
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between mb-1">
                    <h3 class="font-bold text-base">${v.brand} ${v.model}</h3>
                    <span class="text-xs text-slate-500">${v.year}</span>
                </div>
                <div class="flex items-center gap-2 mb-2">
                    ${branchTag}
                    ${v.color ? `<span class="text-xs text-slate-500">${v.color}</span>` : ''}
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-slate-500">ราคาขาย</div>
                        <div class="text-lg font-bold gradient-text">${formatCurrency(v.selling_price)}</div>
                    </div>
                    ${v.license_plate ? `<div class="text-right"><div class="text-xs text-slate-500">ทะเบียน</div><div class="text-sm font-semibold">${v.license_plate}</div></div>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');
}

// ===== SEARCH & FILTER =====
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentFilters.search = document.getElementById('searchInput').value;
        loadVehicles();
    }, 400);
}

function filterByStatus(status) {
    currentFilters.status = status;
    document.querySelectorAll('#statusFilters .filter-chip').forEach(c => {
        c.classList.toggle('active', c.dataset.status === status);
    });
    loadVehicles();
}

function filterByBranch(branchId) {
    currentFilters.branch_id = branchId;
    loadVehicles();
}

// Navigate to vehicles page and set status filter (avoids double API call)
function goToVehiclesWithStatus(status) {
    currentFilters.status = status;
    currentPage = 'vehicles';
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    const target = document.getElementById('page-vehicles');
    if (target) { target.classList.add('active'); target.style.animation = 'none'; target.offsetHeight; target.style.animation = ''; }
    document.querySelectorAll('.bottom-nav .nav-item').forEach(n => n.classList.toggle('active', n.dataset.page === 'vehicles'));
    document.querySelectorAll('#desktopNav .nav-btn').forEach(n => {
        const isActive = n.dataset.page === 'vehicles';
        n.classList.toggle('active', isActive);
        n.style.background = isActive ? 'rgba(249, 115, 22, 0.15)' : 'transparent';
        n.style.color = isActive ? '#fdba74' : '#94a3b8';
    });
    document.querySelectorAll('#statusFilters .filter-chip').forEach(c => c.classList.toggle('active', c.dataset.status === status));
    loadVehicles();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===== VEHICLE DETAIL (ALBUM STYLE) =====
async function showVehicleDetail(vehicleId) {
    currentDetailId = vehicleId;
    const data = await api('api/vehicles.php?id=' + vehicleId);
    if (!data.success || !data.vehicles.length) {
        showToast('ไม่พบข้อมูลรถ', 'error');
        return navigateTo('vehicles');
    }

    const v = data.vehicles[0];
    const images = v.images || [];
    const branchData = await api('api/branches.php');
    const branches = branchData.success ? branchData.branches : [];

    const container = document.getElementById('vehicleDetail');
    container.innerHTML = `
        <!-- Album Header -->
        <div class="album-header">
            <button onclick="navigateTo('vehicles')" class="album-back-btn">
                <i class='bx bx-x text-2xl'></i>
            </button>
            <div class="album-title">
                <h2 class="text-base font-bold">${v.brand} ${v.model} <span class="text-slate-500 font-normal">(${images.length})</span></h2>
                <p class="text-xs text-slate-500">${v.year} • ${v.color || ''}</p>
            </div>
            <div class="album-menu-wrap">
                <button onclick="toggleAlbumMenu()" class="album-menu-btn" id="albumMenuBtn">
                    <i class='bx bx-dots-vertical-rounded text-xl'></i>
                </button>
                <div class="album-dropdown" id="albumDropdown">
                    <button onclick="document.getElementById('albumUploadInput').click(); toggleAlbumMenu();">
                        <i class='bx bx-upload'></i> อัปโหลด
                    </button>
                    <button onclick="renameAlbum(${v.id}); toggleAlbumMenu();">
                        <i class='bx bx-edit'></i> เปลี่ยนชื่ออัลบั้ม
                    </button>
                    ${images.length ? `<button onclick="downloadAlbum(${v.id}); toggleAlbumMenu();">
                        <i class='bx bx-download'></i> ดาวน์โหลดอัลบั้ม
                    </button>` : ''}
                    <button onclick="shareAlbum(${v.id}); toggleAlbumMenu();">
                        <i class='bx bx-share-alt'></i> แชร์อัลบั้ม
                    </button>
                    <button class="text-red-400" onclick="deleteAlbum(${v.id}); toggleAlbumMenu();">
                        <i class='bx bx-trash'></i> ลบอัลบั้ม
                    </button>
                </div>
            </div>
            <input type="file" id="albumUploadInput" multiple accept="image/*" hidden onchange="uploadMoreImages(${v.id})">
        </div>

        <!-- Photo Grid -->
        <div class="album-grid">
            ${images.length ? images.map((img, i) => `
                <div class="album-photo" onclick="openLightbox(${i})">
                    <img src="uploads/${img.filename}" alt="" loading="lazy">
                    <button onclick="event.stopPropagation();deleteImage(${img.id})" class="album-photo-delete">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
            `).join('') : `
                <div class="album-empty" onclick="document.getElementById('albumUploadInput').click()">
                    <i class='bx bx-camera text-4xl text-orange-400/30'></i>
                    <p class="text-sm text-slate-500 mt-2">แตะเพื่อเพิ่มรูปภาพ</p>
                </div>
            `}
        </div>

        <!-- Vehicle Info -->
        <div class="px-4 pb-6">
            <!-- Price & Status -->
            <div class="glass-card-static p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-slate-500">ราคาขาย</div>
                        <div class="text-xl font-bold text-orange-400">${formatCurrency(v.selling_price)}</div>
                    </div>
                    <div class="text-right">
                        ${getStatusBadge(v.status)}
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="glass-card-static p-4 mb-4">
                <h3 class="font-bold text-sm mb-3 text-slate-400">ข้อมูลรถ</h3>
                <div class="info-row"><span class="info-label">ทะเบียน</span><span class="info-value">${v.license_plate || '-'}</span></div>
                <div class="info-row"><span class="info-label">VIN</span><span class="info-value text-xs">${v.vin || '-'}</span></div>
                <div class="info-row"><span class="info-label">เลขไมล์</span><span class="info-value">${formatNumber(v.mileage)} กม.</span></div>
                <div class="info-row">
                    <span class="info-label">สาขา</span>
                    <span class="info-value">
                        ${v.branch_name ? `<span class="branch-tag" style="background:${v.branch_color}20;color:${v.branch_color};"><i class='bx bxs-map-pin'></i> ${v.branch_name}</span>` : '<span class="text-slate-500">ยังไม่กำหนด</span>'}
                    </span>
                </div>
                ${v.notes ? `<div class="info-row"><span class="info-label">หมายเหตุ</span><span class="info-value text-slate-400">${v.notes}</span></div>` : ''}
            </div>

            <!-- Quick Actions -->
            <div class="glass-card-static p-4 mb-4">
                <h3 class="font-bold text-sm mb-3 text-slate-400">จัดการ</h3>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <select id="detailStatus" class="form-select !text-sm" onchange="updateVehicleField(${v.id}, 'status', this.value)">
                        ${Object.entries(STATUS_MAP).map(([k, s]) => `<option value="${k}" ${v.status === k ? 'selected' : ''}>${s.icon} ${s.label}</option>`).join('')}
                    </select>
                    <select id="detailBranch" class="form-select !text-sm" onchange="updateVehicleField(${v.id}, 'branch_id', this.value)">
                        <option value="">-- ไม่ระบุสาขา --</option>
                        ${branches.map(b => `<option value="${b.id}" ${v.branch_id == b.id ? 'selected' : ''}>${b.name}</option>`).join('')}
                    </select>
                </div>
                <button onclick="openVehicleModal(${v.id})" class="btn-secondary w-full !text-sm justify-center"><i class='bx bx-edit'></i> แก้ไขข้อมูลรถ</button>
            </div>
        </div>
    `;

    // Store images for lightbox
    window._detailImages = images;
    
    // Close dropdown when clicking outside
    document.addEventListener('click', closeAlbumMenuOnClickOutside);
}

// ===== ALBUM FUNCTIONS =====
function toggleAlbumMenu() {
    const dd = document.getElementById('albumDropdown');
    if (dd) dd.classList.toggle('show');
}

function closeAlbumMenuOnClickOutside(e) {
    const dd = document.getElementById('albumDropdown');
    const btn = document.getElementById('albumMenuBtn');
    if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) {
        dd.classList.remove('show');
    }
}

async function renameAlbum(id) {
    const data = await api('api/vehicles.php?id=' + id);
    if (!data.success || !data.vehicles.length) return;
    const v = data.vehicles[0];
    
    const brand = prompt('ยี่ห้อ:', v.brand);
    if (brand === null) return;
    const model = prompt('รุ่น:', v.model);
    if (model === null) return;
    
    if (!brand.trim() || !model.trim()) {
        return showToast('กรุณากรอกยี่ห้อและรุ่น', 'error');
    }
    
    const res = await api('api/vehicles.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, brand: brand.trim(), model: model.trim() })
    });
    if (res.success) {
        showToast('เปลี่ยนชื่อเรียบร้อย');
        showVehicleDetail(id);
    }
}

function downloadAlbum(id) {
    window.location.href = 'api/download_album.php?id=' + id;
    showToast('กำลังดาวน์โหลด...');
}

async function shareAlbum(id) {
    const res = await api('api/vehicles.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    if (res.success && res.share_token) {
        const url = window.location.origin + window.location.pathname.replace('index.php', '') + 'share.php?token=' + res.share_token;
        try {
            await navigator.clipboard.writeText(url);
            showToast('คัดลอกลิงก์แชร์แล้ว!');
        } catch {
            prompt('คัดลอกลิงก์นี้:', url);
        }
    } else {
        showToast('ไม่สามารถสร้างลิงก์แชร์ได้', 'error');
    }
}

async function deleteAlbum(id) {
    if (!confirm('ลบอัลบั้มนี้? รูปภาพทั้งหมดจะถูกลบด้วย')) return;
    const res = await api('api/vehicles.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    if (res.success) {
        showToast('ลบอัลบั้มเรียบร้อยแล้ว');
        navigateTo('vehicles');
    }
}

// ===== LIGHTBOX =====
function openLightbox(index) {
    const images = window._detailImages || [];
    if (!images.length) return;

    let current = index;
    const lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.id = 'lightboxOverlay';

    function render() {
        lb.innerHTML = `
            <button class="lb-close" onclick="closeLightbox()"><i class='bx bx-x'></i></button>
            ${images.length > 1 ? `<button class="lb-nav lb-prev" onclick="lbNav(-1)"><i class='bx bx-chevron-left'></i></button>` : ''}
            <img src="uploads/${images[current].filename}" alt="">
            ${images.length > 1 ? `<button class="lb-nav lb-next" onclick="lbNav(1)"><i class='bx bx-chevron-right'></i></button>` : ''}
            <div class="lb-counter">${current + 1} / ${images.length}</div>
        `;
    }

    window.lbNav = function(dir) {
        current = (current + dir + images.length) % images.length;
        render();
    };

    render();
    document.body.appendChild(lb);
    document.body.style.overflow = 'hidden';

    lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
    document.addEventListener('keydown', handleLbKey);
}

function handleLbKey(e) {
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') window.lbNav && window.lbNav(-1);
    if (e.key === 'ArrowRight') window.lbNav && window.lbNav(1);
}

function closeLightbox() {
    const lb = document.getElementById('lightboxOverlay');
    if (lb) lb.remove();
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleLbKey);
}

function selectThumb(index) {
    const images = window._detailImages || [];
    if (!images[index]) return;
    currentThumbIndex = index;
    document.getElementById('mainImage').src = 'uploads/' + images[index].filename;
    document.querySelectorAll('.detail-thumbnails img').forEach((t, i) => t.classList.toggle('active', i === index));
}

// ===== VEHICLE MODAL =====
function openVehicleModal(editId) {
    const isEdit = !!editId;
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'vehicleModal';

    let formData = { brand: '', model: '', year: new Date().getFullYear(), color: '', vin: '', license_plate: '', mileage: 0, selling_price: 0, branch_id: '', status: 'available', notes: '' };

    const populateAndShow = (data) => {
        if (data) formData = { ...formData, ...data };
        modal.innerHTML = `
        <div class="modal-content p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold">${isEdit ? 'แก้ไขข้อมูลรถ' : 'เพิ่มรถใหม่'}</h2>
                <button onclick="closeModal('vehicleModal')" class="w-10 h-10 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all"><i class='bx bx-x text-2xl'></i></button>
            </div>
            <form id="vehicleForm" onsubmit="submitVehicle(event, ${editId || 'null'})" enctype="multipart/form-data">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div><label class="form-label">ยี่ห้อ *</label><input name="brand" class="form-input" value="${escapeHtml(formData.brand)}" required placeholder="เช่น Toyota"></div>
                    <div><label class="form-label">รุ่น *</label><input name="model" class="form-input" value="${escapeHtml(formData.model)}" required placeholder="เช่น Camry"></div>
                </div>
                <div class="grid grid-cols-3 gap-3 mb-3">
                    <div><label class="form-label">ปี</label><input name="year" type="number" class="form-input" value="${formData.year}"></div>
                    <div><label class="form-label">สี</label><input name="color" class="form-input" value="${escapeHtml(formData.color)}" placeholder="เช่น ขาว"></div>
                    <div><label class="form-label">เลขไมล์</label><input name="mileage" type="text" inputmode="numeric" class="form-input" value="${formatNumber(formData.mileage)}" oninput="formatInputComma(this)"></div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div><label class="form-label">เลขตัวถัง (VIN)</label><input name="vin" class="form-input" value="${escapeHtml(formData.vin)}"></div>
                    <div><label class="form-label">ทะเบียน</label><input name="license_plate" class="form-input" value="${escapeHtml(formData.license_plate)}"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">ราคาขาย</label>
                    <input name="selling_price" type="text" inputmode="decimal" class="form-input" value="${formatNumber(formData.selling_price)}" oninput="formatInputComma(this)">
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="form-label">สาขา</label>
                        <select name="branch_id" class="form-select" id="modalBranch">
                            <option value="">-- ไม่ระบุ --</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            ${Object.entries(STATUS_MAP).map(([k, s]) => `<option value="${k}" ${formData.status === k ? 'selected' : ''}>${s.label}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">หมายเหตุ</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="หมายเหตุเพิ่มเติม...">${escapeHtml(formData.notes)}</textarea>
                </div>
                ${!isEdit ? `
                <div class="mb-4">
                    <label class="form-label">รูปภาพ</label>
                    <div class="upload-zone" onclick="document.getElementById('vehicleImages').click()" id="modalUploadZone">
                        <input type="file" id="vehicleImages" name="images" multiple accept="image/*" hidden onchange="previewModalImages(this)">
                        <i class='bx bx-cloud-upload'></i>
                        <p class="text-sm text-slate-500">คลิกเพื่ออัพโหลดรูปภาพ</p>
                    </div>
                    <div class="upload-preview" id="modalPreview"></div>
                </div>
                ` : ''}
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('vehicleModal')" class="btn-secondary flex-1 justify-center">ยกเลิก</button>
                    <button type="submit" class="btn-primary flex-1 justify-center" id="submitVehicleBtn">${isEdit ? 'บันทึก' : 'เพิ่มรถ'}</button>
                </div>
            </form>
        </div>`;

        document.getElementById('modalContainer').appendChild(modal);
        document.body.style.overflow = 'hidden';

        // Populate branch dropdown
        api('api/branches.php').then(bd => {
            if (bd.success) {
                const sel = document.getElementById('modalBranch');
                bd.branches.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.name;
                    if (formData.branch_id == b.id) opt.selected = true;
                    sel.appendChild(opt);
                });
            }
        });

        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal('vehicleModal'); });
    };

    if (isEdit) {
        api('api/vehicles.php?id=' + editId).then(d => {
            if (d.success && d.vehicles.length) populateAndShow(d.vehicles[0]);
        });
    } else {
        populateAndShow(null);
    }
}

async function submitVehicle(event, editId) {
    event.preventDefault();
    const btn = document.getElementById('submitVehicleBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> กำลังบันทึก...';

    const form = document.getElementById('vehicleForm');

    if (editId) {
        // Update via JSON
        const fd = new FormData(form);
        const payload = { id: editId };
        for (const [key, value] of fd.entries()) {
            if (key !== 'images') payload[key] = value;
        }
        // Strip commas from numeric fields
        if (payload.mileage) payload.mileage = stripCommas(payload.mileage);
        if (payload.selling_price) payload.selling_price = stripCommas(payload.selling_price);
        const result = await api('api/vehicles.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (result.success) {
            showToast(result.message);
            closeModal('vehicleModal');
            if (currentPage === 'detail') showVehicleDetail(editId);
            else loadVehicles();
        } else {
            showToast(result.error || 'เกิดข้อผิดพลาด', 'error');
            btn.disabled = false;
            btn.innerHTML = 'บันทึก';
        }
    } else {
        // Create with FormData (supports file upload)
        const fd = new FormData(form);
        // Strip commas from numeric fields
        fd.set('mileage', stripCommas(fd.get('mileage')));
        fd.set('selling_price', stripCommas(fd.get('selling_price')));
        // Re-key file input from 'images' to 'images[]' for PHP array handling
        const fileInput = document.getElementById('vehicleImages');
        if (fileInput && fileInput.files.length) {
            fd.delete('images');
            for (const f of fileInput.files) {
                fd.append('images[]', f);
            }
        }
        const result = await api('api/vehicles.php', { method: 'POST', body: fd });
        if (result.success) {
            showToast(result.message);
            closeModal('vehicleModal');
            navigateTo('vehicles');
        } else {
            showToast(result.error || 'เกิดข้อผิดพลาด', 'error');
            btn.disabled = false;
            btn.innerHTML = 'เพิ่มรถ';
        }
    }
}

function previewModalImages(input) {
    const preview = document.getElementById('modalPreview');
    preview.innerHTML = '';
    for (let f of input.files) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.innerHTML += `<div class="preview-item"><img src="${e.target.result}" alt=""></div>`;
        };
        reader.readAsDataURL(f);
    }
}

// ===== QUICK UPDATE =====
async function updateVehicleField(id, field, value) {
    const payload = { id };
    payload[field] = value;
    const result = await api('api/vehicles.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    if (result.success) {
        showToast(result.message);
        showVehicleDetail(id);
    } else {
        showToast(result.error || 'ไม่สามารถอัพเดทได้', 'error');
    }
}

// ===== DELETE VEHICLE =====
function confirmDeleteVehicle(id) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'deleteModal';
    modal.innerHTML = `
        <div class="modal-content p-6" style="max-width:400px;margin:auto;">
            <div class="text-center mb-4">
                <div class="w-16 h-16 rounded-full bg-red-500/15 flex items-center justify-center mx-auto mb-3">
                    <i class='bx bx-trash text-3xl text-red-400'></i>
                </div>
                <h3 class="text-lg font-bold">ยืนยันการลบ</h3>
                <p class="text-sm text-slate-400 mt-1">ข้อมูลรถและรูปภาพทั้งหมดจะถูกลบอย่างถาวร</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeModal('deleteModal')" class="btn-secondary flex-1 justify-center">ยกเลิก</button>
                <button onclick="deleteVehicle(${id})" class="btn-danger flex-1 justify-center" id="confirmDeleteBtn">ลบรถ</button>
            </div>
        </div>`;
    document.getElementById('modalContainer').appendChild(modal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal('deleteModal'); });
}

async function deleteVehicle(id) {
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';
    const result = await api('api/vehicles.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    if (result.success) {
        showToast(result.message);
        closeModal('deleteModal');
        navigateTo('vehicles');
    } else {
        showToast(result.error || 'ไม่สามารถลบได้', 'error');
        btn.disabled = false;
        btn.innerHTML = 'ลบรถ';
    }
}

// ===== IMAGE MANAGEMENT =====
async function uploadMoreImages(vehicleId) {
    const input = document.getElementById('addMoreImages');
    if (!input.files.length) return;

    const fd = new FormData();
    fd.append('vehicle_id', vehicleId);
    for (let f of input.files) fd.append('images[]', f);

    showToast('กำลังอัพโหลด...', 'info');
    const result = await api('api/vehicle_images.php', { method: 'POST', body: fd });
    if (result.success) {
        showToast(result.message);
        showVehicleDetail(vehicleId);
    } else {
        showToast(result.error || 'อัพโหลดไม่สำเร็จ', 'error');
    }
    input.value = '';  // Reset so re-selecting same files triggers onchange
}

async function deleteImage(imageId) {
    const result = await api('api/vehicle_images.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: imageId })
    });
    if (result.success) {
        showToast(result.message);
        if (currentDetailId) showVehicleDetail(currentDetailId);
    } else {
        showToast(result.error || 'ไม่สามารถลบรูปได้', 'error');
    }
}

// ===== BRANCHES =====
async function loadBranches() {
    const data = await api('api/branches.php');
    if (!data.success) return;
    allBranches = data.branches;

    const container = document.getElementById('branchList');
    if (!allBranches.length) {
        container.innerHTML = `<div class="empty-state"><i class='bx bxs-map'></i><p class="text-lg font-semibold mb-1">ยังไม่มีสาขา</p><p class="text-sm">เพิ่มสาขาเพื่อจัดการตำแหน่งรถ</p></div>`;
        return;
    }

    container.innerHTML = allBranches.map(b => {
        const safeName = b.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        return `
        <div class="glass-card p-4 mb-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:${b.color}20;color:${b.color};">
                    <i class='bx bxs-map-pin text-xl'></i>
                </div>
                <div>
                    <h3 class="font-bold">${b.name}</h3>
                    <p class="text-xs text-slate-500">${b.vehicle_count} คัน (พร้อมขาย ${b.available_count})</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="color" value="${b.color}" class="w-8 h-8 rounded-lg border-0 cursor-pointer" onchange="updateBranchColor(${b.id}, this.value)" title="เปลี่ยนสี">
                <button onclick="openBranchModal(${b.id}, '${safeName}', '${b.color}')" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all text-slate-400">
                    <i class='bx bx-edit'></i>
                </button>
                <button onclick="confirmDeleteBranch(${b.id}, '${safeName}')" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-red-500/20 transition-all text-red-400">
                    <i class='bx bx-trash'></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

function openBranchModal(editId, editName, editColor) {
    const isEdit = !!editId;
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'branchModal';
    modal.innerHTML = `
        <div class="modal-content p-6" style="max-width:420px;margin:auto;">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold">${isEdit ? 'แก้ไขสาขา' : 'เพิ่มสาขาใหม่'}</h2>
                <button onclick="closeModal('branchModal')" class="w-10 h-10 rounded-xl flex items-center justify-center hover:bg-white/10"><i class='bx bx-x text-2xl'></i></button>
            </div>
            <div class="mb-3">
                <label class="form-label">ชื่อสาขา</label>
                <input id="branchNameInput" class="form-input" value="${editName || ''}" placeholder="เช่น สาขาบางนา" required>
            </div>
            <div class="mb-4">
                <label class="form-label">สี</label>
                <input id="branchColorInput" type="color" class="form-input !p-1 !h-12" value="${editColor || '#f97316'}">
            </div>
            <div class="flex gap-3">
                <button onclick="closeModal('branchModal')" class="btn-secondary flex-1 justify-center">ยกเลิก</button>
                <button onclick="saveBranch(${editId || 'null'})" class="btn-primary flex-1 justify-center" id="saveBranchBtn">${isEdit ? 'บันทึก' : 'เพิ่ม'}</button>
            </div>
        </div>`;
    document.getElementById('modalContainer').appendChild(modal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal('branchModal'); });
    document.getElementById('branchNameInput').focus();
}

async function saveBranch(editId) {
    const name = document.getElementById('branchNameInput').value.trim();
    const color = document.getElementById('branchColorInput').value;
    if (!name) return showToast('กรุณากรอกชื่อสาขา', 'error');

    const btn = document.getElementById('saveBranchBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';

    const payload = { name, color };
    if (editId) payload.id = editId;

    const result = await api('api/branches.php', {
        method: editId ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    if (result.success) {
        showToast(result.message);
        closeModal('branchModal');
        allBranches = [];  // clear cache
        loadBranches();
    } else {
        showToast(result.error || 'ไม่สามารถบันทึกสาขาได้', 'error');
        btn.disabled = false;
        btn.innerHTML = editId ? 'บันทึก' : 'เพิ่ม';
    }
}

async function updateBranchColor(id, color) {
    const result = await api('api/branches.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, color })
    });
    if (result.success) {
        allBranches = [];  // clear cache
        loadBranches();
    } else {
        showToast(result.error || 'ไม่สามารถเปลี่ยนสีได้', 'error');
    }
}

function confirmDeleteBranch(id, name) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'deleteBranchModal';
    modal.innerHTML = `
        <div class="modal-content p-6" style="max-width:400px;margin:auto;">
            <div class="text-center mb-4">
                <div class="w-16 h-16 rounded-full bg-red-500/15 flex items-center justify-center mx-auto mb-3">
                    <i class='bx bx-trash text-3xl text-red-400'></i>
                </div>
                <h3 class="text-lg font-bold">ลบสาขา "${name}"</h3>
                <p class="text-sm text-slate-400 mt-1">รถในสาขานี้จะถูกเปลี่ยนเป็น "ไม่ระบุสาขา"</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeModal('deleteBranchModal')" class="btn-secondary flex-1 justify-center">ยกเลิก</button>
                <button onclick="deleteBranch(${id})" class="btn-danger flex-1 justify-center">ลบ</button>
            </div>
        </div>`;
    document.getElementById('modalContainer').appendChild(modal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal('deleteBranchModal'); });
}

async function deleteBranch(id) {
    const result = await api('api/branches.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    if (result.success) {
        showToast(result.message);
        closeModal('deleteBranchModal');
        allBranches = [];  // clear cache so it refetches
        loadBranches();
        loadDashboard();
    } else {
        showToast(result.error || 'ไม่สามารถลบสาขาได้', 'error');
    }
}

// ===== MODAL HELPERS =====
function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.opacity = '0';
        modal.style.transition = 'opacity 0.2s ease';
        setTimeout(() => {
            modal.remove();
            if (!document.querySelector('.modal-overlay')) document.body.style.overflow = '';
        }, 200);
    }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    navigateTo('dashboard');
    
    // Keyboard shortcut
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal-overlay');
            if (modals.length) closeModal(modals[modals.length - 1].id);
        }
    });
});

// ===== LOGOUT =====
async function logout() {
    if (!confirm('ต้องการออกจากระบบ?')) return;
    await fetch('api/auth.php', { method: 'DELETE' });
    window.location.href = 'login.php';
}
