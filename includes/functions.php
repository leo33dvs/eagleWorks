<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirects to specified URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Checks if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Gets current user role if logged in
 */
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Verifies if the user has the required role
 */
function checkRole($requiredRole) {
    if (!isLoggedIn() || $_SESSION['role'] !== $requiredRole) {
        redirect('login.php');
    }
}

/**
 * Check if user is either a freelancer or company
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate safe filename for uploaded files
 */
function generateSafeFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $safeName = uniqid() . '_' . time() . '.' . $extension;
    return $safeName;
}

/**
 * Handle file upload
 */
function uploadFile($fileData, $uploadDir, $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf']) {
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = $fileData['name'];
    $fileSize = $fileData['size'];
    $fileTmp = $fileData['tmp_name'];
    $fileError = $fileData['error'];
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file
    if ($fileError !== 0) {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
    
    if (!in_array($fileExt, $allowedExtensions)) {
        return ['success' => false, 'message' => 'File extension not allowed.'];
    }
    
    if ($fileSize > 5000000) { // 5MB max
        return ['success' => false, 'message' => 'File size too large.'];
    }
    
    $newFileName = generateSafeFileName($fileName);
    $destination = $uploadDir . '/' . $newFileName;
    
    if (move_uploaded_file($fileTmp, $destination)) {
        return ['success' => true, 'filename' => $newFileName];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

/**
 * Calculate average rating
 */
function calculateAverageRating($pdo, $id, $userType) {
    $table = $userType === 'FREELANCER' ? 'freelancers' : 'companies';
    $idColumn = $userType === 'FREELANCER' ? 'freelancer_id' : 'company_id';
    
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM ratings WHERE $idColumn = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $avgRating = $result && $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
    
    // Update the average rating in the respective table
    $updateStmt = $pdo->prepare("UPDATE $table SET average_rating = ? WHERE id = ?");
    $updateStmt->execute([$avgRating, $id]);
    
    return $avgRating;
}

/**
 * Get user data based on user ID
 */
function getUserData($pdo, $userId, $role) {
    if ($role === 'FREELANCER') {
        $stmt = $pdo->prepare("
            SELECT f.*, u.email FROM freelancers f
            JOIN users u ON f.user_id = u.id
            WHERE u.id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, u.email FROM companies c
            JOIN users u ON c.user_id = u.id
            WHERE u.id = ?
        ");
    }
    
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get freelancer data from ID
 */
function getFreelancerData($pdo, $freelancerId) {
    $stmt = $pdo->prepare("
        SELECT f.*, u.email FROM freelancers f
        JOIN users u ON f.user_id = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$freelancerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get company data from ID
 */
function getCompanyData($pdo, $companyId) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.email FROM companies c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get ratings for a specific user
 */
function getRatings($pdo, $id, $userType) {
    $idColumn = $userType === 'FREELANCER' ? 'freelancer_id' : 'company_id';
    $otherType = $userType === 'FREELANCER' ? 'COMPANY' : 'FREELANCER';
    $otherIdColumn = $userType === 'FREELANCER' ? 'company_id' : 'freelancer_id';
    $otherTable = $userType === 'FREELANCER' ? 'companies' : 'freelancers';
    
    $stmt = $pdo->prepare("
        SELECT r.*, o.full_name as reviewer_name, o.profile_picture as reviewer_picture
        FROM ratings r
        JOIN $otherTable o ON r.$otherIdColumn = o.id
        WHERE r.$idColumn = ? AND r.rated_by = ?
        ORDER BY r.created_at DESC
    ");
    
    if ($userType === 'COMPANY') {
        $stmt = $pdo->prepare("
            SELECT r.*, o.full_name as reviewer_name, o.profile_picture as reviewer_picture
            FROM ratings r
            JOIN freelancers o ON r.freelancer_id = o.id
            WHERE r.company_id = ? AND r.rated_by = ?
            ORDER BY r.created_at DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT r.*, o.name as reviewer_name, o.logo as reviewer_picture
            FROM ratings r
            JOIN companies o ON r.company_id = o.id
            WHERE r.freelancer_id = ? AND r.rated_by = ?
            ORDER BY r.created_at DESC
        ");
    }
    
    $stmt->execute([$id, $otherType]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get ID from user_id for freelancer or company
 */
function getProfileId($pdo, $userId, $role) {
    $table = $role === 'FREELANCER' ? 'freelancers' : 'companies';
    $stmt = $pdo->prepare("SELECT id FROM $table WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
}

/**
 * Check if user has already rated another user
 */
function hasRated($pdo, $raterId, $raterType, $ratedId, $ratedType) {
    $raterIdColumn = $raterType === 'FREELANCER' ? 'freelancer_id' : 'company_id';
    $ratedIdColumn = $ratedType === 'FREELANCER' ? 'freelancer_id' : 'company_id';
    
    $stmt = $pdo->prepare("
        SELECT id FROM ratings 
        WHERE $raterIdColumn = ? AND $ratedIdColumn = ? AND rated_by = ?
    ");
    $stmt->execute([$raterId, $ratedId, $raterType]);
    return $stmt->fetch() ? true : false;
}

/**
 * Format a date in a human-readable format
 */
function formatDate($date) {
    $timestamp = strtotime($date);
    return date('F j, Y', $timestamp);
}

/**
 * Generate star rating HTML
 */
function generateStarRating($rating) {
    $fullStar = '<i class="fas fa-star text-warning"></i>';
    $halfStar = '<i class="fas fa-star-half-alt text-warning"></i>';
    $emptyStar = '<i class="far fa-star text-warning"></i>';
    
    $output = '';
    $fullStars = floor($rating);
    $halfStars = ceil($rating - $fullStars);
    $emptyStars = 5 - $fullStars - $halfStars;
    
    for ($i = 0; $i < $fullStars; $i++) {
        $output .= $fullStar;
    }
    
    for ($i = 0; $i < $halfStars; $i++) {
        $output .= $halfStar;
    }
    
    for ($i = 0; $i < $emptyStars; $i++) {
        $output .= $emptyStar;
    }
    
    return $output . ' <span class="text-muted">(' . number_format($rating, 1) . ')</span>';
}

/**
 * Get available professions 
 */
function getAvailableProfessions() {
    return [
        'Barman',
        'Garçom',
        'Cozinheiro',
        'Diarista',
        'Designer',
        'Desenvolvedor Web',
        'Fotógrafo',
        'Tradutor',
        'Motorista',
        'Eletricista',
        'Encanador',
        'Personal Trainer',
        'Professor Particular',
        'Instrutor de Yoga',
        'Maquiador',
        'Cabeleireiro',
        'Outros'
    ];
}
