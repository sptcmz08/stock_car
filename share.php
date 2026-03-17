<?php
/**
 * Public Share Page — Premium Customer-Facing Vehicle Listing
 * Dara Autocar
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
    'available' => ['label' => 'พร้อมขาย', 'color' => '#22c55e', 'bg' => '#22c55e22'],
    'reserved'  => ['label' => 'จอง', 'color' => '#f59e0b', 'bg' => '#f59e0b22'],
    'sold'      => ['label' => 'ขายแล้ว', 'color' => '#ef4444', 'bg' => '#ef444422'],
    'maintenance' => ['label' => 'ซ่อมบำรุง', 'color' => '#6366f1', 'bg' => '#6366f122'],
];
$st = $statusMap[$vehicle['status']] ?? $statusMap['available'];
$imgCount = count($images);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1">
    <title><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> | Dara Autocar</title>
    <meta name="description" content="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' ปี ' . $vehicle['year'] . ' ราคา ฿' . number_format($vehicle['selling_price'])) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> <?= $vehicle['year'] ?> | Dara Autocar">
    <meta property="og:description" content="ราคา ฿<?= number_format($vehicle['selling_price']) ?> • <?= $vehicle['color'] ?>">
    <?php if ($images): ?>
    <meta property="og:image" content="uploads/<?= htmlspecialchars($images[0]['filename']) ?>">
    <?php endif; ?>
    
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --accent: #f97316;
            --accent2: #fb923c;
            --bg: #0c1322;
            --card: rgba(30,41,59,0.7);
            --border: rgba(255,255,255,0.06);
            --text: #f1f5f9;
            --text2: #94a3b8;
            --text3: #64748b;
            --radius: 16px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Noto Sans Thai', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }

        /* ===== HERO GALLERY ===== */
        .hero {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            max-height: 480px;
            overflow: hidden;
            background: #1e293b;
        }
        .hero img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .hero img.active { opacity: 1; }
        .hero-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(transparent, rgba(12,19,34,0.9));
            pointer-events: none;
        }
        .hero-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            backdrop-filter: blur(12px);
            z-index: 2;
        }
        .hero-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 2;
        }
        .hero-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            cursor: pointer;
            transition: all 0.3s;
        }
        .hero-dot.active {
            background: var(--accent);
            width: 24px;
            border-radius: 4px;
        }
        .hero-count {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(8px);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            color: rgba(255,255,255,0.8);
            z-index: 2;
        }
        .hero-no-img {
            width: 100%;
            aspect-ratio: 4/3;
            max-height: 480px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ===== CONTENT ===== */
        .wrap {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 16px 32px;
        }

        /* Price Bar */
        .price-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
        }
        .price-value {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .price-label {
            font-size: 11px;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        /* Title Section */
        .title-section {
            padding: 20px 0;
        }
        .title-section h1 {
            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 8px;
        }
        .title-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            background: rgba(255,255,255,0.05);
            color: var(--text2);
        }
        .tag i { font-size: 14px; }
        .tag.branch {
            font-weight: 600;
        }

        /* Specs Grid */
        .specs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1px;
            background: var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin: 16px 0;
        }
        .spec-item {
            background: var(--card);
            backdrop-filter: blur(10px);
            padding: 16px;
            text-align: center;
        }
        .spec-item .spec-icon {
            font-size: 20px;
            color: var(--accent);
            margin-bottom: 4px;
        }
        .spec-item .spec-val {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .spec-item .spec-label {
            font-size: 11px;
            color: var(--text3);
        }

        /* Photo Thumbnails */
        .thumbs-section { margin: 16px 0; }
        .thumbs-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .thumbs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            border-radius: var(--radius);
            overflow: hidden;
        }
        .thumb {
            aspect-ratio: 1;
            overflow: hidden;
            cursor: pointer;
        }
        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .thumb:active img { transform: scale(1.08); }
        @media (min-width: 480px) { .thumb:hover img { transform: scale(1.08); } }

        /* Actions */
        .actions-bar {
            display: flex;
            gap: 8px;
            margin: 16px 0;
        }
        .act-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--card);
            backdrop-filter: blur(10px);
            color: var(--text);
            font-size: 13px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .act-btn:active { transform: scale(0.97); }
        .act-btn i { font-size: 17px; }

        /* Notes */
        .notes-card {
            background: var(--card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            margin: 16px 0;
            font-size: 14px;
            color: var(--text2);
            line-height: 1.6;
        }
        .notes-card strong {
            display: block;
            color: var(--text);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        /* Footer */
        .share-footer {
            text-align: center;
            padding: 24px 0 40px;
            font-size: 11px;
            color: var(--text3);
            border-top: 1px solid var(--border);
            margin-top: 16px;
        }
        .share-footer .brand {
            font-weight: 700;
            color: var(--accent);
        }

        /* ===== LIGHTBOX ===== */
        .lb {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.98);
            z-index: 200;
            display: flex;
            flex-direction: column;
            animation: lbFade 0.2s ease;
        }
        @keyframes lbFade { from { opacity:0 } to { opacity:1 } }
        .lb-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            flex-shrink: 0;
        }
        .lb-cnt {
            font-size: 14px;
            color: rgba(255,255,255,0.5);
            font-weight: 500;
        }
        .lb-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            color: white;
            border: none;
            font-size: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lb-btn:active { background: rgba(255,255,255,0.2); }
        .lb-img {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .lb-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            user-select: none;
        }
        .lb-bot {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            flex-shrink: 0;
        }
        .lb-dl {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 24px;
            border-radius: 10px;
            background: var(--accent);
            color: white;
            border: none;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        .lb-dl:active { opacity: 0.8; }
        
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .lb-bot { padding-bottom: calc(16px + env(safe-area-inset-bottom)); }
            .share-footer { padding-bottom: calc(40px + env(safe-area-inset-bottom)); }
        }
    </style>
