<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Handle AJAX request untuk get admin list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_admin_list') {
    header('Content-Type: application/json');
    
    $stmt = $conn->prepare("SELECT id, username, email, phone, address, role FROM users WHERE role = 'admin' ORDER BY username ASC");
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database query error'
        ]);
        exit;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $row['phone'] = $row['phone'] ?? 'N/A';
        $row['address'] = $row['address'] ?? 'N/A';
        $admins[] = $row;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'admins' => $admins,
        'count' => count($admins)
    ]);
    exit;
}

// Set default values if session variables are not set
$username = $_SESSION['username'] ?? $_SESSION['email'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$email = $_SESSION['email'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

// Get user avatar path
$avatar_path = '';
if ($user_id) {
    $avatar_dir = 'uploads/avatars/';
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($extensions as $ext) {
        $file = $avatar_dir . 'user_' . $user_id . '.' . $ext;
        if (file_exists($file)) {
            $avatar_path = $file;
            break;
        }
    }
}

// Ambil total user dari database
$totalUsers = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $totalUsers = $row['total'];
}

// Ambil total kegiatan dari database
$totalKegiatan = 0;
$resultKegiatan = mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal");
if ($resultKegiatan) {
    $rowKegiatan = mysqli_fetch_assoc($resultKegiatan);
    $totalKegiatan = $rowKegiatan['total'];
}

// Get inventory data from peminjaman_barang_db
$invDbName = 'peminjaman_barang_db';
$inventoryError = '';
$inventoryItems = [];

// Connect to inventory database
$invConn = mysqli_connect('localhost', 'root', '', $invDbName);
if (!$invConn) {
    $inventoryError = 'Gagal koneksi ke database inventory: ' . mysqli_connect_error();
} else {
    // Get items that are either out of stock (habis/hilang) or currently borrowed
    $query = "SELECT 
        b.*, 
        COALESCE(p.borrowed_count, 0) as borrowed_count
    FROM barang b 
    LEFT JOIN (
        SELECT id_barang, COUNT(*) as borrowed_count 
        FROM peminjaman 
        WHERE status = 'dipinjam' 
        GROUP BY id_barang
    ) p ON b.id_barang = p.id_barang
    WHERE 
        b.flag_status IN ('habis', 'hilang')
        OR b.jumlah_tersedia = 0
        OR p.borrowed_count > 0
    ORDER BY 
        CASE 
            WHEN b.flag_status IN ('habis', 'hilang') THEN 1
            WHEN b.jumlah_tersedia = 0 THEN 2
            ELSE 3
        END,
        p.borrowed_count DESC";
    
    $result = mysqli_query($invConn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $inventoryItems[] = $row;
        }
    } else {
        $inventoryError = 'Gagal mengambil data inventory: ' . mysqli_error($invConn);
    }
}

// Get daftar anak magang dari database peminjaman_barang_db dengan absensi dari absensi_db
$anakMagangList = [];
if ($invConn) {
    $today = date('Y-m-d');
    
    // Pertama ambil data karyawan magang dari peminjaman_barang_db (termasuk jam kerja)
    $queryMagang = "SELECT id_karyawan, nama, id_card, divisi, jabatan, jam_mulai, jam_akhir 
                    FROM karyawan 
                    WHERE jabatan = 'Magang' OR divisi LIKE '%Magang%'
                    ORDER BY nama ASC";
    $resultMagang = mysqli_query($invConn, $queryMagang);
    
    if ($resultMagang && mysqli_num_rows($resultMagang) > 0) {
        // Buka koneksi ke absensi_db untuk mendapatkan jam masuk/keluar
        $absensiConn = @mysqli_connect('localhost', DB_USER, DB_PASS, 'absensi_db');
        
        while ($rowMagang = mysqli_fetch_assoc($resultMagang)) {
            $rowMagang['jam_masuk'] = '-';
            $rowMagang['jam_keluar'] = '-';
            
            // Jika koneksi absensi berhasil, ambil data absensi hari ini
            // Gunakan id_card sebagai JOIN key karena id_karyawan bisa berbeda antar database
            if ($absensiConn) {
                $queryAbsensi = "SELECT a.jam_masuk, a.jam_keluar 
                                FROM absensi a 
                                INNER JOIN karyawan k ON a.id_karyawan = k.id_karyawan 
                                WHERE k.id_card = '" . mysqli_real_escape_string($absensiConn, $rowMagang['id_card']) . "' 
                                AND a.tanggal = '" . $today . "' LIMIT 1";
                $resultAbsensi = @mysqli_query($absensiConn, $queryAbsensi);
                
                if ($resultAbsensi && mysqli_num_rows($resultAbsensi) > 0) {
                    $rowAbsensi = mysqli_fetch_assoc($resultAbsensi);
                    if ($rowAbsensi['jam_masuk']) {
                        $rowMagang['jam_masuk'] = date('H:i', strtotime($rowAbsensi['jam_masuk']));
                    }
                    if ($rowAbsensi['jam_keluar']) {
                        $rowMagang['jam_keluar'] = date('H:i', strtotime($rowAbsensi['jam_keluar']));
                    }
                }
            }
            
            $anakMagangList[] = $rowMagang;
        }
        
        if ($absensiConn) {
            mysqli_close($absensiConn);
        }
    }
}

