<?php
/**
 * Public Share Page - View vehicle album without login
 * Premium mobile-friendly design
 */
require_once __DIR__ . '/config.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404</title></head><body style="background:#0f172a;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><div style="text-align:center"><h1 style="font-size:48px;margin:0">404</h1><p style="color:#94a3b8">ไม่พบอัลบั้ม</p></div></body></html>';
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT v.*, b.name as branch_name, b.color as branch_color FROM vehicles v LEFT JOIN branches b ON v.branch_id = b.id WHERE v.share_token = ? LIMIT 1");
$stmt->execute([$token]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404</title></head><body style="background:#0f172a;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><div style="text-align:center"><h1 style="font-size:48px;margin:0">404</h1><p style="color:#94a3b8">ไม่พบอัลบั้ม หรือลิงก์หมดอายุ</p></div></body></html>';
    exit;
}

$stmt = $db->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY sort_order");
$stmt->execute([$vehicle['id']]);
$images = $stmt->fetchAll();

$statusMap = [
    'available' => ['label' => 'พร้อมขาย', 'color' => '#22c55e', 'icon' => '🟢'],
    'reserved' => ['label' => 'จอง', 'color' => '#f59e0b', 'icon' => '🟡'],
    'sold' => ['label' => 'ขายแล้ว', 'color' => '#ef4444', 'icon' => '🔴'],
    'maintenance' => ['label' => 'ซ่อม', 'color' => '#6366f1', 'icon' => '🔵'],
];
$st = $statusMap[$vehicle['status']] ?? $statusMap['available'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1">
    <title><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> - อัลบั้มรถ</title>
    <meta name="description" content="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' ปี ' . $vehicle['year'] . ' ราคา ฿' . number_format($vehicle['selling_price'])) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> <?= $vehicle['year'] ?>">
    <meta property="og:description" content="ราคา ฿<?= number_format($vehicle['selling_price']) ?>">
    <?php if ($images): ?>
    <meta property="og:image" content="uploads/<?= htmlspecialchars($images[0]['filename']) ?>">
    <?php endif; ?>
    
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --accent: #f97316;
            --bg: #0f172a;
            --card: #1e293b;
            --border: rgba(255,255,255,0.08);
            --text: #f1f5f9;
            --text2: #94a3b8;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Noto Sans Thai', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Hero / Cover */
        .hero {
            position: relative;
            width: 100%;
            height: 320px;
            overflow: hidden;
        }
        .hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(transparent 40%, rgba(15,23,42,0.95) 100%);
        }
        .hero-no-img {
            width: 100%;
            height: 320px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }
        .hero-count {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(10px);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Content */
        .content {
            max-width: 640px;
            margin: 0 auto;
            padding: 0 16px;
            position: relative;
            margin-top: -60px;
            z-index: 2;
        }
        
        /* Vehicle Title Card */
        .title-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 12px;
        }
        .title-card h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .title-card .sub {
            color: var(--text2);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .title-card .price {
            font-size: 26px;
            font-weight: 700;
            color: var(--accent);
            margin-top: 12px;
        }
        .title-card .branch-tag {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        
        /* Action Buttons */
        .actions {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 12px;
            border-radius: 12px;
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
            font-size: 13px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .action-btn:active { transform: scale(0.97); }
        .action-btn i { font-size: 18px; }
        .action-btn.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        .action-btn.primary:hover { opacity: 0.9; }
        
        /* Photo Grid */
        .photo-section {
            margin-bottom: 12px;
        }
        .photo-section h2 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text2);
            padding: 8px 4px;
        }
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3px;
            border-radius: 12px;
            overflow: hidden;
        }
        .photo-item {
            position: relative;
            cursor: pointer;
            overflow: hidden;
        }
        .photo-item img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            display: block;
            transition: transform 0.3s;
        }
        .photo-item:active img { transform: scale(1.05); }
        @media (min-width: 480px) {
            .photo-item:hover img { transform: scale(1.05); }
        }
        
        /* Info Card */
        .info-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 12px;
        }
        .info-card h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text2);
            margin-bottom: 12px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: var(--text2); }
        .info-row .value { font-weight: 500; text-align: right; }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 24px 16px 40px;
            color: var(--text2);
            font-size: 12px;
        }
        
        /* Lightbox */
        .lb {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.98);
            z-index: 200;
            display: flex;
            flex-direction: column;
            animation: lbIn 0.2s ease;
        }
        @keyframes lbIn { from { opacity:0 } to { opacity:1 } }
        .lb-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            flex-shrink: 0;
        }
        .lb-counter {
            font-size: 14px;
            color: rgba(255,255,255,0.6);
            font-weight: 500;
        }
        .lb-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: white;
            border: none;
            font-size: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            -webkit-tap-highlight-color: transparent;
        }
        .lb-btn:active { background: rgba(255,255,255,0.25); }
        .lb-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .lb-body img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            user-select: none;
            -webkit-user-drag: none;
        }
        .lb-nav {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 25%;
            background: none;
            border: none;
            cursor: pointer;
            color: transparent;
        }
        .lb-nav.prev { left: 0; }
        .lb-nav.next { right: 0; }
        .lb-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            gap: 12px;
            flex-shrink: 0;
        }
        .lb-save {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 10px;
            background: var(--accent);
            color: white;
            border: none;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        .lb-save:active { opacity: 0.8; }
        
        /* Safe area for mobile */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .lb-footer { padding-bottom: calc(16px + env(safe-area-inset-bottom)); }
            .footer { padding-bottom: calc(40px + env(safe-area-inset-bottom)); }
        }
    </style>
