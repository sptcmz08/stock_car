<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'] ?? 'admin';
$displayName = $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'User';
$branchName = $_SESSION['branch_name'] ?? null;
$isAdmin = ($role === 'admin');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dara Autocar - ระบบสต็อกรถยนต์</title>
    <meta name="description" content="ระบบจัดการสต็อกรถยนต์ สำหรับเต็นท์รถ">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=20">
</head>
<body>
    <!-- Pass user info to JS -->
    <script>
        window.APP_USER = {
            id: <?= (int)$_SESSION['user_id'] ?>,
            username: "<?= htmlspecialchars($_SESSION['username'] ?? '') ?>",
            role: "<?= htmlspecialchars($role) ?>",
            branch_id: <?= $_SESSION['branch_id'] ? (int)$_SESSION['branch_id'] : 'null' ?>,
            display_name: "<?= htmlspecialchars($displayName) ?>",
            branch_name: <?= $branchName ? '"' . htmlspecialchars($branchName) . '"' : 'null' ?>,
            is_admin: <?= $isAdmin ? 'true' : 'false' ?>
        };
    </script>

    <!-- ===== Sidebar ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class='bx bxs-car'></i>
                </div>
                <div class="sidebar-logo-text">
                    <h1 class="gradient-text">Dara Autocar</h1>
                    <p>ระบบสต็อกรถยนต์</p>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-nav-group">
                <span class="sidebar-nav-label">เมนูหลัก</span>
                <a class="sidebar-link active" onclick="navigateTo('dashboard')" data-page="dashboard">
                    <i class='bx bxs-dashboard'></i>
                    <span>แดชบอร์ด</span>
                </a>
                <a class="sidebar-link" onclick="navigateTo('vehicles')" data-page="vehicles">
                    <i class='bx bxs-car'></i>
                    <span>รถยนต์</span>
                </a>
            </div>

            <?php if ($isAdmin): ?>
            <div class="sidebar-nav-group">
                <span class="sidebar-nav-label">จัดการระบบ</span>
                <a class="sidebar-link" onclick="navigateTo('branches')" data-page="branches">
                    <i class='bx bxs-map'></i>
                    <span>จัดการสาขา</span>
                </a>
                <a class="sidebar-link" onclick="navigateTo('users')" data-page="users">
                    <i class='bx bxs-user-account'></i>
                    <span>จัดการผู้ใช้</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <i class='bx bxs-user'></i>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($displayName) ?></div>
                    <div class="sidebar-user-role">
                        <?php if ($isAdmin): ?>
                            <span class="role-badge role-admin">Admin</span>
                        <?php else: ?>
                            <span class="role-badge role-branch"><?= htmlspecialchars($branchName ?? 'สาขา') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <button onclick="logout()" class="sidebar-logout-btn" title="ออกจากระบบ">
                    <i class='bx bx-log-out'></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- ===== Sidebar Overlay (Mobile) ===== -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- ===== Top Bar (Mobile) ===== -->
    <header class="topbar" id="topbar">
        <button class="topbar-menu-btn" onclick="toggleSidebar()">
            <i class='bx bx-menu'></i>
        </button>
        <div class="topbar-title">
            <h1 class="gradient-text">Dara Autocar</h1>
        </div>
        <button onclick="openVehicleModal()" class="topbar-action-btn" title="เพิ่มรถ">
            <i class='bx bx-plus'></i>
        </button>
    </header>

    <!-- ===== Main Content ===== -->
    <main class="main-area" id="mainArea">

        <!-- Dashboard Section -->
        <section id="page-dashboard" class="page-section active">
            <div class="page-header">
                <div>
                    <h2 class="page-title">แดชบอร์ด</h2>
                    <p class="page-subtitle" id="dashboardSubtitle">ภาพรวมสต็อกรถยนต์<?= $branchName ? ' — ' . htmlspecialchars($branchName) : '' ?></p>
                </div>
                <button onclick="openVehicleModal()" class="btn-primary !py-2 !px-4 !text-sm !rounded-xl hidden md:inline-flex">
                    <i class='bx bx-plus'></i> เพิ่มรถ
                </button>
            </div>

            <!-- Hero Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6" id="heroStats">
                <!-- Filled by JS -->
            </div>

            <!-- Financial & Status Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                <div class="dash-card" id="financialCard">
                    <!-- Filled by JS -->
                </div>
                <div class="dash-card" id="statusChart">
                    <!-- Filled by JS -->
                </div>
            </div>

            <!-- Branch Breakdown (Admin only shows full) -->
            <div class="dash-card mb-6" id="branchCard">
                <!-- Filled by JS -->
            </div>

            <!-- Profit Summary -->
            <div class="dash-card mb-6" id="profitCard">
                <!-- Filled by JS -->
            </div>

            <!-- Recent Vehicles -->
            <div class="dash-card" id="recentCard">
                <!-- Filled by JS -->
            </div>
        </section>

        <!-- Vehicles Section -->
        <section id="page-vehicles" class="page-section">
            <div class="page-header">
                <div>
                    <h2 class="page-title">รถยนต์</h2>
                    <p class="page-subtitle" id="vehicleCount">กำลังโหลด...</p>
                </div>
                <button onclick="openVehicleModal()" class="btn-primary !py-2 !px-4 !text-sm !rounded-xl hidden md:inline-flex">
                    <i class='bx bx-plus'></i> เพิ่มรถ
                </button>
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

        <!-- Branches Section (Admin Only) -->
        <?php if ($isAdmin): ?>
        <section id="page-branches" class="page-section">
            <div class="page-header">
                <div>
                    <h2 class="page-title">จัดการสาขา</h2>
                    <p class="page-subtitle">เพิ่ม แก้ไข หรือลบสาขา</p>
                </div>
                <button onclick="openBranchModal()" class="btn-primary !py-2 !px-4 !text-sm !rounded-xl">
                    <i class='bx bx-plus'></i> เพิ่มสาขา
                </button>
            </div>
            <div id="branchList">
                <!-- Filled by JS -->
            </div>
        </section>

        <!-- Users Section (Admin Only) -->
        <section id="page-users" class="page-section">
            <div class="page-header">
                <div>
                    <h2 class="page-title">จัดการผู้ใช้</h2>
                    <p class="page-subtitle">เพิ่ม แก้ไข หรือลบผู้ใช้สาขา</p>
                </div>
                <button onclick="openUserModal()" class="btn-primary !py-2 !px-4 !text-sm !rounded-xl">
                    <i class='bx bx-plus'></i> เพิ่มผู้ใช้
                </button>
            </div>
            <div id="userList">
                <!-- Filled by JS -->
            </div>
        </section>
        <?php endif; ?>
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
        <?php if ($isAdmin): ?>
        <a class="nav-item" onclick="navigateTo('branches')" data-page="branches">
            <i class='bx bxs-map'></i>
            <span>สาขา</span>
        </a>
        <a class="nav-item" onclick="navigateTo('users')" data-page="users">
            <i class='bx bxs-user-account'></i>
            <span>ผู้ใช้</span>
        </a>
        <?php endif; ?>
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
    <script src="assets/js/app.js?v=20"></script>
</body>
</html>
