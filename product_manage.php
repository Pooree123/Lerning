<?php
session_start();
require 'config.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// --- Logic: เพิ่มสินค้า ---
if (isset($_POST['add_product'])) {
    $name = $_POST['p_name']; $price = $_POST['p_price']; $cat = $_POST['cat_id']; $detail = $_POST['p_detail'];
    if (isset($_FILES['p_image']) && $_FILES['p_image']['error'] == 0) {
        $ext = pathinfo($_FILES['p_image']['name'], PATHINFO_EXTENSION);
        $new_name = uniqid('img_', true) . "." . $ext;
        if (move_uploaded_file($_FILES['p_image']['tmp_name'], "uploads/" . $new_name)) {
            $pdo->prepare("INSERT INTO products (p_name, p_price, cat_id, p_detail, p_image) VALUES (?,?,?,?,?)")
                ->execute([$name, $price, $cat, $detail, $new_name]);
            header("Location: product_manage.php?res=success"); exit();
        }
    }
}

// --- Logic: ลบสินค้า ---
if (isset($_GET['del_id'])) {
    $id = $_GET['del_id'];
    $stmt_img = $pdo->prepare("SELECT p_image FROM products WHERE p_id = ?");
    $stmt_img->execute([$id]);
    $row = $stmt_img->fetch();
    if ($row && $row['p_image']) {
        $file_path = "uploads/" . $row['p_image'];
        if (file_exists($file_path)) { unlink($file_path); }
    }
    $pdo->prepare("DELETE FROM products WHERE p_id = ?")->execute([$id]);
    header("Location: product_manage.php?res=deleted"); exit();
}

// --- Logic: เพิ่มหมวดหมู่ ---
if (isset($_POST['add_cat'])) {
    $pdo->prepare("INSERT INTO categories (cat_name) VALUES (?)")->execute([$_POST['cat_name']]);
    header("Location: product_manage.php?res=cat_added"); exit();
}

// --- Logic: ลบหมวดหมู่ ---
if (isset($_GET['del_cat_id'])) {
    $cid = $_GET['del_cat_id'];
    $stmt_imgs = $pdo->prepare("SELECT p_image FROM products WHERE cat_id = ?");
    $stmt_imgs->execute([cid]);
    $rows = $stmt_imgs->fetchAll();
    foreach ($rows as $row) {
        if ($row['p_image'] && file_exists("uploads/" . $row['p_image'])) { unlink("uploads/" . $row['p_image']); }
    }
    $pdo->prepare("DELETE FROM products WHERE cat_id = ?")->execute([$cid]);
    $pdo->prepare("DELETE FROM categories WHERE cat_id = ?")->execute([$cid]);
    header("Location: product_manage.php?res=cat_deleted"); exit();
}

