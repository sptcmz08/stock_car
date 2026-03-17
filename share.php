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
            --card: #161f30;
            --card2: #1c2640;
            --border: rgba(255,255,255,0.06);
            --text: #f1f5f9;
            --text2: #94a3b8;
            --text3: #64748b;
            --r: 14px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Noto Sans Thai', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }

        /* ===== LAYOUT ===== */
        .wrap {
            max-width: 560px;
            margin: 0 auto;
            padding: 16px;
        }
        .section { margin-bottom: 12px; }

        /* Desktop: 2-column layout */
        @media (min-width: 768px) {
            .wrap {
                max-width: 1000px;
                padding: 24px 32px;
            }
            .desktop-grid {
                display: grid;
                grid-template-columns: 1.2fr 1fr;
                gap: 20px;
                align-items: start;
            }
            .desktop-left { position: sticky; top: 24px; }
            .carousel-main { aspect-ratio: 16/10; }
        }

        /* ===== BRAND BAR ===== */
        .brand-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 16px;
            font-size: 13px;
            color: var(--text3);
        }
        .brand-bar i { color: var(--accent); font-size: 18px; }

        /* ===== IMAGE CAROUSEL (CONTAINED) ===== */
        .carousel-card {
            background: var(--card);
            border-radius: var(--r);
            overflow: hidden;
            position: relative;
        }
        .carousel-main {
            position: relative;
            width: 100%;
            aspect-ratio: 16/10;
            overflow: hidden;
            cursor: pointer;
        }
        .carousel-main img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #111827;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .carousel-main img.active { opacity: 1; }
        .carousel-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
            backdrop-filter: blur(12px);
            z-index: 2;
        }
        .carousel-count {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(6px);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            color: rgba(255,255,255,0.8);
            z-index: 2;
        }
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(4px);
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
            transition: background 0.2s;
        }
        .carousel-nav:active { background: rgba(0,0,0,0.7); }
        .carousel-nav.prev { left: 8px; }
        .carousel-nav.next { right: 8px; }
        /* Thumbnails strip */
        .carousel-strip {
            display: flex;
            gap: 3px;
            padding: 3px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .carousel-strip::-webkit-scrollbar { display: none; }
        .carousel-strip .strip-thumb {
            width: 56px;
            height: 56px;
            flex-shrink: 0;
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            opacity: 0.4;
            transition: opacity 0.2s;
            border: 2px solid transparent;
        }
        .carousel-strip .strip-thumb.active {
            opacity: 1;
            border-color: var(--accent);
        }
        .carousel-strip .strip-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ===== TITLE + PRICE CARD ===== */
        .info-main {
            background: var(--card);
            border-radius: var(--r);
            padding: 20px;
        }
        .info-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .info-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 16px;
        }
        .itag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            background: rgba(255,255,255,0.04);
            color: var(--text2);
        }
        .itag i { font-size: 13px; }
        .info-price-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }
        .info-price-label {
            font-size: 11px;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-price {
            font-size: 30px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }
        .info-branch {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        /* ===== SPECS ===== */
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1px;
            background: var(--border);
            border-radius: var(--r);
            overflow: hidden;
        }
        .spec {
            background: var(--card);
            padding: 14px;
            text-align: center;
        }
        .spec-icon { color: var(--accent); font-size: 18px; margin-bottom: 4px; }
        .spec-val { font-size: 14px; font-weight: 600; }
        .spec-lbl { font-size: 10px; color: var(--text3); text-transform: uppercase; letter-spacing: 0.5px; }

        /* ===== MORE PHOTOS ===== */
        .more-photos {
            background: var(--card);
            border-radius: var(--r);
            padding: 16px;
        }
        .more-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .pho-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            border-radius: 10px;
            overflow: hidden;
        }
        .pho-item {
            aspect-ratio: 1;
            overflow: hidden;
            cursor: pointer;
        }
        .pho-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .pho-item:active img { transform: scale(1.06); }

        /* ===== ACTIONS ===== */
        .act-row {
            display: flex;
            gap: 8px;
        }
        .act-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--text2);
            font-size: 12px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .act-btn:active { transform: scale(0.97); }
        .act-btn i { font-size: 16px; }

        /* ===== NOTES ===== */
        .note-card {
            background: var(--card);
            border-radius: var(--r);
            padding: 16px;
            font-size: 13px;
            color: var(--text2);
            line-height: 1.6;
        }
        .note-card strong {
            display: block;
            color: var(--text3);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        /* ===== FOOTER ===== */
        .s-footer {
            text-align: center;
            padding: 20px 0 32px;
            font-size: 11px;
            color: var(--text3);
        }
        .s-footer .brand {
            font-weight: 700;
            color: var(--accent);
        }

        /* ===== LIGHTBOX ===== */
        .lb { position:fixed;inset:0;background:rgba(0,0,0,0.98);z-index:200;display:flex;flex-direction:column;animation:lbF 0.2s ease; }
        @keyframes lbF { from{opacity:0}to{opacity:1} }
        .lb-top { display:flex;align-items:center;justify-content:space-between;padding:12px 16px;flex-shrink:0; }
        .lb-cnt { font-size:14px;color:rgba(255,255,255,0.5);font-weight:500; }
        .lb-btn { width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,0.08);color:white;border:none;font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center; }
        .lb-btn:active { background:rgba(255,255,255,0.2); }
        .lb-img { flex:1;display:flex;align-items:center;justify-content:center;overflow:hidden; }
        .lb-img img { max-width:100%;max-height:100%;object-fit:contain;user-select:none; }
        .lb-bot { display:flex;align-items:center;justify-content:center;padding:16px;flex-shrink:0; }
        .lb-dl { display:flex;align-items:center;gap:6px;padding:10px 24px;border-radius:10px;background:var(--accent);color:white;border:none;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer; }
        .lb-dl:active { opacity:0.8; }

        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .lb-bot { padding-bottom: calc(16px + env(safe-area-inset-bottom)); }
            .s-footer { padding-bottom: calc(32px + env(safe-area-inset-bottom)); }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <!-- Brand -->
        <div class="brand-bar">
            <i class='bx bxs-car'></i> Dara Autocar
        </div>

        <!-- Image Carousel Card -->
        <div class="desktop-grid">
        <div class="desktop-left">
        <?php if ($images): ?>
        <div class="carousel-card section" id="carouselCard">
            <div class="carousel-main" onclick="openLB(window._cIdx||0)">
                <?php foreach ($images as $i => $img): ?>
                <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" class="<?= $i===0 ? 'active' : '' ?>" alt="" <?= $i>0 ? 'loading="lazy"' : '' ?>>
                <?php endforeach; ?>
                <div class="carousel-badge" style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>"><?= $st['label'] ?></div>
                <?php if ($imgCount > 1): ?>
                <div class="carousel-count"><i class='bx bx-images'></i> <?= $imgCount ?></div>
                <?php endif; ?>
            </div>
            <?php if ($imgCount > 1): ?>
            <div class="carousel-strip" id="thumbStrip">
                <?php foreach ($images as $i => $img): ?>
                <div class="strip-thumb <?= $i===0?'active':'' ?>" onclick="goSlide(<?= $i ?>)">
                    <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="section" style="background:var(--card);border-radius:var(--r);aspect-ratio:16/10;display:flex;align-items:center;justify-content:center;">
            <i class='bx bxs-car' style="font-size:60px;color:rgba(249,115,22,0.12)"></i>
        </div>
        <?php endif; ?>

        <!-- More Photos Grid (desktop: under carousel) -->
        <?php if ($imgCount > 1): ?>
        <div class="more-photos section desktop-photos">
            <div class="more-title">รูปภาพทั้งหมด</div>
            <div class="pho-grid">
                <?php foreach ($images as $i => $img): ?>
                <div class="pho-item" onclick="openLB(<?= $i ?>)">
                    <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        </div><!-- /desktop-left -->

        <div class="desktop-right">
        <!-- Title + Price -->
        <div class="info-main section">
            <div class="info-name"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></div>
            <div class="info-tags">
                <span class="itag"><i class='bx bx-calendar'></i> <?= $vehicle['year'] ?></span>
                <?php if ($vehicle['color']): ?>
                <span class="itag"><i class='bx bx-palette'></i> <?= htmlspecialchars($vehicle['color']) ?></span>
                <?php endif; ?>
                <?php if ($vehicle['license_plate']): ?>
                <span class="itag"><i class='bx bx-id-card'></i> <?= htmlspecialchars($vehicle['license_plate']) ?></span>
                <?php endif; ?>
                <?php if ($vehicle['mileage'] > 0): ?>
                <span class="itag"><i class='bx bx-tachometer'></i> <?= number_format($vehicle['mileage']) ?> กม.</span>
                <?php endif; ?>
            </div>
            <div class="info-price-row">
                <div>
                    <div class="info-price-label">ราคา</div>
                    <div class="info-price">฿<?= number_format($vehicle['selling_price']) ?></div>
                </div>
                <?php if ($vehicle['branch_name']): ?>
                <span class="info-branch" style="background:<?= $vehicle['branch_color'] ?>18;color:<?= $vehicle['branch_color'] ?>">
                    <i class='bx bxs-map-pin'></i> <?= htmlspecialchars($vehicle['branch_name']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($imgCount > 0): ?>
        <div class="act-row section">
            <button class="act-btn" onclick="dlAll()" id="dlAllBtn">
                <i class='bx bx-download'></i> ดาวน์โหลดรูปทั้งหมด (<?= $imgCount ?>)
            </button>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($vehicle['notes']): ?>
        <div class="note-card section">
            <strong>หมายเหตุ</strong>
            <?= nl2br(htmlspecialchars($vehicle['notes'])) ?>
        </div>
        <?php endif; ?>
        </div><!-- /desktop-right -->
        </div><!-- /desktop-grid -->

        <!-- Footer -->
        <div class="s-footer">
            <span class="brand">Dara Autocar</span>
        </div>
    </div>

    <script>
    const imgs = <?= json_encode(array_map(fn($img) => $img['filename'], $images)) ?>;
    window._cIdx = 0;

    <?php if ($imgCount > 1): ?>
    // Auto-slide
    let autoTimer = setInterval(() => goSlide((window._cIdx + 1) % imgs.length), 4000);
    <?php endif; ?>

    function goSlide(i) {
        const slides = document.querySelectorAll('.carousel-main img');
        const thumbs = document.querySelectorAll('.strip-thumb');
        slides.forEach(s => s.classList.remove('active'));
        thumbs.forEach(t => t.classList.remove('active'));
        if (slides[i]) slides[i].classList.add('active');
        if (thumbs[i]) {
            thumbs[i].classList.add('active');
            thumbs[i].scrollIntoView({ behavior:'smooth', block:'nearest', inline:'center' });
        }
        window._cIdx = i;
        // Reset auto-slide timer
        <?php if ($imgCount > 1): ?>
        clearInterval(autoTimer);
        autoTimer = setInterval(() => goSlide((window._cIdx + 1) % imgs.length), 4000);
        <?php endif; ?>
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

    async function dlAll() {
        const btn = document.getElementById('dlAllBtn');
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> กำลังดาวน์โหลด...';
        btn.disabled = true;
        for (let i = 0; i < imgs.length; i++) {
            await dlPic(i);
            if (i < imgs.length - 1) await new Promise(r => setTimeout(r, 600));
        }
        btn.innerHTML = '<i class="bx bx-check"></i> ดาวน์โหลดเสร็จแล้ว!';
        setTimeout(() => {
            btn.innerHTML = '<i class="bx bx-download"></i> ดาวน์โหลดรูปทั้งหมด (' + imgs.length + ')';
            btn.disabled = false;
        }, 2000);
    }
    </script>
</body>
</html>
