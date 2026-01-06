<?php
session_start();
require 'config.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงออเดอร์ที่ยังไม่เสร็จสิ้น (status 0, 1, 2) และไม่รวมที่ถูกยกเลิก (-1)
$stmt = $pdo->query("SELECT * FROM orders WHERE order_status >= 0 AND order_status < 3 ORDER BY order_date ASC");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการออเดอร์ลูกค้า - Minimal Cafe</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap');
        body { font-family: 'Kanit', sans-serif; background-color: #fdfaf7; }
        .main-content { margin-left: 260px; padding: 40px; transition: 0.3s; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 20px; } }
        
        .order-card { border: none; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: white; overflow: hidden; position: relative; transition: 0.3s; }
        .order-card:hover { transform: translateY(-5px); }
        
        .status-0 { border-top: 10px solid #ffc107; } /* เข้าใหม่ */
        .status-1 { border-top: 10px solid #0d6efd; } /* กำลังทำ */
        .status-2 { border-top: 10px solid #198754; } /* เสร็จแล้ว */

        .modal-content { border-radius: 30px; border: none; }
        .btn-round { border-radius: 15px; padding: 10px 20px; }
        
        .btn-cancel { position: absolute; top: 15px; right: 15px; color: #dc3545; background: #fff5f5; border: none; width: 35px; height: 35px; border-radius: 50%; transition: 0.2s; }
        .btn-cancel:hover { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0">🔔 รับออเดอร์ลูกค้า</h3>
            <span class="badge bg-dark rounded-pill px-3">อัปเดตอัตโนมัติ</span>
        </div>

        <div class="row g-4">
            <?php foreach($orders as $o): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card order-card status-<?= $o['order_status'] ?> p-4">
                    <button class="btn-cancel" title="ยกเลิกออเดอร์" onclick="confirmStatus('<?= $o['order_id'] ?>', -1, 'ยืนยันการยกเลิกออเดอร์?')">
                        <i class="bi bi-trash3-fill"></i>
                    </button>

                    <div class="d-flex justify-content-between mb-3 me-4">
                        <div>
                            <h5 class="fw-bold mb-0">คิว #<?= substr($o['order_id'], -4) ?></h5>
                            <small class="text-muted">ID: <?= $o['order_id'] ?></small>
                        </div>
                        <span class="text-muted small"><?= date('H:i', strtotime($o['order_date'])) ?></span>
                    </div>

                    <div class="mb-4">
                        <?php
                        $st = $pdo->prepare("SELECT od.*, p.p_name FROM order_details od JOIN products p ON od.product_id = p.p_id WHERE od.order_id = ?");
                        $st->execute([$o['order_id']]);
                        while($item = $st->fetch()):
                        ?>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="small"><?= $item['p_name'] ?></span>
                                <span class="fw-bold">x<?= $item['qty'] ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="d-grid">
                        <?php if($o['order_status'] == 0): ?>
                            <button class="btn btn-warning btn-round fw-bold" onclick="confirmStatus('<?= $o['order_id'] ?>', 1, 'ยืนยันการรับออเดอร์?')">
                                <i class="bi bi-play-fill me-1"></i> รับออเดอร์
                            </button>
                        <?php elseif($o['order_status'] == 1): ?>
                            <button class="btn btn-primary btn-round fw-bold text-white" onclick="confirmStatus('<?= $o['order_id'] ?>', 2, 'ทำออเดอร์นี้เสร็จแล้วใช่ไหม?')">
                                <i class="bi bi-check-circle me-1"></i> ทำเสร็จแล้ว
                            </button>
                        <?php elseif($o['order_status'] == 2): ?>
                            <a href="index.php?pay_id=<?= $o['order_id'] ?>" class="btn btn-success btn-round fw-bold text-white">
                                <i class="bi bi-cash-stack me-1"></i> ไปที่หน้าคิดเงิน
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if(count($orders) == 0): ?>
            <div class="text-center py-5 mt-5">
                <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3">ยังไม่มีออเดอร์ใหม่ในขณะนี้</p>
            </div>
        <?php endif; ?>
    </main>

    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow-lg">
                <div class="modal-body p-4 text-center">
                    <div id="modalIcon" class="mb-3"><i class="bi bi-question-circle text-primary" style="font-size: 3rem;"></i></div>
                    <h5 class="fw-bold mb-3" id="statusTitle">ยืนยันรายการ</h5>
                    <p class="text-muted small mb-4">คุณต้องการดำเนินการต่อใช่หรือไม่?</p>
                    
                    <input type="hidden" id="targetOrderId">
                    <input type="hidden" id="targetStatus">

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light w-100 btn-round border" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-dark w-100 btn-round" id="confirmBtn" onclick="executeStatusUpdate()">ยืนยัน</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        const sModal = new bootstrap.Modal(document.getElementById('statusModal'));
        
        function confirmStatus(id, status, title) {
            document.getElementById('targetOrderId').value = id;
            document.getElementById('targetStatus').value = status;
            document.getElementById('statusTitle').innerText = title;
            
            const iconDiv = document.getElementById('modalIcon');
            const confirmBtn = document.getElementById('confirmBtn');

            // เปลี่ยนสี Modal ตามสถานะ (ถ้าเป็นยกเลิกให้ใช้สีแดง)
            if(status == -1) {
                iconDiv.innerHTML = '<i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>';
                confirmBtn.className = 'btn btn-danger w-100 btn-round';
            } else {
                iconDiv.innerHTML = '<i class="bi bi-question-circle text-primary" style="font-size: 3rem;"></i>';
                confirmBtn.className = 'btn btn-dark w-100 btn-round';
            }
            
            sModal.show();
        }

        async function executeStatusUpdate() {
            const id = document.getElementById('targetOrderId').value;
            const status = document.getElementById('targetStatus').value;
            
            try {
                const res = await fetch(`update_status.php?id=${id}&status=${status}`);
                const result = await res.text();
                
                if(result.trim() === "success") {
                    location.reload();
                } else {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถอัปเดตสถานะได้: ' + result, 'error');
                }
            } catch (e) {
                Swal.fire('ผิดพลาด', 'การเชื่อมต่อล้มเหลว', 'error');
            }
        }

        // รีเฟรชหน้าจอทุก 20 วินาที
        setInterval(() => { 
            if(!document.querySelector('.modal.show') && !Swal.isVisible()) { 
                location.reload(); 
            }
        }, 20000);
    </script>
</body>
</html>