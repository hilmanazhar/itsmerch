<?php
// src/api/upload_image.php
// Upload product image with auto-compression if > 2MB
require 'db.php';

header('Content-Type: application/json');

// Max file size: 2MB
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB in bytes
define('TARGET_FILE_SIZE', 2 * 1024 * 1024); // Target after compression
define('UPLOAD_DIR', '../assets/images/products/');

// Allowed file types
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('Method not allowed', 405);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi limit server)',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar',
        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension'
    ];
    $error_code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    json_err($errors[$error_code] ?? 'Upload error', 400);
}

$file = $_FILES['image'];
$original_size = $file['size'];

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    json_err('Tipe file tidak diizinkan. Gunakan: JPG, PNG, GIF, atau WebP', 400);
}

// Validate extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $allowed_extensions)) {
    json_err('Ekstensi file tidak valid', 400);
}

// Create upload directory if not exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Generate unique filename
$filename = 'prod_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
$target_path = UPLOAD_DIR . $filename;

// Check if compression is needed
$needs_compression = $original_size > MAX_FILE_SIZE;
$compressed = false;
$final_size = $original_size;

if ($needs_compression) {
    // Compress the image
    $compressed_result = compressImage($file['tmp_name'], $target_path, $mime_type);
    
    if ($compressed_result['success']) {
        $compressed = true;
        $final_size = $compressed_result['size'];
    } else {
        json_err('Gagal mengkompresi gambar: ' . $compressed_result['error'], 500);
    }
} else {
    // Just move the file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        json_err('Gagal menyimpan file', 500);
    }
}

// Return success response
$image_url = 'assets/images/products/' . $filename;

json_ok([
    'success' => true,
    'image_url' => $image_url,
    'filename' => $filename,
    'original_size' => formatFileSize($original_size),
    'final_size' => formatFileSize($final_size),
    'compressed' => $compressed,
    'compression_ratio' => $compressed ? round((1 - $final_size / $original_size) * 100, 1) . '%' : '0%'
]);

/**
 * Compress image to fit under target size
 */
function compressImage($source, $destination, $mime_type) {
    // Load image based on type
    switch ($mime_type) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($source);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($source);
            break;
        default:
            return ['success' => false, 'error' => 'Unsupported image type'];
    }
    
    if (!$image) {
        return ['success' => false, 'error' => 'Failed to load image'];
    }
    
    // Get original dimensions
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);
    
    // Start with high quality and reduce until under target size
    $quality = 85;
    $min_quality = 30;
    $scale = 1.0;
    
    // Calculate initial file size estimate
    $original_size = filesize($source);
    
    // If file is very large, scale down dimensions first
    if ($original_size > TARGET_FILE_SIZE * 3) {
        // Calculate scale factor to reduce file size
        $scale = sqrt(TARGET_FILE_SIZE / $original_size) * 1.5; // A bit more to account for compression
        $scale = max(0.3, min(1.0, $scale)); // Keep between 30% and 100%
    }
    
    $new_width = (int)($orig_width * $scale);
    $new_height = (int)($orig_height * $scale);
    
    // Resize if needed
    if ($scale < 1.0) {
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG
        if ($mime_type === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        imagedestroy($image);
        $image = $resized;
    }
    
    // Try different quality levels until file size is acceptable
    $attempts = 0;
    $max_attempts = 10;
    
    do {
        // Save to temporary file to check size
        $temp_file = sys_get_temp_dir() . '/compress_' . uniqid() . '.jpg';
        
        // Always save as JPEG for best compression
        imagejpeg($image, $temp_file, $quality);
        
        $current_size = filesize($temp_file);
        
        if ($current_size <= TARGET_FILE_SIZE) {
            // Success - move to final destination
            rename($temp_file, $destination);
            imagedestroy($image);
            
            return [
                'success' => true,
                'size' => $current_size
            ];
        }
        
        // Clean up temp file
        @unlink($temp_file);
        
        // Reduce quality for next attempt
        $quality -= 10;
        $attempts++;
        
        // If quality is too low, also reduce dimensions
        if ($quality < $min_quality && $scale > 0.3) {
            $scale *= 0.8;
            $new_width = (int)($orig_width * $scale);
            $new_height = (int)($orig_height * $scale);
            
            $resized = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, imagesx($image), imagesy($image));
            imagedestroy($image);
            $image = $resized;
            
            $quality = 70; // Reset quality after resize
        }
        
    } while ($attempts < $max_attempts);
    
    // Last resort - just save with minimum quality
    imagejpeg($image, $destination, $min_quality);
    $final_size = filesize($destination);
    imagedestroy($image);
    
    return [
        'success' => true,
        'size' => $final_size
    ];
}

/**
 * Format file size to human readable
 */
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
