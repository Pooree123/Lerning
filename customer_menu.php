<?php
session_start();
require 'config.php';

// ดึงข้อมูลหมวดหมู่และสินค้า
$categories = $pdo->query("SELECT * FROM categories ORDER BY cat_id ASC")->fetchAll();
$products = $pdo->query("SELECT p.*, c.cat_name FROM products p LEFT JOIN categories c ON p.cat_id = c.cat_id ORDER BY p.p_id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Minimal Cafe</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap');
        body { font-family: 'Kanit', sans-serif; background-color: #fdfaf7; padding-bottom: 120px; }
        .product-card { border: none; border-radius: 25px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.04); transition: 0.3s; height: 100%; background: white; cursor: pointer; }
        .product-card:active { transform: scale(0.95); }
        .product-img { height: 160px; object-fit: cover; width: 100%; }
        .cat-badge { cursor: pointer; white-space: nowrap; transition: 0.3s; border: 1px solid #eee; background: white; color: #666; padding: 8px 20px !important; }
        .cat-badge.active { background: #8d7b68 !important; color: white !important; border-color: #8d7b68; }
        .cart-floating { position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 450px; z-index: 1000; }
        .btn-cart-main { background: #212529; color: white; border-radius: 20px; padding: 18px; border: none; width: 100%; font-weight: 600; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .status-box { border-radius: 20px; background: white; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body>

<div class="container py-4">
    <div id="statusArea" class="mb-4"></div>

    <header class="text-center mb-4">
        <h3 class="fw-bold mb-1">MINIMAL CAFE</h3>
        <p class="text-muted small">เลือกเมนูที่ต้องการและกดสั่งได้เลยครับ</p>
    </header>

    <div class="d-flex gap-2 overflow-auto pb-3 mb-3 no-scrollbar">
        <span class="badge rounded-pill cat-badge active" onclick="filterCat('all', this)">ทั้งหมด</span>
        <?php foreach($categories as $cat): ?>
            <span class="badge rounded-pill cat-badge" onclick="filterCat('<?= $cat['cat_name'] ?>', this)"><?= $cat['cat_name'] ?></span>
        <?php endforeach; ?>
    </div>

    <div class="row g-3" id="menuGrid">
        <?php foreach($products as $p): ?>
        <div class="col-6 col-md-4 product-item" data-cat="<?= $p['cat_name'] ?>">
            <div class="card product-card" onclick="addToCart(<?= $p['p_id'] ?>, '<?= $p['p_name'] ?>', <?= $p['p_price'] ?>)">
                <img src="uploads/<?= $p['p_image'] ?>" class="product-img" onerror="this.src='https://via.placeholder.com/200x160?text=No+Image'">
                <div class="card-body p-3 text-center">
                    <h6 class="fw-bold mb-1 text-truncate"><?= $p['p_name'] ?></h6>
                    <span class="text-dark fw-bold">฿<?= number_format($p['p_price'], 2) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="cart-floating" id="cartSection" style="display: none;">
    <button class="btn-cart-main" data-bs-toggle="modal" data-bs-target="#cartModal">
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-bag-check-fill me-2"></i> ตะกร้าของคุณ (<span id="cartCount">0</span>)</span>
            <span id="cartTotal">฿0.00</span>
        </div>
    </button>
</div>

<div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 25px;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="fw-bold m-0">รายการที่สั่ง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="cartItems"></div>
                <hr class="my-4" style="border-style: dashed;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">ยอดรวมทั้งสิ้น</span>
                    <h4 class="fw-bold text-dark mb-0" id="modalTotalText">฿0.00</h4>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button class="btn btn-dark w-100 py-3 rounded-4 fw-bold shadow-sm" onclick="confirmOrder()">ยืนยันส่งออเดอร์</button>
            </div>
        </div>
    </div>
</div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    let cart = [];
    // สร้างตัวแปรเก็บ Order ID ที่เคยแจ้งเตือนว่าเสร็จไปแล้ว เพื่อป้องกัน Swal เด้งซ้ำ
    window.notifiedOrders = window.notifiedOrders || [];

    function filterCat(cat, btn) {
        document.querySelectorAll('.cat-badge').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.product-item').forEach(item => {
            item.style.display = (cat === 'all' || item.dataset.cat === cat) ? 'block' : 'none';
        });
    }

    function addToCart(id, name, price) {
        let found = cart.find(item => item.id === id);
        if (found) { found.qty++; } else { cart.push({ id, name, price, qty: 1 }); }
        updateCartUI();
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 800 });
        Toast.fire({ icon: 'success', title: 'เพิ่มเมนูแล้ว' });
    }

    function updateCartUI() {
        const cartItems = document.getElementById('cartItems');
        const cartSection = document.getElementById('cartSection');
        let total = 0, count = 0;
        if (cart.length === 0) { cartSection.style.display = 'none'; return; }
        cartSection.style.display = 'block';
        let html = '';
        cart.forEach((item, index) => {
            total += item.price * item.qty;
            count += item.qty;
            html += `<div class="d-flex justify-content-between align-items-center mb-3">
                <div style="flex: 1;"><h6 class="mb-0 fw-bold">${item.name}</h6><small class="text-muted">฿${item.price.toFixed(2)} x ${item.qty}</small></div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-dark px-2" onclick="changeQty(${index}, -1)">-</button>
                    <button class="btn btn-dark px-2" disabled>${item.qty}</button>
                    <button class="btn btn-outline-dark px-2" onclick="changeQty(${index}, 1)">+</button>
                </div>
            </div>`;
        });
        cartItems.innerHTML = html;
        document.getElementById('modalTotalText').innerText = '฿' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('cartTotal').innerText = '฿' + total.toLocaleString();
        document.getElementById('cartCount').innerText = count;
    }

    function changeQty(index, delta) {
        cart[index].qty += delta;
        if (cart[index].qty <= 0) cart.splice(index, 1);
        updateCartUI();
        if(cart.length === 0) bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
    }

    async function confirmOrder() {
        if (cart.length === 0) return;
        Swal.fire({ title: 'กำลังส่งออเดอร์...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        try {
            const res = await fetch('save_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ total: total, cart: cart, staff_id: 0, order_status: 0 })
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire({ icon: 'success', title: 'สั่งอาหารสำเร็จ!', html: `เลขออเดอร์ของคุณคือ <br><h2 class="fw-bold mt-2 text-primary">${result.order_id}</h2>`, confirmButtonText: 'ตกลง' })
                .then(() => { cart = []; location.reload(); });
            }
        } catch (error) { Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'); }
    }

    async function updateStatusUI() {
        try {
            const res = await fetch('check_order_status.php');
            const data = await res.json();
            
            const statusContainer = document.getElementById('statusArea'); 
            if (!statusContainer) return;

            if (!data.orders || data.orders.length === 0) {
                statusContainer.innerHTML = '';
                return;
            }

            let html = '';

            data.orders.forEach(order => {
                let badgeClass = 'bg-warning text-dark';
                let statusText = 'รอรับรายการ...';
                
                if(order.status == 1) { 
                    badgeClass = 'bg-primary text-white'; 
                    statusText = 'กำลังปรุง...'; 
                } else if(order.status == 2) { 
                    badgeClass = 'bg-success text-white shadow-sm'; 
                    statusText = 'เสร็จแล้ว!'; 
                    
                    // ระบบแจ้งเตือนรายออเดอร์: ตรวจสอบว่าเคยแจ้งเตือน Order ID นี้ไปหรือยัง
                    if (!window.notifiedOrders.includes(order.order_id)) {
                        new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3').play();
                        Swal.fire({
                            title: '✨ อาหารเสร็จแล้ว!',
                            html: `ออเดอร์หมายเลข <b class="text-primary" style="font-size: 1.5rem;">#${order.order_id}</b> <br>ทำเสร็จเรียบร้อยแล้วครับ!`,
                            icon: 'success',
                            confirmButtonText: 'รับทราบ',
                            confirmButtonColor: '#8d7b68'
                        });
                        window.notifiedOrders.push(order.order_id); // บันทึกว่าแจ้งเตือนแล้ว
                    }
                }

                html += `
                    <div class="status-box p-3 mb-2 shadow-sm border-0 bg-white animate__animated animate__fadeIn">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block small">ออเดอร์ #${order.order_id}</small>
                                <span class="badge ${badgeClass} rounded-pill p-2 px-3">${statusText}</span>
                            </div>
                            <div class="text-end">
                                ${order.status < 2 && order.queue_before > 0 ? 
                                `<small class="text-muted d-block" style="font-size:0.75rem;"><i class="bi bi-people"></i> อีก ${order.queue_before} คิว</small>` : ''}
                                ${order.status == 2 ? `<small class="text-success fw-bold">กรุณารับอาหาร</small>` : ''}
                            </div>
                        </div>
                    </div>`;
            });
            
            statusContainer.innerHTML = html;
        } catch (e) { console.log("Status check error"); }
    }

    setInterval(updateStatusUI, 5000);
    updateStatusUI();
</script>
</body>
</html>