$products = $pdo->query("SELECT p.*, c.cat_name FROM products p LEFT JOIN categories c ON p.cat_id = c.cat_id ORDER BY p.p_id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY cat_id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสต็อกสินค้า</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f8f9fa; overflow-x: hidden; }
        
        /* Layout สำหรับเนื้อหาหลัก */
        .main-content { 
            margin-left: 260px; 
            padding: 30px; 
            transition: 0.3s; 
        }

        /* ปรับปรุง Responsive สำหรับ Mobile */
        @media (max-width: 991px) { 
            .main-content { 
                margin-left: 0; 
                padding: 20px; 
            } 
        }

        .product-img { width: 55px; height: 55px; object-fit: cover; border-radius: 12px; }
        .card { border-radius: 20px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-control, .form-select { border-radius: 12px; padding: 10px 15px; border: 1px solid #eee; }
        
        .inline-confirm {
            max-height: 0; overflow: hidden; transition: all 0.3s ease-out;
            background: #fff8f8; border-radius: 15px; opacity: 0;
        }
        .inline-confirm.show { max-height: 300px; padding: 15px; margin-top: 10px; border: 1px solid #ffe3e3; opacity: 1; }
        .preview-container { background: #fdfdfd; border-radius: 15px; border: 2px dashed #ddd; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0">📦 จัดการสินค้า</h2>
            <div>
                <button class="btn btn-outline-dark rounded-pill px-3 me-2" data-bs-toggle="modal" data-bs-target="#catModal">📁 หมวดหมู่</button>
                <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProdModal">+ เพิ่มสินค้า</button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <input type="text" id="pSearch" class="form-control" placeholder="🔍 ค้นหาเมนู..." onkeyup="filterData()">
            </div>
            <div class="col-md-3">
                <select id="cFilter" class="form-select" onchange="filterData()">
                    <option value="">ทุกหมวดหมู่</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['cat_name']); ?>"><?php echo htmlspecialchars($cat['cat_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="card overflow-hidden">
            <table class="table align-middle mb-0" id="pTable">
                <thead class="bg-light text-muted small">
                    <tr><th class="ps-4">รูป</th><th>รายการ</th><th>หมวดหมู่</th><th>ราคา</th><th class="text-center">จัดการ</th></tr>
                </thead>
                <tbody>
                    <?php foreach($products as $p): ?>
                    <tr class="p-row">
                        <td class="ps-4">
                            <img src="uploads/<?php echo $p['p_image']; ?>" class="product-img shadow-sm border" onerror="this.src='https://via.placeholder.com/55'">
                        </td>
                        <td><div class="fw-bold p-name"><?php echo htmlspecialchars($p['p_name']); ?></div></td>
                        <td><span class="badge rounded-pill bg-light text-dark border p-cat"><?php echo htmlspecialchars($p['cat_name']); ?></span></td>
                        <td class="fw-bold text-primary">฿<?php echo number_format($p['p_price'], 2); ?></td>
                        <td class="text-center">
                            <a href="product_edit.php?id=<?php echo $p['p_id']; ?>" class="btn btn-sm btn-light rounded-3 px-3 me-1">แก้ไข</a>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 px-3" 
                                onclick="if(confirm('ยืนยันการลบสินค้านี้?')) window.location.href='?del_id=<?php echo $p['p_id']; ?>'">ลบ</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal fade" id="addProdModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <form action="" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold">✨ เพิ่มสินค้าใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6 border-end pe-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">ชื่อเมนู</label>
                                <input type="text" name="p_name" id="inputName" class="form-control rounded-3" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">หมวดหมู่</label>
                                <select name="cat_id" class="form-select rounded-3" required>
                                    <option value="" selected disabled>เลือกหมวดหมู่...</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['cat_id']; ?>"><?php echo htmlspecialchars($cat['cat_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">ราคา (บาท)</label>
                                <input type="number" name="p_price" id="inputPrice" class="form-control rounded-3" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">รายละเอียด</label>
                                <textarea name="p_detail" id="inputDetail" class="form-control rounded-3" rows="3"></textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold">รูปภาพ</label>
                                <input type="file" name="p_image" id="inputImage" class="form-control rounded-3" accept="image/*" required onchange="previewFile()">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex flex-column align-items-center justify-content-center preview-container py-5">
                            <div class="card text-center border-0 shadow-sm" style="width: 240px; border-radius: 20px; overflow: hidden;">
                                <div style="height: 160px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                    <img id="previewImg" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                                    <span id="previewPlaceholder">📷</span>
                                </div>
                                <div class="card-body p-3 bg-white border-top">
                                    <h6 class="fw-bold mb-1" id="previewName">ชื่อสินค้า</h6>
                                    <p class="text-muted small mb-2" id="previewDetail" style="height: 35px; overflow: hidden;">รายละเอียด...</p>
                                    <h5 class="fw-bold text-primary mb-0" id="previewPrice">฿0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="add_product" class="btn btn-dark px-5 rounded-3 fw-bold shadow">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="catModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold">📂 หมวดหมู่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <form action="" method="POST" class="d-flex gap-2 mb-4">
                        <input type="text" name="cat_name" class="form-control" placeholder="ชื่อใหม่..." required>
                        <button type="submit" name="add_cat" class="btn btn-dark">เพิ่ม</button>
                    </form>
                    
                    <div class="list-group list-group-flush border rounded-4 overflow-hidden">
                        <?php foreach($categories as $cat): 
                            $count = $pdo->query("SELECT COUNT(*) FROM products WHERE cat_id = ".$cat['cat_id'])->fetchColumn();
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold d-block"><?php echo htmlspecialchars($cat['cat_name']); ?></span>
                                    <small class="text-muted">สินค้า <?php echo $count; ?> รายการ</small>
                                </div>
                                <button type="button" class="btn btn-sm text-danger border-0" onclick="toggleInlineConfirm(<?php echo $cat['cat_id']; ?>)">ลบ</button>
                            </div>

                            <div id="confirm-<?php echo $cat['cat_id']; ?>" class="inline-confirm">
                                <p class="text-danger small fw-bold mb-1">⚠️ คำเตือน!</p>
                                <p class="text-muted mb-3" style="font-size: 0.75rem;">หากลบหมวดนี้ สินค้าทั้ง <?php echo $count; ?> รายการจะถูกลบถาวร</p>
                                <div class="d-flex gap-2">
                                    <a href="?del_cat_id=<?php echo $cat['cat_id']; ?>" class="btn btn-danger btn-sm w-100 rounded-3 text-white text-decoration-none text-center">ยืนยันลบ</a>
                                    <button type="button" class="btn btn-light btn-sm w-100 rounded-3" onclick="toggleInlineConfirm(<?php echo $cat['cat_id']; ?>)">ยกเลิก</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันค้นหาและกรอง
        function filterData() {
            let search = document.getElementById('pSearch').value.toLowerCase();
            let cat = document.getElementById('cFilter').value;
            document.querySelectorAll('.p-row').forEach(row => {
                let name = row.querySelector('.p-name').innerText.toLowerCase();
                let category = row.querySelector('.p-cat').innerText;
                row.style.display = (name.includes(search) && (cat === "" || category === cat)) ? "" : "none";
            });
        }

        // ส่วนของการ Preview รูปภาพ
        function previewFile() {
            const preview = document.getElementById('previewImg');
            const placeholder = document.getElementById('previewPlaceholder');
            const file = document.getElementById('inputImage').files[0];
            const reader = new FileReader();
            reader.onloadend = function () {
                preview.src = reader.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            }
            if (file) { reader.readAsDataURL(file); }
        }

        // Live Preview ข้อมูล
        document.getElementById('inputName').addEventListener('input', function() {
            document.getElementById('previewName').innerText = this.value || 'ชื่อสินค้า';
        });
        document.getElementById('inputPrice').addEventListener('input', function() {
            document.getElementById('previewPrice').innerText = '฿' + (parseFloat(this.value) || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
        });
        document.getElementById('inputDetail').addEventListener('input', function() {
            document.getElementById('previewDetail').innerText = this.value || 'รายละเอียด...';
        });
    </script>
</body>
</html>