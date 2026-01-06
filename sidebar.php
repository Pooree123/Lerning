<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'config.php';

function isActive($fileName) {
    return basename($_SERVER['PHP_SELF']) == $fileName ? 'active' : '';
}

$stmt_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 0");
$new_orders_count = $stmt_count->fetchColumn();
?>

<div class="mobile-top-nav d-lg-none shadow-sm">
    <button class="btn border-0 p-2" onclick="toggleSidebar()">
        <i class="bi bi-list fs-2"></i>
    </button>
    <div class="fw-bold fs-5 text-dark">MINIMAL CAFE</div>
    <div style="position: relative; width: 45px;">
        <?php if ($new_orders_count > 0): ?>
            <span class="position-absolute top-0 start-0 translate-middle p-2 bg-danger border border-light rounded-circle" style="margin-top: 15px; margin-left: 10px;"></span>
        <?php endif; ?>
    </div> 
</div>

<nav class="sidebar shadow-sm" id="sidebar">
    <div class="p-4 text-center border-bottom bg-white d-none d-lg-block">
        <h5 class="fw-bold mb-0 text-dark">MINIMAL CAFE</h5>
    </div>
    
    <div class="p-3 d-lg-none text-end">
        <button class="btn-close" onclick="toggleSidebar()"></button>
    </div>

    <div class="nav flex-column mt-lg-3 p-2">
        <div class="small text-muted px-4 mb-2 text-uppercase fw-bold" style="font-size: 0.7rem;">หน้าร้าน</div>
        <a href="index.php" class="nav-link <?php echo isActive('index.php'); ?>"><i class="bi bi-cart me-2"></i> คิดเงิน</a>
        
        <a href="staff_order_view.php" class="nav-link <?php echo isActive('staff_order_view.php'); ?> justify-content-between">
            <span><i class="bi bi-bell me-2"></i> รับออเดอร์ลูกค้า</span>
            <?php if ($new_orders_count > 0): ?>
                <span class="badge rounded-pill bg-danger animate__pulse">
                    <?= $new_orders_count ?>
                </span>
            <?php endif; ?>
        </a>

        <div class="small text-muted px-4 mt-4 mb-2 text-uppercase fw-bold" style="font-size: 0.7rem;">จัดการระบบ</div>
        <a href="dashboard.php" class="nav-link <?php echo isActive('dashboard.php'); ?>"><i class="bi bi-graph-up me-2"></i> สถิติภาพรวม</a>
        <a href="order_history.php" class="nav-link <?php echo isActive('order_history.php'); ?>"><i class="bi bi-file-text me-2"></i> ประวัติการขาย</a>
        <a href="product_manage.php" class="nav-link <?php echo isActive('product_manage.php'); ?>"><i class="bi bi-box-seam me-2"></i> จัดการสินค้า</a>
        
        <hr class="mx-3 my-4">
        <a href="logout.php" class="nav-link text-danger fw-bold"><i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ</a>
    </div>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<style>
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    .animate__pulse { animation: pulse 1s infinite; }
    
    .mobile-top-nav { display: flex; align-items: center; justify-content: space-between; background: white; height: 60px; position: sticky; top: 0; z-index: 1000; }
    
    .sidebar { width: 260px; height: 100vh; position: fixed; left: 0; top: 0; background: white; border-right: 1px solid #eee; z-index: 1050; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    .nav-link { color: #666; padding: 12px 20px; border-radius: 12px; margin: 2px 10px; text-decoration: none; transition: 0.2s; display: flex; align-items: center; }
    .nav-link:hover { background: #f8f9fa; color: #000; }
    .nav-link.active { background: #212529; color: white !important; font-weight: 500; }
    
    .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.3); z-index: 1040; backdrop-filter: blur(2px); }
    
    @media (max-width: 991px) { 
        .sidebar { transform: translateX(-100%); width: 280px; } 
        .sidebar.active { transform: translateX(0); } 
        .sidebar-overlay.active { display: block; } 
    }
</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if(sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    }

    // ป้องกันการกดเมนูแล้วไม่ทำงานถ้า Bootstrap ยังโหลดไม่เสร็จ
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Sidebar System Ready");
    });
</script>