<?php
// 1. เริ่ม Session และโหลดการตั้งค่าฐานข้อมูล
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php'; 

// ตรวจสอบสิทธิ์การเข้าใช้งาน
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// 2. ดึงข้อมูลหมวดหมู่และสินค้าสำหรับแสดงหน้า POS
$categories = $pdo->query("SELECT * FROM categories ORDER BY cat_id ASC")->fetchAll();
$products = $pdo->query("SELECT p.*, c.cat_name FROM products p LEFT JOIN categories c ON p.cat_id = c.cat_id ORDER BY p.p_id DESC")->fetchAll();

// 3. ส่วนดึงข้อมูลออเดอร์จากลูกค้า (ดึงเมื่อมี pay_id ส่งมา)
$pay_id = isset($_GET['pay_id']) ? $_GET['pay_id'] : '';
$pre_items = [];

if ($pay_id != '') {
    $stmt = $pdo->prepare("SELECT od.*, p.p_name, p.p_price FROM order_details od 
                           JOIN products p ON od.product_id = p.p_id 
                           WHERE od.order_id = ?");
    $stmt->execute([$pay_id]);
    $pre_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe POS - ระบบขายหน้าร้าน</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap');
        body { font-family: 'Kanit', sans-serif; background-color: #fcfcfc; overflow-x: hidden; }
        .main-content { margin-left: 260px; padding: 30px; transition: 0.3s; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 20px; } }
        .product-card { border: none; border-radius: 15px; transition: 0.2s; background: #fff; cursor: pointer; box-shadow: 0 4px 8px rgba(0,0,0,0.05); height: 100%; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .product-img { height: 140px; background: #f8f9fa; border-radius: 15px 15px 0 0; overflow: hidden; }
        .product-img img { width: 100%; height: 100%; object-fit: cover; }
        .order-summary { background: white; border-radius: 20px; border: 1px solid #eee; padding: 20px; position: sticky; top: 25px; height: calc(100vh - 50px); display: flex; flex-direction: column; }
        .order-items-scroll { flex-grow: 1; overflow-y: auto; padding-right: 5px; }
        .modal-content { border-radius: 25px; border: none; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <header class="mb-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-4">
                                <h3 class="fw-bold m-0 text-dark">เมนูสินค้า</h3>
                            </div>
                            <div class="col-md-8 text-end">
                                <div class="input-group shadow-sm rounded-pill overflow-hidden">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control border-0" id="searchInput" placeholder="ค้นหาชื่อเมนู..." onkeyup="filterProducts()">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 overflow-auto pb-2 mt-3 no-scrollbar">
                            <button class="btn btn-dark rounded-pill px-4 cat-btn active" onclick="filterByCategory('all', this)">ทั้งหมด</button>
                            <?php foreach($categories as $cat): ?>
                                <button class="btn btn-outline-dark rounded-pill px-4 cat-btn" 
                                        onclick="filterByCategory('<?= htmlspecialchars($cat['cat_name']) ?>', this)">
                                    <?= htmlspecialchars($cat['cat_name']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </header>

                    <div class="row g-3" id="productGrid">
                        <?php foreach($products as $p): ?>
                        <div class="col-6 col-md-4 col-xl-3 product-item" 
                             data-name="<?= htmlspecialchars(mb_strtolower($p['p_name'])) ?>" 
                             data-category="<?= htmlspecialchars($p['cat_name']) ?>">
                            <div class="card product-card text-center" 
                                 onclick="openProductModal(<?= htmlspecialchars(json_encode([
                                     'id' => $p['p_id'],
                                     'name' => $p['p_name'],
                                     'description' => $p['p_detail'],
                                     'price' => (float)$p['p_price'],
                                     'image' => 'uploads/'.$p['p_image']
                                 ])) ?>)">
                                <div class="product-img">
                                    <img src="uploads/<?= $p['p_image'] ?>" onerror="this.src='https://via.placeholder.com/200x150?text=No+Image'">
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-1 text-truncate"><?= $p['p_name'] ?></h6>
                                    <span class="text-primary fw-bold">฿<?= number_format($p['p_price'], 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="order-summary shadow-sm">
                        <input type="hidden" id="current_order_id" value="<?= htmlspecialchars($pay_id) ?>">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold m-0 text-dark">รายการสั่งซื้อ <?= ($pay_id != '') ? "<small class='text-primary'>(#$pay_id)</small>" : "" ?></h5>
                            <button class="btn btn-sm btn-outline-danger border-0" onclick="clearCart()">ล้างรายการ</button>
                        </div>
                        
                        <div class="order-items-scroll" id="cartItems">
                            <div class="text-center text-muted mt-5">ยังไม่มีรายการสินค้า</div>
                        </div>

                        <div class="border-top pt-3 mt-auto">
                            <div class="d-flex justify-content-between mb-3">
                                <h4 class="fw-bold">ยอดสุทธิ</h4>
                                <h4 class="fw-bold text-success" id="netAmount">฿0.00</h4>
                            </div>
                            <button class="btn btn-dark w-100 py-3 rounded-4 fw-bold shadow" onclick="checkout()">ชำระเงิน</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="productDetailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pb-4">
                    <img id="modalImg" src="" class="mb-3 rounded-4 shadow-sm w-100" style="height: 180px; object-fit: cover;">
                    <h4 class="fw-bold mb-1" id="modalName"></h4>
                    <p class="text-muted small mb-3" id="modalDesc"></p>
                    <h3 class="fw-bold text-primary mb-4" id="modalPrice"></h3>
                    <div class="d-flex justify-content-center align-items-center mb-4 bg-light rounded-pill py-2">
                        <button class="btn btn-link text-dark text-decoration-none fw-bold fs-4 px-3" onclick="updateModalQty(-1)">-</button>
                        <input type="number" class="form-control text-center border-0 bg-transparent fw-bold fs-5 p-0" style="width: 50px;" value="1" id="modalQty" readonly>
                        <button class="btn btn-link text-dark text-decoration-none fw-bold fs-4 px-3" onclick="updateModalQty(1)">+</button>
                    </div>
                    <button class="btn btn-dark w-100 py-3 rounded-4 fw-bold shadow" onclick="addCurrentToCart()">เพิ่มลงรายการ</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow-lg">
                <div class="modal-body text-center p-4">
                    <h5 class="fw-bold mb-3">สแกนเพื่อชำระเงิน</h5>
                    <div class="bg-white p-2 mb-3 rounded-4 shadow-sm">
                        <img id="qrImage" src="" class="w-100 rounded-3">
                    </div>
                    <h3 class="fw-bold text-success mb-4" id="payAmount">฿0.00</h3>
                    <div class="d-grid gap-2">
                        <button class="btn btn-dark py-3 rounded-4 fw-bold shadow" onclick="confirmPayment()">ยืนยันการชำระเงิน</button>
                        <button class="btn btn-light py-2 rounded-4 text-muted border-0" data-bs-dismiss="modal">ยกเลิก</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];
        let currentProduct = null;
        let currentCategory = 'all';
        const prodModal = new bootstrap.Modal(document.getElementById('productDetailModal'));
        const payModal = new bootstrap.Modal(document.getElementById('paymentModal'));

        // ดึงข้อมูลออเดอร์จากลูกค้ามาใส่ตะกร้า (ถ้ามี pay_id)
        document.addEventListener("DOMContentLoaded", function() {
            const preItems = <?php echo json_encode($pre_items); ?>;
            if (preItems.length > 0) {
                cart = preItems.map(item => ({
                    id: parseInt(item.product_id),
                    name: item.p_name,
                    price: parseFloat(item.p_price),
                    qty: parseInt(item.qty),
                    image: '' // ไม่จำเป็นสำหรับการแสดงผลในตะกร้า
                }));
                renderCart();
            }
        });

        function filterProducts() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.product-item');
            items.forEach(item => {
                const name = item.getAttribute('data-name');
                const cat = item.getAttribute('data-category');
                item.style.display = (name.includes(input) && (currentCategory === 'all' || cat === currentCategory)) ? 'block' : 'none';
            });
        }

        function filterByCategory(category, btn) {
            currentCategory = category;
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.replace('btn-dark', 'btn-outline-dark'));
            btn.classList.replace('btn-outline-dark', 'btn-dark');
            filterProducts();
        }

        function openProductModal(product) {
            currentProduct = product;
            document.getElementById('modalName').innerText = product.name;
            document.getElementById('modalDesc').innerText = product.description || '-';
            document.getElementById('modalPrice').innerText = '฿' + product.price.toFixed(2);
            document.getElementById('modalImg').src = product.image;
            document.getElementById('modalQty').value = 1;
            prodModal.show();
        }

        function updateModalQty(change) {
            let input = document.getElementById('modalQty');
            let val = parseInt(input.value) + change;
            if(val >= 1) input.value = val;
        }

        function addCurrentToCart() {
            const qty = parseInt(document.getElementById('modalQty').value);
            const existing = cart.find(item => item.id === currentProduct.id);
            if (existing) { existing.qty += qty; } 
            else { cart.push({ ...currentProduct, qty: qty }); }
            prodModal.hide();
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            if (cart.length === 0) {
                container.innerHTML = '<div class="text-center text-muted mt-5">ยังไม่มีรายการสินค้า</div>';
                document.getElementById('netAmount').innerText = '฿0.00';
                return;
            }
            let html = '', total = 0;
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.qty;
                total += itemTotal;
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div style="flex: 1">
                            <h6 class="mb-0 fw-bold small">${item.name}</h6>
                            <small class="text-muted">฿${item.price.toFixed(2)} x ${item.qty}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="fw-bold me-2 small">฿${itemTotal.toFixed(2)}</span>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-light border-0" onclick="updateCartQty(${index}, -1)">-</button>
                                <button class="btn btn-light border-0" onclick="updateCartQty(${index}, 1)">+</button>
                            </div>
                        </div>
                    </div>`;
            });
            container.innerHTML = html;
            document.getElementById('netAmount').innerText = '฿' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
        }

        function updateCartQty(index, change) {
            cart[index].qty += change;
            if (cart[index].qty < 1) cart.splice(index, 1);
            renderCart();
        }

        function clearCart() { 
            if(confirm('ต้องการล้างรายการทั้งหมด?')) { 
                // 1. ล้างข้อมูลในตัวแปรตะกร้า
                cart = []; 
                
                // 2. ล้างค่า ID ออเดอร์ลูกค้าใน Input Hidden
                if(document.getElementById('current_order_id')) {
                    document.getElementById('current_order_id').value = ''; 
                }

                // 3. อัปเดตการแสดงผลตะกร้า (ให้แสดงว่าว่าง)
                renderCart(); 

                window.location.href = 'index.php';
            } 
        }

        function checkout() {
            if (cart.length === 0) return;
            let total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            const promptPayId = "0840111767"; 
            document.getElementById('qrImage').src = `https://promptpay.io/${promptPayId}/${total.toFixed(2)}.png`;
            document.getElementById('payAmount').innerText = '฿' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
            payModal.show();
        }

        async function confirmPayment() {
            const orderData = {
                order_id: document.getElementById('current_order_id').value, // ส่งเลขเดิมไปอัปเดตถ้ามี
                total: cart.reduce((sum, item) => sum + (item.price * item.qty), 0),
                cart: cart
            };

            try {
                const response = await fetch('save_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });
                const result = await response.json();
                
                if(result.success) {
                    payModal.hide();
                    alert('ชำระเงินเรียบร้อย! บันทึกใบเสร็จ #' + result.order_id);
                    cart = []; 
                    document.getElementById('current_order_id').value = ''; // ล้าง ID ทิ้ง
                    renderCart();
                    // ถ้ามาจากออเดอร์ลูกค้า ให้กลับไปหน้ารับออเดอร์
                    if(orderData.order_id != '') { window.location.href = 'staff_order_view.php'; }
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (e) {
                alert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            }
        }
    </script>
</body>
</html>