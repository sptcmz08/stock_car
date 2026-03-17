<?php
/**
 * Public Share Page - View vehicle album without login
 */
require_once __DIR__ . '/config.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    http_response_code(404);
    echo '<h1>ไม่พบอัลบั้ม</h1>';
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT v.*, b.name as branch_name, b.color as branch_color FROM vehicles v LEFT JOIN branches b ON v.branch_id = b.id WHERE v.share_token = ? LIMIT 1");
$stmt->execute([$token]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    http_response_code(404);
    echo '<h1>ไม่พบอัลบั้ม หรือลิงก์หมดอายุ</h1>';
    exit;
}

// Get images
$stmt = $db->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY sort_order");
$stmt->execute([$vehicle['id']]);
$images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> - อัลบั้มรถ</title>
    <meta name="description" content="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' ปี ' . $vehicle['year']) ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=5">
    
    <style>
        .share-header {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-glass);
            padding: 16px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .share-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3px;
        }
        .share-grid-item {
            position: relative;
            cursor: pointer;
        }
        .share-grid-item img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            display: block;
            transition: opacity 0.2s;
        }
        .share-grid-item:hover img { opacity: 0.85; }
        .share-info { padding: 20px 16px; }
        .share-lightbox {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.97);
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        .share-lb-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            flex-shrink: 0;
        }
        .share-lb-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .share-lb-body img {
            max-width: 95vw;
            max-height: 80vh;
            object-fit: contain;
        }
        .share-lb-btn {
            width: 40px;
            height: 40px;
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
        }
        .share-lb-btn:hover { background: rgba(255,255,255,0.2); }
        .share-dl-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            background: var(--accent);
            color: white;
            border: none;
            font-size: 14px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .share-dl-btn:hover { opacity: 0.85; }
        .share-counter {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="share-header">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h1>
                    <p class="text-xs text-slate-500"><?= $vehicle['year'] ?> • <?= htmlspecialchars($vehicle['color'] ?: '-') ?> • <?= count($images) ?> รูป</p>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold text-orange-400">฿<?= number_format($vehicle['selling_price']) ?></div>
                    <?php if ($vehicle['branch_name']): ?>
                        <span class="text-xs" style="color:<?= $vehicle['branch_color'] ?>"><i class='bx bxs-map-pin'></i> <?= htmlspecialchars($vehicle['branch_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (count($images) > 0): ?>
            <div style="margin-top:12px;">
                <button class="share-dl-btn" onclick="downloadAll()">
                    <i class='bx bx-download'></i> ดาวน์โหลดทั้งหมด (<?= count($images) ?> รูป)
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="max-w-2xl mx-auto">
        <?php if ($images): ?>
            <div class="share-grid">
                <?php foreach ($images as $i => $img): ?>
                    <div class="share-grid-item" onclick="openLB(<?= $i ?>)">
                        <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-20 text-slate-500">
                <i class='bx bxs-car text-6xl text-orange-400/20'></i>
                <p class="mt-2">ไม่มีรูปภาพ</p>
            </div>
        <?php endif; ?>

        <div class="share-info">
            <div class="glass-card-static p-4 mb-4">
                <h3 class="font-bold text-sm mb-3 text-slate-400">ข้อมูลรถ</h3>
                <div class="info-row"><span class="info-label">ทะเบียน</span><span class="info-value"><?= htmlspecialchars($vehicle['license_plate'] ?: '-') ?></span></div>
                <div class="info-row"><span class="info-label">เลขไมล์</span><span class="info-value"><?= number_format($vehicle['mileage']) ?> กม.</span></div>
                <?php if ($vehicle['notes']): ?>
                    <div class="info-row"><span class="info-label">หมายเหตุ</span><span class="info-value text-slate-400"><?= htmlspecialchars($vehicle['notes']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    const images = <?= json_encode(array_map(function($img) { return $img['filename']; }, $images)) ?>;
    
    function openLB(i) {
        let cur = i;
        const el = document.createElement('div');
        el.className = 'share-lightbox';
        render();
        document.body.appendChild(el);
        
        function render() {
            el.innerHTML = `
                <div class="share-lb-header">
                    <button class="share-lb-btn" onclick="this.closest('.share-lightbox').remove()"><i class="bx bx-x"></i></button>
                    <span class="share-counter">${cur + 1} / ${images.length}</span>
                    <button class="share-lb-btn" onclick="saveSingle(${cur})"><i class="bx bx-download"></i></button>
                </div>
                <div class="share-lb-body">
                    <img src="uploads/${images[cur]}" alt="">
                </div>
            `;
        }
        
        // Swipe support
        let sx = 0;
        el.addEventListener('touchstart', function(e) { sx = e.touches[0].clientX; });
        el.addEventListener('touchend', function(e) {
            const dx = e.changedTouches[0].clientX - sx;
            if (Math.abs(dx) > 50) {
                cur = dx > 0 ? Math.max(0, cur - 1) : Math.min(images.length - 1, cur + 1);
                render();
            }
        });
        
        // Click on body to navigate
        el.addEventListener('click', function(e) {
            if (e.target.tagName === 'IMG') {
                const rect = e.target.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                if (clickX < rect.width / 2) {
                    cur = Math.max(0, cur - 1);
                } else {
                    cur = Math.min(images.length - 1, cur + 1);
                }
                render();
            }
        });
    }
    
    async function saveSingle(i) {
        try {
            const res = await fetch('uploads/' + images[i]);
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = images[i];
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch(e) { alert('ดาวน์โหลดไม่สำเร็จ'); }
    }
    
    async function downloadAll() {
        const btn = event.target.closest('button');
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> กำลังดาวน์โหลด...';
        btn.disabled = true;
        const promises = images.map(async (filename) => {
            try {
                const res = await fetch('uploads/' + filename);
                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch(e) {}
        });
        await Promise.all(promises);
        btn.innerHTML = '<i class="bx bx-check"></i> ดาวน์โหลดเสร็จแล้ว!';
        setTimeout(() => {
            btn.innerHTML = '<i class="bx bx-download"></i> ดาวน์โหลดทั้งหมด (' + images.length + ' รูป)';
            btn.disabled = false;
        }, 2000);
    }
    </script>
</body>
</html>
