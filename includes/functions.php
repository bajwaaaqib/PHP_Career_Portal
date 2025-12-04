<?php
// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate file upload
function validateFile($file, $allowed_types = ['pdf', 'doc', 'docx'], $max_size = 5242880) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload failed.";
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = "File size must be less than 5MB.";
    }
    
    // Check file extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        $errors[] = "Only PDF, DOC, and DOCX files are allowed.";
    }
    
    return $errors;
}

// Generate unique filename
function generateFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

// Upload file
function uploadFile($file, $destination) {
    $filename = generateFilename($file['name']);
    $target_path = $destination . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }
    
    return false;
}

// Delete old file
function deleteOldFile($filename, $directory) {
    if ($filename && file_exists($directory . $filename)) {
        unlink($directory . $filename);
    }
}
?>