<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Stock Car - ระบบสต็อกรถยนต์</title>
    <meta name="description" content="ระบบจัดการสต็อกรถยนต์ สำหรับเต็นท์รถ">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>
    <!-- ===== Top Header ===== -->
    <header class="top-header flex items-center justify-between px-4 lg:px-8">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-lg font-bold" style="background: var(--accent-gradient);">
                <i class='bx bxs-car'></i>
            </div>
            <div>
                <h1 class="text-lg font-bold gradient-text leading-tight">StockCar</h1>
                <p class="text-xs text-slate-500 hidden sm:block">ระบบสต็อกรถยนต์</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center gap-1" id="desktopNav">
                <button onclick="navigateTo('dashboard')" class="nav-btn active px-4 py-2 rounded-xl text-sm font-medium transition-all" data-page="dashboard">
                    <i class='bx bxs-dashboard mr-1'></i> แดชบอร์ด
                </button>
                <button onclick="navigateTo('vehicles')" class="nav-btn px-4 py-2 rounded-xl text-sm font-medium transition-all" data-page="vehicles">
                    <i class='bx bxs-car mr-1'></i> รถยนต์
                </button>
                <button onclick="navigateTo('branches')" class="nav-btn px-4 py-2 rounded-xl text-sm font-medium transition-all" data-page="branches">
                    <i class='bx bxs-map mr-1'></i> สาขา
                </button>
            </nav>
            <button onclick="openVehicleModal()" class="hidden md:flex btn-primary !py-2 !px-4 !text-sm !rounded-xl">
                <i class='bx bx-plus'></i> เพิ่มรถ
            </button>
            <button onclick="logout()" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all text-slate-400 hover:text-red-400" title="ออกจากระบบ">
                <i class='bx bx-log-out text-xl'></i>
            </button>
        </div>
    </header>

    <!-- ===== Main Content ===== -->
    <main class="main-content px-4 lg:px-8 max-w-7xl mx-auto">

        <!-- Dashboard Section -->
        <section id="page-dashboard" class="page-section active">
            <div class="mb-6">
                <h2 class="text-2xl font-bold">แดชบอร์ด</h2>
                <p class="text-slate-500 text-sm mt-1">ภาพรวมสต็อกรถยนต์</p>
            </div>
            
            <!-- Advanced Search -->
            <div class="glass-card-static p-5 mb-6">
                <h3 class="text-base font-bold mb-4 flex items-center gap-2">
                    <i class='bx bx-search-alt text-orange-400'></i> ค้นหาอย่างละเอียด
                </h3>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="form-label">ยี่ห้อ</label>
                        <select id="advBrand" class="form-select" onchange="onAdvBrandChange()">
                            <option value="">-- ทุกยี่ห้อ --</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">รุ่น</label>
                        <select id="advModel" class="form-select">
                            <option value="">-- ทุกรุ่น --</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="form-label">ปีเริ่มต้น</label>
                        <select id="advYearMin" class="form-select">
                            <option value="">-- ไม่ระบุ --</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">ปีสิ้นสุด</label>
                        <select id="advYearMax" class="form-select">
                            <option value="">-- ไม่ระบุ --</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="form-label">สาขา</label>
                        <select id="advBranch" class="form-select">
                            <option value="">-- ทุกสาขา --</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">สถานะ</label>
                        <select id="advStatus" class="form-select">
                            <option value="">-- ทั้งหมด --</option>
                            <option value="available">🟢 พร้อมขาย</option>
                            <option value="reserved">🟡 จอง</option>
                            <option value="sold">🔴 ขายแล้ว</option>
                            <option value="maintenance">🔵 ซ่อม</option>
                        </select>
                    </div>
                </div>
                <button onclick="advancedSearch()" class="btn-primary w-full justify-center">
                    <i class='bx bx-search'></i> ค้นหา
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" id="statsGrid">
                <!-- Filled by JS -->
            </div>
            
            <!-- Financial Summary -->
            <div class="glass-card-static p-5 mb-6" id="financialSummary">
                <!-- Filled by JS -->
            </div>

            <!-- Branch Breakdown -->
            <div class="glass-card-static p-5 mb-6">
                <h3 class="text-base font-bold mb-4 flex items-center gap-2">
                    <i class='bx bxs-map text-orange-400'></i> สต็อกแยกตามสาขา
                </h3>
                <div id="branchBreakdown">
                    <!-- Filled by JS -->
                </div>
            </div>

            <!-- Recent Vehicles -->
            <div class="glass-card-static p-5">
                <h3 class="text-base font-bold mb-4 flex items-center gap-2">
                    <i class='bx bxs-time-five text-orange-400'></i> รถที่เพิ่มล่าสุด
                </h3>
                <div id="recentVehicles">
                    <!-- Filled by JS -->
                </div>
            </div>
        </section>

        <!-- Vehicles Section -->
        <section id="page-vehicles" class="page-section">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold">รถยนต์</h2>
                    <p class="text-slate-500 text-sm mt-1" id="vehicleCount">กำลังโหลด...</p>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="mb-4">
                <div class="search-bar mb-3">
                    <i class='bx bx-search'></i>
                    <input type="text" id="searchInput" class="form-input" placeholder="ค้นหายี่ห้อ, รุ่น, ทะเบียน, VIN..." oninput="debounceSearch()">
                </div>
                <div class="flex gap-2 overflow-x-auto pb-2" id="statusFilters">
                    <button class="filter-chip active" onclick="filterByStatus('')" data-status="">ทั้งหมด</button>
                    <button class="filter-chip" onclick="filterByStatus('available')" data-status="available">🟢 พร้อมขาย</button>
                    <button class="filter-chip" onclick="filterByStatus('reserved')" data-status="reserved">🟡 จอง</button>
                    <button class="filter-chip" onclick="filterByStatus('sold')" data-status="sold">🔴 ขายแล้ว</button>
                    <button class="filter-chip" onclick="filterByStatus('maintenance')" data-status="maintenance">🔵 ซ่อม</button>
                </div>
                <!-- Branch Filter -->
                <div class="flex gap-2 overflow-x-auto pb-2 mt-2" id="branchFilters">
                    <!-- Filled by JS -->
                </div>
            </div>

            <!-- Vehicle Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="vehicleGrid">
                <!-- Filled by JS -->
            </div>
        </section>

        <!-- Vehicle Detail Section -->
        <section id="page-detail" class="page-section">
            <div id="vehicleDetail">
                <!-- Filled by JS -->
            </div>
        </section>

        <!-- Branches Section -->
        <section id="page-branches" class="page-section">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold">จัดการสาขา</h2>
                    <p class="text-slate-500 text-sm mt-1">เพิ่ม แก้ไข หรือลบสาขา</p>
                </div>
                <button onclick="openBranchModal()" class="btn-primary !py-2 !px-4 !text-sm">
                    <i class='bx bx-plus'></i> เพิ่มสาขา
                </button>
            </div>
            <div id="branchList">
                <!-- Filled by JS -->
            </div>
        </section>
    </main>

    <!-- ===== Bottom Navigation (Mobile) ===== -->
    <nav class="bottom-nav flex md:hidden">
        <a class="nav-item active" onclick="navigateTo('dashboard')" data-page="dashboard">
            <i class='bx bxs-dashboard'></i>
            <span>แดชบอร์ด</span>
        </a>
        <a class="nav-item" onclick="navigateTo('vehicles')" data-page="vehicles">
            <i class='bx bxs-car'></i>
            <span>รถยนต์</span>
        </a>
        <a class="nav-item" onclick="navigateTo('branches')" data-page="branches">
            <i class='bx bxs-map'></i>
            <span>สาขา</span>
        </a>
    </nav>

    <!-- ===== FAB (Mobile) ===== -->
    <button class="fab md:hidden" onclick="openVehicleModal()" id="fabBtn">
        <i class='bx bx-plus'></i>
    </button>

    <!-- ===== Toast Container ===== -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- ===== Modals Container ===== -->
    <div id="modalContainer"></div>

    <!-- ===== App JS ===== -->
    <script src="assets/js/app.js?v=4"></script>
</body>
</html>
