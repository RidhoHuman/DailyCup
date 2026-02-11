<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Edit Product';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: ' . SITE_URL . '/admin/products/');
    exit;
}

// Get product data
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . SITE_URL . '/admin/products/');
    exit;
}

$categories = getCategories(false);

// Get detail images
$stmtImages = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order");
$stmtImages->execute([$productId]);
$detailImages = $stmtImages->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = sanitizeInput($_POST['description'] ?? '');
        $basePrice = floatval($_POST['base_price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || $categoryId == 0 || $basePrice <= 0) {
            $error = 'Please fill all required fields';
        } else {
            // Handle main image upload
            $imagePath = $product['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/images/products/';
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('prod_') . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // Delete old image if exists
                    if ($product['image'] && file_exists($uploadDir . $product['image'])) {
                        unlink($uploadDir . $product['image']);
                    }
                    $imagePath = $fileName;
                }
            }

            $stmt = $db->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, base_price = ?, stock = ?, image = ?, is_featured = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            
            if ($stmt->execute([$categoryId, $name, $description, $basePrice, $stock, $imagePath, $isFeatured, $isActive, $productId])) {
                // Clear cache for updated product list
                clearAllCache();
                
                // Handle new detail images upload
                if (isset($_FILES['detail_images'])) {
                    $uploadDir = __DIR__ . '/../../assets/images/products/';
                    foreach ($_FILES['detail_images']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['detail_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileExtension = pathinfo($_FILES['detail_images']['name'][$key], PATHINFO_EXTENSION);
                            $fileName = uniqid('detail_') . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($tmpName, $targetPath)) {
                                $stmtImg = $db->prepare("INSERT INTO product_images (product_id, image_path, display_order) VALUES (?, ?, ?)");
                                $stmtImg->execute([$productId, $fileName, $key]);
                            }
                        }
                    }
                }

                // Handle detail image deletion
                if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                    $uploadDir = __DIR__ . '/../../assets/images/products/';
                    foreach ($_POST['delete_images'] as $imgId) {
                        $stmtGetImg = $db->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
                        $stmtGetImg->execute([$imgId, $productId]);
                        $imgToDelete = $stmtGetImg->fetch();
                        
                        if ($imgToDelete) {
                            if (file_exists($uploadDir . $imgToDelete['image_path'])) {
                                unlink($uploadDir . $imgToDelete['image_path']);
                            }
                            $stmtDelImg = $db->prepare("DELETE FROM product_images WHERE id = ?");
                            $stmtDelImg->execute([$imgId]);
                        }
                    }
                }

                $success = 'Product updated successfully';
                // Refresh product data
                $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $product = $stmt->fetch();
            } else {
                $error = 'Failed to update product';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title"><i class="bi bi-pencil"></i> Edit Product</h1>
            <a href="<?php echo SITE_URL; ?>/admin/products/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Product Name *</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Base Price (Rp) *</label>
                                <input type="number" name="base_price" class="form-control" required 
                                       value="<?php echo $product['base_price']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Stock *</label>
                                <input type="number" name="stock" class="form-control" required 
                                       value="<?php echo $product['stock']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" 
                                           <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_featured">Featured Product</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                           <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active / Visible</label>
                                </div>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label class="form-label">Main Product Image</label>
                                <?php if ($product['image']): ?>
                                    <div class="mb-2">
                                        <?php 
                                        $imgSrc = SITE_URL . '/assets/images/products/' . $product['image'];
                                        if (strpos($product['image'], 'uploads/') !== false) {
                                            $imgSrc = SITE_URL . '/webapp/' . ltrim($product['image'], '/');
                                        }
                                        ?>
                                        <img src="<?php echo $imgSrc; ?>" class="img-thumbnail" style="max-height: 150px;">
                                    </div>
                                <?php endif; ?>
                                <div class="image-upload-area" onclick="document.getElementById('mainImage').click()">
                                    <i class="bi bi-cloud-upload"></i>
                                    <p id="mainImageText"><?php echo $product['image'] ? 'Change main image' : 'Click to upload main image'; ?></p>
                                    <input type="file" name="image" id="mainImage" accept="image/*" class="d-none" onchange="updateFileName(this, 'mainImageText')">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Photos (Gallery)</label>
                                <?php if (!empty($detailImages)): ?>
                                    <div class="row g-2 mb-3">
                                        <?php foreach ($detailImages as $img): ?>
                                            <div class="col-4 position-relative">
                                                <?php 
                                                $gallerySrc = SITE_URL . '/assets/images/products/' . $img['image_path'];
                                                if (strpos($img['image_path'], 'uploads/') !== false) {
                                                    $gallerySrc = SITE_URL . '/webapp/' . ltrim($img['image_path'], '/');
                                                }
                                                ?>
                                                <img src="<?php echo $gallerySrc; ?>" class="img-thumbnail">
                                                <div class="form-check position-absolute top-0 end-0 bg-white rounded-circle p-1 shadow-sm" style="margin: 5px;">
                                                    <input class="form-check-input" type="checkbox" name="delete_images[]" value="<?php echo $img['id']; ?>" title="Delete image">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-danger d-block mb-2"><i class="bi bi-trash"></i> Check to delete existing photos</small>
                                <?php endif; ?>
                                
                                <div class="image-upload-area" onclick="document.getElementById('detailImages').click()" style="border-style: dashed; background: #f8f9fa;">
                                    <i class="bi bi-images"></i>
                                    <p id="detailImagesText">Add more detail photos</p>
                                    <input type="file" name="detail_images[]" id="detailImages" accept="image/*" multiple class="d-none" onchange="updateFileNames(this, 'detailImagesText')">
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-coffee btn-lg">
                                    <i class="bi bi-save"></i> Update Product
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
function updateFileName(input, textId) {
    const textElement = document.getElementById(textId);
    if (input.files && input.files[0]) {
        textElement.textContent = input.files[0].name;
    }
}

function updateFileNames(input, textId) {
    const textElement = document.getElementById(textId);
    if (input.files && input.files.length > 0) {
        textElement.textContent = input.files.length + ' files selected';
    }
}
</script>
