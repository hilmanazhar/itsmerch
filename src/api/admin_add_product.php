<?php
// src/api/admin_add_product.php
require 'db.php';

// Max file size: 2MB
define('MAX_FILE_SIZE', 2 * 1024 * 1024);

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Fallback to POST if not JSON
    $input = $_POST;
}

$name = $input['name'] ?? '';
$desc = $input['description'] ?? '';
$price = $input['price'] ?? 0;
$stock = $input['stock'] ?? 0;
$image = $input['image_url'] ?? '';

// Handle File Upload with auto-compression
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/images/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileInfo = pathinfo($_FILES['image_file']['name']);
    $ext = strtolower($fileInfo['extension']);
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    if (in_array($ext, $allowed)) {
        $filename = 'prod_' . time() . '_' . uniqid() . '.jpg'; // Always save as jpg for compression
        $targetPath = $uploadDir . $filename;
        $originalSize = $_FILES['image_file']['size'];
        
        // Check if compression is needed (file > 2MB)
        if ($originalSize > MAX_FILE_SIZE) {
            // Compress the image
            $compressed = compressProductImage($_FILES['image_file']['tmp_name'], $targetPath, $ext);
            if ($compressed) {
                $image = 'assets/images/products/' . $filename;
            }
        } else {
            // File is under 2MB, just move it
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                $image = 'assets/images/products/' . $filename;
            }
        }
    }
}

$category_id = $input['category_id'] ?? null;

if (!$name || !$price) {
    json_err('Nama dan harga wajib diisi');
}

// Handle empty category
if (empty($category_id)) {
    $category_id = null;
}

$stmt = $mysqli->prepare("INSERT INTO products (name,description,image_url,price,stock,category_id) VALUES (?,?,?,?,?,?)");
$stmt->bind_param('sssdii', $name, $desc, $image, $price, $stock, $category_id);
$res = $stmt->execute();
$id = $stmt->insert_id;

if ($res) {
    json_ok(['success'=>true, 'id'=>$id]);
} else {
    json_err('Failed to add product: ' . $stmt->error);
}

/**
 * Compress image to fit under 2MB
 */
function compressProductImage($source, $destination, $originalExt) {
    // Load image based on type
    switch ($originalExt) {
        case 'jpg':
        case 'jpeg':
            $image = @imagecreatefromjpeg($source);
            break;
        case 'png':
            $image = @imagecreatefrompng($source);
            break;
        case 'gif':
            $image = @imagecreatefromgif($source);
            break;
        case 'webp':
            $image = @imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);
    $original_size = filesize($source);
    
    // Calculate scale to reduce file size
    $scale = sqrt(MAX_FILE_SIZE / $original_size) * 1.2;
    $scale = max(0.3, min(1.0, $scale));
    
    $new_width = (int)($orig_width * $scale);
    $new_height = (int)($orig_height * $scale);
    
    // Resize if needed
    if ($scale < 1.0) {
        $resized = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        imagedestroy($image);
        $image = $resized;
    }
    
    // Try different quality levels
    $quality = 85;
    $attempts = 0;
    
    do {
        $temp_file = sys_get_temp_dir() . '/compress_' . uniqid() . '.jpg';
        imagejpeg($image, $temp_file, $quality);
        $current_size = filesize($temp_file);
        
        if ($current_size <= MAX_FILE_SIZE) {
            rename($temp_file, $destination);
            imagedestroy($image);
            return true;
        }
        
        @unlink($temp_file);
        $quality -= 10;
        $attempts++;
    } while ($quality >= 30 && $attempts < 6);
    
    // Last attempt with minimum quality
    imagejpeg($image, $destination, 30);
    imagedestroy($image);
    return true;
}
?>