</head>
<body>

    <!-- Hero Image Gallery -->
    <?php if ($images): ?>
    <div class="hero" onclick="openLB(window._heroIdx || 0)">
        <?php foreach ($images as $i => $img): ?>
        <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" class="<?= $i === 0 ? 'active' : '' ?>" alt="" <?= $i > 0 ? 'loading="lazy"' : '' ?>>
        <?php endforeach; ?>
        <div class="hero-overlay"></div>
        <div class="hero-badge" style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>"><?= $st['label'] ?></div>
        <?php if ($imgCount > 1): ?>
        <div class="hero-dots">
            <?php for ($i = 0; $i < min($imgCount, 8); $i++): ?>
            <div class="hero-dot <?= $i === 0 ? 'active' : '' ?>" onclick="event.stopPropagation();goHero(<?= $i ?>)"></div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <div class="hero-count"><i class='bx bx-images'></i> <?= $imgCount ?></div>
    </div>
    <?php else: ?>
    <div class="hero-no-img">
        <i class='bx bxs-car' style="font-size:80px;color:rgba(249,115,22,0.12)"></i>
    </div>
    <?php endif; ?>

    <div class="wrap">
        <!-- Price -->
        <div class="price-bar">
            <div>
                <div class="price-label">ราคา</div>
                <div class="price-value">฿<?= number_format($vehicle['selling_price']) ?></div>
            </div>
            <?php if ($vehicle['branch_name']): ?>
            <span class="tag branch" style="background:<?= $vehicle['branch_color'] ?>18;color:<?= $vehicle['branch_color'] ?>">
                <i class='bx bxs-map-pin'></i> <?= htmlspecialchars($vehicle['branch_name']) ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Title -->
        <div class="title-section">
            <h1><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h1>
            <div class="title-tags">
                <span class="tag"><i class='bx bx-calendar'></i> <?= $vehicle['year'] ?></span>
                <?php if ($vehicle['color']): ?>
                <span class="tag"><i class='bx bx-palette'></i> <?= htmlspecialchars($vehicle['color']) ?></span>
                <?php endif; ?>
                <?php if ($vehicle['license_plate']): ?>
                <span class="tag"><i class='bx bx-id-card'></i> <?= htmlspecialchars($vehicle['license_plate']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Specs Grid -->
        <div class="specs">
            <div class="spec-item">
                <div class="spec-icon"><i class='bx bx-calendar'></i></div>
                <div class="spec-val"><?= $vehicle['year'] ?></div>
                <div class="spec-label">ปี</div>
            </div>
            <div class="spec-item">
                <div class="spec-icon"><i class='bx bx-tachometer'></i></div>
                <div class="spec-val"><?= number_format($vehicle['mileage']) ?></div>
                <div class="spec-label">กิโลเมตร</div>
            </div>
            <?php if ($vehicle['color']): ?>
            <div class="spec-item">
                <div class="spec-icon"><i class='bx bx-palette'></i></div>
                <div class="spec-val"><?= htmlspecialchars($vehicle['color']) ?></div>
                <div class="spec-label">สี</div>
            </div>
            <?php endif; ?>
            <?php if ($vehicle['license_plate']): ?>
            <div class="spec-item">
                <div class="spec-icon"><i class='bx bx-id-card'></i></div>
                <div class="spec-val"><?= htmlspecialchars($vehicle['license_plate']) ?></div>
                <div class="spec-label">ทะเบียน</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Photo Thumbnails -->
        <?php if ($imgCount > 1): ?>
        <div class="thumbs-section">
            <div class="thumbs-title">รูปภาพ <?= $imgCount ?> รูป</div>
            <div class="thumbs-grid">
                <?php foreach ($images as $i => $img): ?>
                <div class="thumb" onclick="openLB(<?= $i ?>)">
                    <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if ($imgCount > 0): ?>
        <div class="actions-bar">
            <a href="api/download_album.php?id=<?= $vehicle['id'] ?>&token=<?= $token ?>" class="act-btn">
                <i class='bx bx-download'></i> ดาวน์โหลดรูป
            </a>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($vehicle['notes']): ?>
        <div class="notes-card">
            <strong>หมายเหตุ</strong>
            <?= nl2br(htmlspecialchars($vehicle['notes'])) ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="share-footer">
            <span class="brand">Dara Autocar</span>
        </div>
    </div>

    <!-- Lightbox & Scripts -->
    <script>
    const imgs = <?= json_encode(array_map(function($img) { return $img['filename']; }, $images)) ?>;
    window._heroIdx = 0;

    // Hero auto-slide
    <?php if ($imgCount > 1): ?>
    (function() {
        const slides = document.querySelectorAll('.hero img');
        const dots = document.querySelectorAll('.hero-dot');
        let cur = 0;
        setInterval(() => {
            slides[cur].classList.remove('active');
            if (dots[cur]) dots[cur].classList.remove('active');
            cur = (cur + 1) % slides.length;
            slides[cur].classList.add('active');
            if (dots[cur]) dots[cur].classList.add('active');
            window._heroIdx = cur;
        }, 4000);
    })();
    <?php endif; ?>

    function goHero(i) {
        const slides = document.querySelectorAll('.hero img');
        const dots = document.querySelectorAll('.hero-dot');
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        slides[i].classList.add('active');
        if (dots[i]) dots[i].classList.add('active');
        window._heroIdx = i;
    }

    function openLB(i) {
        let cur = i;
        const el = document.createElement('div');
        el.className = 'lb';
        render();
        document.body.appendChild(el);
        document.body.style.overflow = 'hidden';

        function render() {
            el.innerHTML = `
                <div class="lb-top">
                    <button class="lb-btn" onclick="closeLB(this)"><i class="bx bx-x"></i></button>
                    <span class="lb-cnt">${cur+1} / ${imgs.length}</span>
                    <div style="width:44px"></div>
                </div>
                <div class="lb-img"><img src="uploads/${imgs[cur]}" alt=""></div>
                <div class="lb-bot">
                    <button class="lb-dl" onclick="dlPic(${cur})"><i class="bx bx-download"></i> บันทึกรูปนี้</button>
                </div>`;
        }

        let sx=0;
        el.addEventListener('touchstart', e => { sx=e.touches[0].clientX; }, {passive:true});
        el.addEventListener('touchend', e => {
            const dx = e.changedTouches[0].clientX - sx;
            if (Math.abs(dx) > 50) { cur = dx>0 ? Math.max(0,cur-1) : Math.min(imgs.length-1,cur+1); render(); }
        });
        el._kh = e => {
            if (e.key==='Escape') closeLB(el.querySelector('.lb-btn'));
            if (e.key==='ArrowLeft') { cur=Math.max(0,cur-1); render(); }
            if (e.key==='ArrowRight') { cur=Math.min(imgs.length-1,cur+1); render(); }
        };
        document.addEventListener('keydown', el._kh);
    }

    function closeLB(btn) {
        const el = btn.closest('.lb');
        if (el._kh) document.removeEventListener('keydown', el._kh);
        el.remove();
        document.body.style.overflow = '';
    }

    async function dlPic(i) {
        try {
            const r = await fetch('uploads/'+imgs[i]);
            const b = await r.blob();
            const u = URL.createObjectURL(b);
            const a = document.createElement('a');
            a.href=u; a.download=imgs[i]; document.body.appendChild(a); a.click();
            document.body.removeChild(a); URL.revokeObjectURL(u);
        } catch(e) { alert('ดาวน์โหลดไม่สำเร็จ'); }
    }
    </script>
</body>
</html>
