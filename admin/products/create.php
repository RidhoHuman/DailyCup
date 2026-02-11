<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Add Product';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$categories = getCategories(false);

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
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/images/products/';
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('prod_') . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = $fileName;
                }
            }

            $stmt = $db->prepare("INSERT INTO products (category_id, name, description, base_price, stock, image, is_featured, is_active) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$categoryId, $name, $description, $basePrice, $stock, $imagePath, $isFeatured, $isActive])) {
                $productId = $db->lastInsertId();
                
                // Clear cache for new product
                clearAllCache();

                // Handle detail images upload
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

                header('Location: ' . SITE_URL . '/admin/products/');
                exit;
            } else {
                $error = 'Failed to create product';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-plus-circle"></i> Add New Product</h1>
            <a href="<?php echo SITE_URL; ?>/admin/products/" class="btn btn-outline-coffee">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="admin-form">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Base Price (Rp) *</label>
                                <input type="number" name="base_price" class="form-control" step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" name="stock" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Main Product Image</label>
                            <div class="image-upload-area" onclick="document.getElementById('mainImage').click()">
                                <i class="bi bi-cloud-upload"></i>
                                <p id="mainImageText">Click to upload main image</p>
                                <input type="file" name="image" id="mainImage" accept="image/*" class="d-none" onchange="updateFileName(this, 'mainImageText')">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Detail Photos (Multiple)</label>
                            <div class="image-upload-area" onclick="document.getElementById('detailImages').click()" style="border-style: dashed; background: #f8f9fa;">
                                <i class="bi bi-images"></i>
                                <p id="detailImagesText">Click to upload detail photos</p>
                                <input type="file" name="detail_images[]" id="detailImages" accept="image/*" multiple class="d-none" onchange="updateFileNames(this, 'detailImagesText')">
                            </div>
                            <small class="text-muted">You can select multiple images for the product gallery.</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                <label class="form-check-label" for="is_featured">
                                    Featured Product
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-coffee">
                        <i class="bi bi-check-circle"></i> Create Product
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/products/" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </form>
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
