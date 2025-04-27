<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
requireLogin();

// Initialize response variables
$errors = [];
$success = false;

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? sanitizeInput($_POST['comment']) : '';
    $rated_by = isset($_POST['rated_by']) ? sanitizeInput($_POST['rated_by']) : '';
    $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : 'dashboard.php';
    
    // Initialize variables
    $freelancer_id = null;
    $company_id = null;
    
    // Set IDs based on who is rating
    if ($rated_by === 'FREELANCER') {
        // Freelancer rating a company
        if (!isset($_POST['company_id'])) {
            $errors[] = "ID da empresa não informado.";
        } else {
            $company_id = (int)$_POST['company_id'];
            $freelancer_id = getProfileId($pdo, $_SESSION['user_id'], 'FREELANCER');
            
            // Verify that the freelancer exists
            if (!$freelancer_id) {
                $errors[] = "Perfil de freelancer não encontrado.";
            }
        }
    } elseif ($rated_by === 'COMPANY') {
        // Company rating a freelancer
        if (!isset($_POST['freelancer_id'])) {
            $errors[] = "ID do freelancer não informado.";
        } else {
            $freelancer_id = (int)$_POST['freelancer_id'];
            $company_id = getProfileId($pdo, $_SESSION['user_id'], 'COMPANY');
            
            // Verify that the company exists
            if (!$company_id) {
                $errors[] = "Perfil de empresa não encontrado.";
            }
        }
    } else {
        $errors[] = "Tipo de avaliador inválido.";
    }
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Avaliação deve ser entre 1 e 5 estrelas.";
    }
    
    // Check if the user already rated
    if (empty($errors) && hasRated($pdo, 
                               ($rated_by === 'FREELANCER' ? $freelancer_id : $company_id), 
                               $rated_by, 
                               ($rated_by === 'FREELANCER' ? $company_id : $freelancer_id), 
                               ($rated_by === 'FREELANCER' ? 'COMPANY' : 'FREELANCER'))) {
        $errors[] = "Você já avaliou este " . ($rated_by === 'FREELANCER' ? 'empresa' : 'freelancer') . ".";
    }
    
    // If no errors, insert rating
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert rating
            $stmt = $pdo->prepare("
                INSERT INTO ratings (freelancer_id, company_id, rated_by, rating, comment, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$freelancer_id, $company_id, $rated_by, $rating, $comment]);
            
            // Update average rating
            if ($rated_by === 'FREELANCER') {
                // Freelancer rating a company
                calculateAverageRating($pdo, $company_id, 'COMPANY');
            } else {
                // Company rating a freelancer
                calculateAverageRating($pdo, $freelancer_id, 'FREELANCER');
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Set success message
            $success = true;
            
            // Redirect back to the profile page
            header("Location: $return_url");
            exit();
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors[] = "Erro ao salvar avaliação: " . $e->getMessage();
        }
    }
}

// If we got here, there was an error
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Avaliação</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Avaliação enviada com sucesso!
                    </div>
                    <div class="text-center mt-4">
                        <a href="<?php echo $return_url; ?>" class="btn btn-primary">Voltar</a>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo isset($return_url) ? $return_url : 'dashboard.php'; ?>" class="btn btn-primary">Voltar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
