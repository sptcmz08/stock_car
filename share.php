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
            gap: 4px;
            padding: 4px;
        }
        .share-grid img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .share-grid img:hover { opacity: 0.85; }
        .share-info {
            padding: 20px 16px;
        }
        .share-lightbox {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.95);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .share-lightbox img {
            max-width: 95vw;
            max-height: 90vh;
            object-fit: contain;
        }
        .share-lightbox .close-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="share-header">
        <div class="max-w-2xl mx-auto flex items-center justify-between">
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
    </div>

    <div class="max-w-2xl mx-auto">
        <?php if ($images): ?>
            <div class="share-grid">
                <?php foreach ($images as $i => $img): ?>
                    <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="" onclick="openLB(<?= $i ?>)" loading="lazy">
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
    const images = <?= json_encode(array_map(function($img) { return 'uploads/' . $img['filename']; }, $images)) ?>;
    
    function openLB(i) {
        let cur = i;
        const el = document.createElement('div');
        el.className = 'share-lightbox';
        el.innerHTML = '<button class="close-btn" onclick="this.parentElement.remove()"><i class="bx bx-x"></i></button><img src="' + images[cur] + '">';
        el.addEventListener('click', function(e) { if (e.target === el) el.remove(); });
        document.body.appendChild(el);
        
        el.addEventListener('touchstart', function(e) { this._sx = e.touches[0].clientX; });
        el.addEventListener('touchend', function(e) {
            const dx = e.changedTouches[0].clientX - (this._sx || 0);
            if (Math.abs(dx) > 50) {
                cur = dx > 0 ? Math.max(0, cur - 1) : Math.min(images.length - 1, cur + 1);
                el.querySelector('img').src = images[cur];
            }
        });
    }
    </script>
</body>
</html>
