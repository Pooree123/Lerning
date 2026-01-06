<?php
session_start();
require 'config.php';

if (!isset($_SESSION['staff_id'])) { header("Location: login.php"); exit(); }

// 1. ดึงข้อมูลสินค้าเดิมมาแสดง
if (isset($_GET['id'])) {
    $p_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE p_id = ?");
    $stmt->execute([$p_id]);
    $product = $stmt->fetch();

    if (!$product) {
        header("Location: product_manage.php");
        exit();
    }
} else {
    header("Location: product_manage.php");
    exit();
}

// 2. ดึงหมวดหมู่ทั้งหมดสำหรับ Select Box
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// 3. ส่วนประมวลผลการอัปเดตข้อมูล
if (isset($_POST['update_product'])) {
    $name = $_POST['p_name'];
    $price = $_POST['p_price'];
    $cat = $_POST['cat_id'];
    $detail = $_POST['p_detail'];
    $new_image = $_FILES['p_image']['name'];
    $old_image = $_POST['old_image'];

    if ($new_image != "") {
        // กรณี "เปลี่ยนรูปใหม่"
        $file_extension = pathinfo($new_image, PATHINFO_EXTENSION);
        $file_name = uniqid('img_', true) . "." . $file_extension;
        $upload_path = "uploads/" . $file_name;

        if (move_uploaded_file($_FILES['p_image']['tmp_name'], $upload_path)) {
            // ลบรูปเก่าออกจากโฟลเดอร์ (ถ้ามี)
            if ($old_image != "" && file_exists("uploads/" . $old_image)) {
                unlink("uploads/" . $old_image);
            }
        }
    } else {
        // กรณี "ไม่เปลี่ยนรูป" ใช้ชื่อไฟล์เดิม
        $file_name = $old_image;
    }

    $sql = "UPDATE products SET p_name=?, p_price=?, cat_id=?, p_detail=?, p_image=? WHERE p_id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $price, $cat, $detail, $file_name, $p_id]);
    
    header("Location: product_manage.php?update_success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า - Minimal Cafe</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f8f9fa; }
        .main-content { margin-left: 260px; padding: 30px; }
        .preview-container { background: #fff; border-radius: 20px; border: 1px solid #eee; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="d-flex align-items-center mb-4">
                <a href="product_manage.php" class="btn btn-light rounded-circle me-3">←</a>
                <h2 class="fw-bold m-0">🛠️ แก้ไขข้อมูลสินค้า</h2>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row g-5">
                        <div class="col-md-7">
                            <input type="hidden" name="old_image" value="<?php echo $product['p_image']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">ชื่อสินค้า</label>
                                <input type="text" name="p_name" id="inputName" class="form-control" value="<?php echo htmlspecialchars($product['p_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">หมวดหมู่</label>
                                <select name="cat_id" class="form-select" required>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['cat_id']; ?>" <?php echo ($cat['cat_id'] == $product['cat_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['cat_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">ราคา (บาท)</label>
                                <input type="number" name="p_price" id="inputPrice" class="form-control" step="0.01" value="<?php echo $product['p_price']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">รายละเอียด</label>
                                <textarea name="p_detail" id="inputDetail" class="form-control" rows="4"><?php echo htmlspecialchars($product['p_detail']); ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-bold">เปลี่ยนรูปภาพ (ปล่อยว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                                <input type="file" name="p_image" id="inputImage" class="form-control" accept="image/*" onchange="previewFile()">
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="preview-container p-4 text-center h-100 d-flex flex-column justify-content-center">
                                <p class="text-muted small fw-bold mb-4 text-uppercase">ตัวอย่างการแสดงผลปัจจุบัน</p>
                                
                                <div class="card mx-auto border-0 shadow-sm" style="width: 240px; border-radius: 20px; overflow: hidden;">
                                    <div style="height: 180px; background: #f0f0f0;">
                                        <img id="previewImg" src="uploads/<?php echo $product['p_image']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="card-body p-3 bg-white">
                                        <h6 class="fw-bold mb-1" id="previewName"><?php echo htmlspecialchars($product['p_name']); ?></h6>
                                        <p class="text-muted small mb-2" id="previewDetail" style="height: 35px; overflow: hidden;"><?php echo htmlspecialchars($product['p_detail']); ?></p>
                                        <h5 class="fw-bold text-primary mb-0" id="previewPrice">฿<?php echo number_format($product['p_price'], 2); ?></h5>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" name="update_product" class="btn btn-dark w-100 py-3 rounded-3 fw-bold shadow">บันทึกการแก้ไข</button>
                                    <a href="product_manage.php" class="btn btn-link text-muted mt-2 w-100">ยกเลิก</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Live Preview เหมือนหน้าเพิ่มสินค้า
        function previewFile() {
            const preview = document.getElementById('previewImg');
            const file = document.getElementById('inputImage').files[0];
            const reader = new FileReader();

            reader.onloadend = function () {
                preview.src = reader.result;
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('inputName').addEventListener('input', function() {
            document.getElementById('previewName').innerText = this.value || 'ชื่อสินค้า';
        });

        document.getElementById('inputPrice').addEventListener('input', function() {
            let val = parseFloat(this.value) || 0;
            document.getElementById('previewPrice').innerText = '฿' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
        });

        document.getElementById('inputDetail').addEventListener('input', function() {
            document.getElementById('previewDetail').innerText = this.value || 'รายละเอียดสินค้า...';
        });
    </script>
</body>
</html>