// No attendance related code needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024px, initial-scale=1.0, user-scalable=no">
    <title>Dashboard | Modern Auth System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #f093fb;
            --accent: #f5576c;
            --dark: #2d3748;
            --light: #f7fafc;
            --success: #48bb78;
            --warning: #ed8936;
            --error: #f56565;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass: rgba(255, 255, 255, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f1f5f9;
            min-height: 100vh;
            color: var(--dark);
            font-family: 'Poppins', sans-serif;
            min-width: 1200px;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
            padding: 20px 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 100;
            box-shadow: var(--shadow);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            animation: slideUpBounce 1s ease-out;
        }
        
        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }
        
        .sidebar-header p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            margin: 5px 15px;
            position: relative;
        }
        
        .sidebar-menu a i {
            margin-right: 12px;
            width: 20px;
        }
        
        .sidebar-menu a:hover, 
        .sidebar-menu a.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            overflow-x: hidden;
            z-index: 1;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease-out forwards;
            position: relative;
            z-index: 10;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .sidebar-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 8px;
            padding: 10px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .header h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            animation: slideUpBounce 1s ease-out;
        }
        
        .user-profile .dropdown {
            position: relative;
        }
        
        .user-profile .dropdown-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .user-profile .dropdown-menu {
            position: absolute;
            right: 0;
            top: 50px;
            background: white;
            width: 200px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 10px 0;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 200;
        }
        
        .user-profile .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            top: 45px;
        }
        
        .user-profile .dropdown-menu a {
            display: block;
            padding: 8px 15px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .user-profile .dropdown-menu a:hover {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }
        
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.8s ease-out forwards;
            transform: translateY(30px);
            position: relative;
            z-index: 1;
        }
        
        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }
        
        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .card-icon.blue {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .card-icon.green {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .card-icon.orange {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .card-icon.red {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }
        
        .card h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .card h2 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .card-footer {
            display: flex;
            align-items: center;
            margin-top: 10px;
            font-size: 13px;
        }
        
        .card-footer i {
            margin-right: 5px;
        }
        
        .card-footer.positive {
            color: var(--success);
        }
        
        .card-footer.negative {
            color: var(--error);
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        @media (min-width: 768px) {
            .content-grid {
                grid-template-columns: 2fr 1fr;
            }
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
            animation: fadeInUp 1s ease-out 0.5s forwards;
            transform: translateY(30px);
            position: relative;
            z-index: 1;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-header img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 3px solid rgba(99, 102, 241, 0.2);
            object-fit: cover;
            animation: slideUpBounce 1.2s ease-out;
        }
        
        .profile-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .profile-header p {
            color: #64748b;
            font-size: 14px;
        }
        
        .profile-badge {
            display: inline-block;
            padding: 3px 10px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .profile-details {
            margin-top: 20px;
        }
        
        .profile-detail {
            display: flex;
            margin-bottom: 10px;
        }
        
        .profile-detail i {
            width: 20px;
            margin-right: 10px;
            color: var(--primary);
        }
        
        .admin-panel {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-top: 30px;
            animation: fadeInUp 1s ease-out 0.7s forwards;
            transform: translateY(30px);
            position: relative;
            z-index: 1;
        }
        
        .admin-panel h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .admin-feature {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .admin-feature i {
            width: 30px;
            height: 30px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .admin-feature:last-child {
            border-bottom: none;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .header h2 {
                font-size: 24px;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .sidebar-overlay {
                display: block;
            }
            
            .sidebar.show + .sidebar-overlay {
                display: block;
            }
            
            .cards {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <div class="sidebar">
        <div class="sidebar-header">
        <div style="display: flex; align-items: center;">
            <img src="<?php echo htmlspecialchars($avatar_path ?: 'https://placehold.co/100x100'); ?>" alt="User Avatar">
                <div>
                    <h3><?php echo htmlspecialchars($username); ?></h3>
                    <p><?php echo htmlspecialchars($role); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <div class="sidebar-menu">
            <a href="#" class="active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="account.php">
                <i class="fas fa-user-circle"></i>
                <span>Informasi Akun</span>
            </a>
            <?php if (isAdmin()): ?>
                <a href="admin_panel.php">
                    <i class="fas fa-users-cog"></i>
                    <span>Admin Panel</span>
                </a>
                <a href="jadual_kegiatan.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Jadual</span>
                </a>
                <a href="peminjaman.php">
                    <i class="fas fa-box-open"></i>
                    <span>Peminjaman Barang</span>
                </a>
                <a href="add_magang.php">
                    <i class="fas fa-user-plus"></i>
                    <span>Tambah Anak Magang</span>
                </a>
            <?php endif; ?>
            <a href="absen_tanpa_rfid.php">
                <i class="fas fa-user-check"></i>
                <span>Absensi (Tanpa RFID)</span>
            </a>
            <a href="#" onclick="confirmLogout(); return false;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Dashboard</h2>
            </div>
            
            <div class="user-profile">
                <img src="<?php echo htmlspecialchars($avatar_path ?: 'https://placehold.co/100x100'); ?>" alt="User Profile Picture">
                <div class="dropdown">
                    <div class="dropdown-toggle" onclick="toggleDropdown()">
                        <span><?php echo htmlspecialchars($username); ?></span>
                        <i class="fas fa-chevron-down" style="margin-left: 5px;"></i>
                    </div>
                    <!-- Dropdown Menu -->
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="jadual_kegiatan.php"><i class="fas fa-chart-line"></i><sp>jadual</sp></a>
                        <a href="absen_tanpa_rfid.php"><i class="fas fa-user-check"></i> Absensi (Tanpa RFID)</a>
                        <?php if (isAdmin()): ?>
                            <a href="peminjaman.php"><i class="fas fa-box-open"></i> Peminjaman Barang</a>
                        <?php endif; ?>
                        <a href="#" onclick="confirmLogout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cards -->
        <div class="cards">
            <div class="card" style="cursor: pointer;" onclick="openAdminListModal()" title="Click to view available admins">
                <div class="card-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Total Users</h3>
                <h2><?php echo number_format($totalUsers); ?></h2>
                <div class="card-footer positive">
                    <i class="fas fa-arrow-up"></i> 12% from last month
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon orange">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Kegiatan</h3>
                <h2><?php echo number_format($totalKegiatan); ?></h2>
                <div class="card-footer positive">
                    <i class="fas fa-arrow-up"></i> Data real-time
                </div>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Main Content -->
            <div class="card">
                <h3>Menu Utama</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                    <a href="account.php" style="text-decoration: none;">
                        <div class="admin-feature" style="background: rgba(99, 102, 241, 0.1); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-user-circle" style="font-size: 24px; color: var(--primary); margin-bottom: 10px;"></i>
                            <div>
                                <h4 style="color: var(--dark);">Informasi Akun</h4>
                                <p style="color: #64748b;">Lihat dan edit profil Anda</p>
                            </div>
                        </div>
                    </a>
                    
                    <?php if (isAdmin()): ?>
                    <a href="inventory.php" style="text-decoration: none;">
                        <div class="admin-feature" style="background: rgba(99, 102, 241, 0.1); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-box" style="font-size: 24px; color: var(--primary); margin-bottom: 10px;"></i>
                            <div>
                                <h4 style="color: var(--dark);">Inventory</h4>
                                <p style="color: #64748b;">Kelola barang dan peminjaman</p>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <a href="jadual_kegiatan.php" style="text-decoration: none;">
                        <div class="admin-feature" style="background: rgba(99, 102, 241, 0.1); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-calendar-alt" style="font-size: 24px; color: var(--primary); margin-bottom: 10px;"></i>
                            <div>
                                <h4 style="color: var(--dark);">Jadual Kegiatan</h4>
                                <p style="color: #64748b;">Lihat dan kelola jadual</p>
                            </div>
                        </div>
                    </a>
                    <a href="absen_tanpa_rfid.php" style="text-decoration: none;">
                        <div class="admin-feature" style="background: linear-gradient(90deg,#10b9811a,#4f46e51a); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-user-check" style="font-size: 24px; color: #10b981; margin-bottom: 10px;"></i>
                            <div>
                                <h4 style="color: var(--dark);">Absensi (Tanpa RFID)</h4>
                                <p style="color: #64748b;">Form cepat bagi anak magang untuk absen menggunakan NIS atau nama</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Side Content -->
            <div>
                <!-- Inventory Status Section -->
                <div class="card" style="margin-bottom:20px;">
                    <h3>Status Barang (Habis/Dipinjam)</h3>
                    <?php if ($inventoryError): ?>
                        <p style="color:#ef4444"><?php echo htmlspecialchars($inventoryError); ?></p>
                    <?php endif; ?>

                    <?php if (empty($inventoryItems)): ?>
                        <p>Semua barang tersedia dan dalam stok.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding:8px;border-bottom:1px solid #e2e8f0; text-align:left;">Nama Barang</th>
                                        <th style="padding:8px;border-bottom:1px solid #e2e8f0; text-align:left;">Kode</th>
                                        <th style="padding:8px;border-bottom:1px solid #e2e8f0; text-align:left;">Status</th>
                                        <th style="padding:8px;border-bottom:1px solid #e2e8f0; text-align:left;">Tersedia</th>
                                        <th style="padding:8px;border-bottom:1px solid #e2e8f0; text-align:left;">Dipinjam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventoryItems as $item): ?>
                                        <tr>
                                            <td style="padding:8px;border-bottom:1px solid #e2e8f0;">
                                                <?php echo htmlspecialchars($item['nama_barang']); ?>
                                            </td>
                                            <td style="padding:8px;border-bottom:1px solid #e2e8f0;">
                                                <?php echo htmlspecialchars($item['kode_barang'] ?? '-'); ?>
                                            </td>
                                            <td style="padding:8px;border-bottom:1px solid #e2e8f0;">
                                                <?php if ($item['flag_status']): ?>
                                                    <span style="display:inline-block;padding:2px 8px;background:#ef4444;color:white;border-radius:12px;font-size:12px;">
                                                        <?php echo strtoupper(htmlspecialchars($item['flag_status'])); ?>
                                                    </span>
                                                <?php elseif ($item['borrowed_count'] > 0): ?>
                                                    <span style="display:inline-block;padding:2px 8px;background:#f59e0b;color:white;border-radius:12px;font-size:12px;">
                                                        DIPINJAM
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding:8px;border-bottom:1px solid #e2e8f0;">
                                                <?php echo htmlspecialchars($item['jumlah_tersedia']); ?>/<?php echo htmlspecialchars($item['jumlah_total']); ?>
                                            </td>
                                            <td style="padding:8px;border-bottom:1px solid #e2e8f0;">
                                                <?php echo htmlspecialchars($item['borrowed_count']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (isAdmin()): ?>
                    <div class="admin-panel">
                        <h3>Admin Tools</h3>

                        <div class="admin-feature">
                            <i class="fas fa-box"></i>
                            <div>
                                <h4>Inventory</h4>
                                <p><a href="inventory.php" style="color:var(--primary);text-decoration:none;">Manage inventory &amp; borrowings</a></p>
                            </div>
                        </div>

                        <div class="admin-feature">
                            <i class="fas fa-users"></i>
                            <div>
                                <h4>Manage Users</h4>
                                <p>Add, edit or remove system users</p>
                            </div>
                        </div>

                        <div class="admin-feature">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <h4>Permissions</h4>
                                <p>Configure user roles and permissions</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="profile-card" style="margin-top: 20px;">
                    <h3>Daftar Anak Magang yang Tercatat</h3>
                    
                    <?php if (empty($anakMagangList)): ?>
                        <div style="padding: 20px; text-align: center; color: #888;">
                            <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                            <p>Belum ada anak magang yang tercatat</p>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto; border-radius: 8px; border: 1px solid #e0e0e0;">
                            <table style="width: 100%; border-collapse: collapse; background: white;">
                                <thead style="background: #f5f5f5; position: sticky; top: 0;">
                                    <tr style="border-bottom: 2px solid #e0e0e0;">
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;">Nama</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;">NIS</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;">Divisi</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #333;">Jam Kerja</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #333;">Jam Masuk</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #333;">Jam Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($anakMagangList as $magang): ?>
                                        <tr style="border-bottom: 1px solid #f0f0f0; hover-style: background: #fafafa;">
                                            <td style="padding: 12px; color: #333;">
                                                <i class="fas fa-user-graduate" style="margin-right: 8px; color: #667eea;"></i>
                                                <?php echo htmlspecialchars($magang['nama'] ?? '-'); ?>
                                            </td>
                                            <td style="padding: 12px; color: #666; font-family: monospace; font-size: 12px;">
                                                <?php echo htmlspecialchars($magang['id_card'] ?? '-'); ?>
                                            </td>
                                            <td style="padding: 12px; color: #666;">
                                                <span style="background: #e8f4f8; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                                                    <?php echo htmlspecialchars($magang['divisi'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 12px; text-align: center; color: #333; font-weight: 500; font-family: monospace; font-size: 12px;">
                                                <?php if (isset($magang['jam_mulai']) && $magang['jam_mulai']): ?>
                                                    <span style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px;">
                                                        <?php echo substr($magang['jam_mulai'], 0, 5); ?> - <?php echo substr($magang['jam_akhir'] ?? '17:00', 0, 5); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #999;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center; color: #333; font-weight: 500; font-family: monospace;">
                                                <?php if ($magang['jam_masuk'] !== '-'): ?>
                                                    <span style="background: #dcfce7; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                                        <i class="fas fa-clock" style="margin-right: 4px;"></i><?php echo $magang['jam_masuk']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #999;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center; color: #333; font-weight: 500; font-family: monospace;">
                                                <?php if ($magang['jam_keluar'] !== '-'): ?>
                                                    <span style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                                        <i class="fas fa-clock" style="margin-right: 4px;"></i><?php echo $magang['jam_keluar']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #999;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 12px; padding: 12px; background: #f9f9f9; border-radius: 6px; text-align: center; font-size: 13px; color: #666;">
                            <i class="fas fa-info-circle"></i> Total: <strong><?php echo count($anakMagangList); ?></strong> anak magang | <i class="fas fa-calendar"></i> <strong><?php echo date('d M Y', strtotime('today')); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleDropdown() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        }
        
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('show');
            if (sidebar.classList.contains('show')) {
                overlay.style.display = 'block';
            } else {
                overlay.style.display = 'none';
            }
        }
        
        function confirmLogout() {
            // Buat modal konfirmasi custom
            const modal = document.createElement('div');
            modal.id = 'logoutModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: white;
                border-radius: 12px;
                padding: 30px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                text-align: center;
                animation: slideIn 0.3s ease-out;
            `;
            
            modalContent.innerHTML = `
                <i class="fas fa-sign-out-alt" style="font-size: 48px; color: #667eea; margin-bottom: 20px; display: block;"></i>
                <h3 style="margin: 0 0 10px 0; color: #333; font-size: 20px;">Yakin ingin keluar?</h3>
                <p style="margin: 0 0 30px 0; color: #666; font-size: 14px;">Anda akan keluar dari sistem. Anda perlu login kembali untuk mengakses aplikasi.</p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button onclick="document.getElementById('logoutModal').remove()" style="
                        background: #e5e7eb;
                        color: #333;
                        border: none;
                        padding: 10px 24px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s;
                    " onmouseover="this.style.background='#d1d5db'" onmouseout="this.style.background='#e5e7eb'">
                        Tetap di Sini
                    </button>
                    <button onclick="window.location.href = 'logout.php';" style="
                        background: #ef4444;
                        color: white;
                        border: none;
                        padding: 10px 24px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s;
                    " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                        Keluar
                    </button>
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Tutup modal saat klik di luar
            modal.onclick = function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            };
            
            // Tutup dengan ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('logoutModal')) {
                    document.getElementById('logoutModal').remove();
                }
            });
        }
        
        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-toggle')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>

    <!-- Admin List Modal -->
    <div id="adminListModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; font-size: 20px; color: #1f2937;">Available Admins</h2>
                    <button onclick="closeAdminListModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">×</button>
                </div>
            </div>
            
            <div id="adminListContent" style="padding: 20px;">
                <div style="text-align: center; color: #6b7280;">Loading admins...</div>
            </div>
            
            <div style="padding: 20px; border-top: 1px solid #e5e7eb; text-align: right;">
                <button onclick="closeAdminListModal()" style="padding: 8px 16px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        function openAdminListModal() {
            const modal = document.getElementById('adminListModal');
            modal.style.display = 'flex';
            
            // Fetch admin list
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=get_admin_list'
            })
            .then(response => response.json())
            .then(data => {
                const content = document.getElementById('adminListContent');
                
                if (data.success && data.admins && data.admins.length > 0) {
                    let html = '<div style="space-y: 12px;">';
                    
                    data.admins.forEach(admin => {
                        const phone = admin.phone && admin.phone !== 'N/A' ? admin.phone : 'Not provided';
                        const email = admin.email || 'N/A';
                        const address = admin.address && admin.address !== 'N/A' ? admin.address : 'Not provided';
                        
                        html += `
                            <div style="padding: 16px; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 12px; background: #f9fafb;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <h3 style="margin: 0 0 8px 0; color: #1f2937; font-size: 16px; font-weight: 600;">${escapeHtml(admin.username)}</h3>
                                        
                                        <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">
                                            <div style="margin-bottom: 4px;">
                                                <strong>Email:</strong> <a href="mailto:${escapeHtml(email)}" style="color: #3b82f6; text-decoration: none; cursor: pointer;">${escapeHtml(email)}</a>
                                            </div>
                                            <div style="margin-bottom: 4px;">
                                                <strong>Phone:</strong> 
                                                ${phone !== 'Not provided' ? '<a href="tel:' + escapeHtml(phone) + '" style="color: #3b82f6; text-decoration: none; cursor: pointer;">' + escapeHtml(phone) + '</a>' : '<span>' + phone + '</span>'}
                                            </div>
                                            <div>
                                                <strong>Address:</strong> ${escapeHtml(address)}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                    content.innerHTML = html;
                } else if (data.success && data.admins.length === 0) {
                    content.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: #6b7280;">No admins available at the moment.</div>';
                } else {
                    content.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: #ef4444;">Error loading admins: ' + escapeHtml(data.message || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('adminListContent').innerHTML = '<div style="text-align: center; padding: 40px 20px; color: #ef4444;">Error: ' + error.message + '</div>';
            });
        }
        
        function closeAdminListModal() {
            document.getElementById('adminListModal').style.display = 'none';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close modal when clicking outside
        document.getElementById('adminListModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAdminListModal();
            }
        });
    </script>
    
    <footer style="text-align: center; padding: 20px; background: rgba(255, 255, 255, 0.8); border-top: 1px solid rgba(255, 255, 255, 0.3); margin-top: 50px; position: relative; z-index: 1;">
        <p style="color: #667eea; font-weight: 500;">&copy; 2026 Muhammad Rifqi Andrian. All rights reserved.</p>
    </footer>
</body>
</html>
