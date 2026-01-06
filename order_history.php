<?php
session_start();
require 'config.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// --- 1. การตั้งค่าแบ่งหน้า (Pagination) ---
$limit = 20; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// --- 2. รับค่าการค้นหา ---
$search_id = isset($_GET['search_id']) ? $_GET['search_id'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// --- 3. สร้างเงื่อนไข Query ---
$sql_where = " WHERE 1=1";
$params = [];

if (!empty($search_id)) {
    $sql_where .= " AND order_id LIKE ?";
    $params[] = "%$search_id%";
}
if (!empty($start_date) && !empty($end_date)) {
    $sql_where .= " AND DATE(order_date) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
} elseif (!empty($start_date)) {
    $sql_where .= " AND DATE(order_date) = ?";
    $params[] = $start_date;
}

// นับจำนวนทั้งหมด
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM orders $sql_where");
$stmt_count->execute($params);
$total_items = $stmt_count->fetchColumn();
$total_pages = ceil($total_items / $limit);

// ดึงข้อมูล
$sql = "SELECT * FROM orders $sql_where ORDER BY order_id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// ยอดรวมสรุป
$stmt_total = $pdo->prepare("SELECT SUM(total_amount) FROM orders $sql_where");
$stmt_total->execute($params);
$search_total = $stmt_total->fetchColumn() ?: 0;

// Logic การลบ (ย้ายมาประมวลผลเมื่อมีการส่งค่า POST จาก Modal)
if (isset($_POST['confirm_delete_id'])) {
    $oid = $_POST['confirm_delete_id'];
    $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")->execute([$oid]);
    $pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$oid]);
    header("Location: order_history.php?res=deleted"); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการขาย - Minimal Cafe</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap');
        body { font-family: 'Kanit', sans-serif; background-color: #fdfaf7; color: #5a5a5a; }
        .main-content { margin-left: 260px; padding: 40px; transition: 0.3s; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 20px; } }
        
        .card { border: none; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .search-box { background: white; border-radius: 25px; padding: 25px; margin-bottom: 30px; border: 1px solid #eee; }
        .form-control { border-radius: 12px; border: 1px solid #eee; padding: 10px; font-size: 0.9rem; }
        .btn-search { border-radius: 12px; background: #8d7b68; color: white; border: none; padding: 10px 25px; }
        
        /* สไตล์ Modal แบบมินิมอล */
        .modal-content { border-radius: 30px; border: none; box-shadow: 0 15px 50px rgba(0,0,0,0.1); }
        .btn-confirm-del { background-color: #dc3545; color: white; border-radius: 12px; border: none; padding: 10px 20px; }
        .btn-cancel { background-color: #f8f9fa; color: #5a5a5a; border-radius: 12px; border: 1px solid #eee; padding: 10px 20px; }
        
        .pagination .page-link { border: none; color: #8d7b68; background: white; margin: 0 3px; border-radius: 10px !important; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .pagination .page-item.active .page-link { background: #8d7b68; color: white; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold m-0">📄 ประวัติการขาย</h3>
                <div class="text-end">
                    <small class="text-muted d-block">ยอดรวมที่ค้นหา</small>
                    <h4 class="fw-bold text-success m-0">฿<?= number_format($search_total, 2) ?></h4>
                </div>
            </div>

            <div class="search-box shadow-sm">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">เลขใบเสร็จ / คิว</label>
                        <input type="text" name="search_id" class="form-control" value="<?= htmlspecialchars($search_id) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">จากวันที่</label>
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">ถึงวันที่</label>
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-search w-100">ค้นหา</button>
                        <a href="order_history.php" class="btn btn-light rounded-3 border w-50 p-2 text-decoration-none text-center small">ล้าง</a>
                    </div>
                </form>
            </div>

            <div class="card overflow-hidden mb-4 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-center align-middle">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th>เลขใบเสร็จ</th>
                                <th>วันที่-เวลา</th>
                                <th>ยอดสุทธิ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $o): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= $o['order_id'] ?></td>
                                <td class="small"><?= date('d/m/Y H:i', strtotime($o['order_date'])) ?></td>
                                <td class="fw-bold text-primary">฿<?= number_format($o['total_amount'], 2) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-light border rounded-pill px-3" onclick="viewOrderDetail('<?= $o['order_id'] ?>')">ตรวจสอบ</button>
                                    <a href="print_receipt.php?order_id=<?= $o['order_id'] ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2 mx-1"><i class="bi bi-printer"></i></a>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="showDeleteModal('<?= $o['order_id'] ?>')">ลบ</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>&search_id=<?= $search_id ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>">ย้อนกลับ</a>
                    </li>
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link shadow-sm" href="?page=<?= $i ?>&search_id=<?= $search_id ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?>&search_id=<?= $search_id ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>">ถัดไป</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3" style="font-size: 3rem;"><i class="bi bi-exclamation-octagon"></i></div>
                    <h5 class="fw-bold mb-2">ยืนยันการลบ?</h5>
                    <p class="small text-muted mb-4">คุณต้องการลบใบเสร็จเลขที่ <span id="del_id_text" class="fw-bold text-dark"></span> ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้</p>
                    <form method="POST">
                        <input type="hidden" name="confirm_delete_id" id="hidden_del_id">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-cancel w-100" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-confirm-del w-100">ยืนยันการลบ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body p-4" id="modalBody"></div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันแสดง Modal ลบ
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        function showDeleteModal(id) {
            document.getElementById('del_id_text').innerText = id;
            document.getElementById('hidden_del_id').value = id;
            deleteModal.show();
        }

        // ฟังก์ชันดูรายละเอียด (คงเดิม)
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        async function viewOrderDetail(id) {
            const body = document.getElementById('modalBody');
            body.innerHTML = 'กำลังโหลด...';
            detailModal.show();
            const res = await fetch(`get_order_items.php?order_id=${id}`);
            const data = await res.json();
            let html = `<h5 class="fw-bold mb-4">ออเดอร์ #${id}</h5>`;
            data.forEach(item => {
                html += `<div class="d-flex justify-content-between mb-2">
                            <span class="small">${item.p_name} x ${item.qty}</span>
                            <span class="fw-bold small">฿${(item.qty * item.price_at_sale).toFixed(2)}</span>
                         </div>`;
            });
            body.innerHTML = html + `<hr><button class="btn btn-dark w-100 rounded-pill" data-bs-dismiss="modal">ปิด</button>`;
        }
    </script>
</body>
</html>