</head>
<body>
    <!-- Hero Cover -->
    <?php if ($images): ?>
    <div class="hero" onclick="openLB(0)">
        <img src="uploads/<?= htmlspecialchars($images[0]['filename']) ?>" alt="">
        <div class="hero-overlay"></div>
        <div class="hero-badge" style="background:<?= $st['color'] ?>22; color:<?= $st['color'] ?>">
            <?= $st['icon'] ?> <?= $st['label'] ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="hero-count"><i class='bx bx-images'></i> <?= count($images) ?></div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="hero-no-img">
        <i class='bx bxs-car' style="font-size:80px;color:rgba(249,115,22,0.15)"></i>
    </div>
    <?php endif; ?>

    <div class="content">
        <!-- Title Card -->
        <div class="title-card">
            <h1><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h1>
            <div class="sub">
                <span><?= $vehicle['year'] ?></span>
                <?php if ($vehicle['color']): ?>
                <span>•</span><span><?= htmlspecialchars($vehicle['color']) ?></span>
                <?php endif; ?>
                <?php if ($vehicle['license_plate']): ?>
                <span>•</span><span><?= htmlspecialchars($vehicle['license_plate']) ?></span>
                <?php endif; ?>
            </div>
            <div class="price">฿<?= number_format($vehicle['selling_price']) ?></div>
            <?php if ($vehicle['branch_name']): ?>
            <div style="margin-top:8px">
                <span class="branch-tag" style="background:<?= $vehicle['branch_color'] ?>20;color:<?= $vehicle['branch_color'] ?>">
                    <i class='bx bxs-map-pin'></i> <?= htmlspecialchars($vehicle['branch_name']) ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <?php if ($images): ?>
        <div class="actions">
            <a href="api/download_album.php?id=<?= $vehicle['id'] ?>&token=<?= $token ?>" class="action-btn primary">
                <i class='bx bx-download'></i> ดาวน์โหลดทั้งหมด (<?= count($images) ?>)
            </a>
        </div>
        <?php endif; ?>

        <!-- Photo Grid -->
        <?php if (count($images) > 1): ?>
        <div class="photo-section">
            <h2><i class='bx bx-images'></i> รูปภาพทั้งหมด</h2>
            <div class="photo-grid">
                <?php foreach ($images as $i => $img): ?>
                <div class="photo-item" onclick="openLB(<?= $i ?>)">
                    <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Info Card -->
        <div class="info-card">
            <h3><i class='bx bx-car'></i> ข้อมูลรถ</h3>
            <?php if ($vehicle['license_plate']): ?>
            <div class="info-row">
                <span class="label">ทะเบียน</span>
                <span class="value"><?= htmlspecialchars($vehicle['license_plate']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label">ปี</span>
                <span class="value"><?= $vehicle['year'] ?></span>
            </div>
            <?php if ($vehicle['color']): ?>
            <div class="info-row">
                <span class="label">สี</span>
                <span class="value"><?= htmlspecialchars($vehicle['color']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label">เลขไมล์</span>
                <span class="value"><?= number_format($vehicle['mileage']) ?> กม.</span>
            </div>
            <?php if ($vehicle['cost_price'] > 0): ?>
            <div class="info-row">
                <span class="label">ราคาทุน</span>
                <span class="value">฿<?= number_format($vehicle['cost_price']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label">ราคาขาย</span>
                <span class="value" style="color:var(--accent);font-weight:700">฿<?= number_format($vehicle['selling_price']) ?></span>
            </div>
            <?php if ($vehicle['notes']): ?>
            <div class="info-row">
                <span class="label">หมายเหตุ</span>
                <span class="value" style="color:var(--text2)"><?= htmlspecialchars($vehicle['notes']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            แชร์โดย StockCar
        </div>
    </div>

    <!-- Lightbox -->
    <script>
    const imgs = <?= json_encode(array_map(function($img) { return $img['filename']; }, $images)) ?>;
    
    function openLB(i) {
        let cur = i;
        const el = document.createElement('div');
        el.className = 'lb';
        render();
        document.body.appendChild(el);
        document.body.style.overflow = 'hidden';
        
        function render() {
            el.innerHTML = `
                <div class="lb-header">
                    <button class="lb-btn" onclick="closeLB(this)"><i class="bx bx-x"></i></button>
                    <span class="lb-counter">${cur + 1} / ${imgs.length}</span>
                    <div style="width:44px"></div>
                </div>
                <div class="lb-body">
                    ${cur > 0 ? '<button class="lb-nav prev" onclick="navLB(-1,this)"></button>' : ''}
                    <img src="uploads/${imgs[cur]}" alt="">
                    ${cur < imgs.length - 1 ? '<button class="lb-nav next" onclick="navLB(1,this)"></button>' : ''}
                </div>
                <div class="lb-footer">
                    <button class="lb-save" onclick="savePic(${cur})"><i class="bx bx-download"></i> บันทึกรูปนี้</button>
                </div>
            `;
        }

        // Swipe support
        let sx=0, sy=0;
        el.addEventListener('touchstart', e => { sx=e.touches[0].clientX; sy=e.touches[0].clientY; }, {passive:true});
        el.addEventListener('touchend', e => {
            const dx = e.changedTouches[0].clientX - sx;
            const dy = e.changedTouches[0].clientY - sy;
            if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 50) {
                cur = dx > 0 ? Math.max(0, cur-1) : Math.min(imgs.length-1, cur+1);
                render();
            }
        });
        
        // Keyboard
        el._keyHandler = e => {
            if (e.key === 'Escape') closeLB(el.querySelector('.lb-btn'));
            if (e.key === 'ArrowLeft') { cur = Math.max(0,cur-1); render(); }
            if (e.key === 'ArrowRight') { cur = Math.min(imgs.length-1,cur+1); render(); }
        };
        document.addEventListener('keydown', el._keyHandler);
        
        window._curLB = { el, cur, render, setCur: c => { cur=c; render(); } };
    }
    
    function navLB(dir, btn) {
        const lb = window._curLB;
        if (!lb) return;
        lb.cur = Math.max(0, Math.min(imgs.length-1, lb.cur + dir));
        lb.setCur(lb.cur);
    }

    function closeLB(btn) {
        const el = btn.closest('.lb');
        if (el._keyHandler) document.removeEventListener('keydown', el._keyHandler);
        el.remove();
        document.body.style.overflow = '';
    }

    async function savePic(i) {
        try {
            const res = await fetch('uploads/' + imgs[i]);
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = imgs[i];
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch(e) { alert('ดาวน์โหลดไม่สำเร็จ'); }
    }
    </script>
</body>
</html>
