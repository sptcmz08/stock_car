<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dara Autocar - เข้าสู่ระบบ</title>
    <meta name="description" content="ระบบจัดการสต็อกรถยนต์ - เข้าสู่ระบบ">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=3">

    <style>
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            animation: fadeInUp 0.5s ease;
        }
        .login-logo {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: linear-gradient(135deg, #f97316, #ea580c, #fb923c);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin: 0 auto 16px;
            box-shadow: 0 8px 30px rgba(249, 115, 22, 0.3);
        }
        .login-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            color: #f1f5f9;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
            outline: none;
        }
        .login-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
            background: rgba(255, 255, 255, 0.08);
        }
        .login-input::placeholder {
            color: #64748b;
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 20px;
            transition: color 0.3s ease;
        }
        .input-group:focus-within .input-icon {
            color: #f97316;
        }
        .shake {
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="login-card w-full max-w-sm p-8" id="loginCard">
        <div class="text-center mb-8">
            <div class="login-logo">
                <i class='bx bxs-car'></i>
            </div>
            <h1 class="text-2xl font-bold gradient-text">Dara Autocar</h1>
            <p class="text-slate-500 text-sm mt-1">ระบบสต็อกรถยนต์</p>
        </div>

        <form onsubmit="handleLogin(event)">
            <div class="mb-4">
                <div class="input-group relative">
                    <i class='bx bxs-user input-icon'></i>
                    <input type="text" id="username" class="login-input" placeholder="ชื่อผู้ใช้" autocomplete="username" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <div class="input-group relative">
                    <i class='bx bxs-lock-alt input-icon'></i>
                    <input type="password" id="password" class="login-input" placeholder="รหัสผ่าน" autocomplete="current-password" required>
                </div>
            </div>
            <div id="loginError" class="text-red-400 text-sm text-center mb-3 hidden"></div>
            <button type="submit" id="loginBtn" class="btn-primary w-full justify-center !py-3.5 !text-base !rounded-xl">
                <i class='bx bx-log-in'></i> เข้าสู่ระบบ
            </button>
        </form>
    </div>

    <script>
    async function handleLogin(e) {
        e.preventDefault();
        const btn = document.getElementById('loginBtn');
        const errEl = document.getElementById('loginError');
        const card = document.getElementById('loginCard');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> กำลังเข้าสู่ระบบ...';
        errEl.classList.add('hidden');

        try {
            const resp = await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: document.getElementById('username').value,
                    password: document.getElementById('password').value
                })
            });
            const data = await resp.json();
            
            if (data.success) {
                window.location.href = 'index.php';
            } else {
                errEl.textContent = data.error || 'เกิดข้อผิดพลาด';
                errEl.classList.remove('hidden');
                card.classList.add('shake');
                setTimeout(() => card.classList.remove('shake'), 400);
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-log-in"></i> เข้าสู่ระบบ';
            }
        } catch (err) {
            errEl.textContent = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-log-in"></i> เข้าสู่ระบบ';
        }
    }
    </script>
</body>
</html>
