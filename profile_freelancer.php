<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    redirect('index.php');
}

$freelancer_id = (int)$_GET['id'];

// Get freelancer data
try {
    $stmt = $pdo->prepare("
        SELECT f.*, u.email FROM freelancers f
        JOIN users u ON f.user_id = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$freelancer_id]);
    $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freelancer) {
        // Freelancer not found
        redirect('index.php');
    }
    
    // Get ratings
    $ratings = getRatings($pdo, $freelancer_id, 'FREELANCER');
    
    // Check if current user can rate this freelancer
    $canRate = false;
    $hasRated = false;
    
    if (isLoggedIn() && getUserRole() === 'COMPANY') {
        $company_id = getProfileId($pdo, $_SESSION['user_id'], 'COMPANY');
        if ($company_id) {
            $canRate = true;
            $hasRated = hasRated($pdo, $company_id, 'COMPANY', $freelancer_id, 'FREELANCER');
        }
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Parse availability and social links
$availability = json_decode($freelancer['availability'], true);
$social_links = json_decode($freelancer['social_links'], true);

// Include header
include 'includes/header.php';
?>

<!-- Freelancer Profile Header -->
<div class="profile-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-3 text-center mb-3 mb-md-0">
                <img src="<?php echo 'assets/uploads/profile_pictures/' . htmlspecialchars($freelancer['profile_picture']); ?>" 
                     alt="<?php echo htmlspecialchars($freelancer['full_name']); ?>" 
                     class="profile-picture">
            </div>
            <div class="col-md-9">
                <h1 class="mb-2"><?php echo htmlspecialchars($freelancer['full_name']); ?></h1>
                <p class="lead mb-2"><?php echo htmlspecialchars($freelancer['profession']); ?></p>
                <div class="mb-3">
                    <?php echo generateStarRating($freelancer['average_rating']); ?>
                </div>
                
                <?php if (isLoggedIn() && getUserRole() === 'COMPANY' && $canRate): ?>
                    <?php if (!$hasRated): ?>
                        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#rateModal">
                            <i class="fas fa-star me-2"></i>Avaliar Freelancer
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            <i class="fas fa-check me-2"></i>Você já avaliou
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column - Details -->
    <div class="col-lg-8">
        <!-- About -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Sobre</h5>
            </div>
            <div class="card-body">
                <h6 class="fw-bold">Experiência Profissional</h6>
                <p><?php echo nl2br(htmlspecialchars($freelancer['resume'])); ?></p>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Disponibilidade e Região</h6>
                        <p><?php echo nl2br(htmlspecialchars($availability['text'] ?? $freelancer['availability'])); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="fw-bold">Contato</h6>
                        <?php if (isLoggedIn()): ?>
                            <p><i class="fas fa-envelope me-2 text-primary"></i> <?php echo htmlspecialchars($freelancer['email']); ?></p>
                            <p><i class="fas fa-phone me-2 text-primary"></i> <?php echo htmlspecialchars($freelancer['phone']); ?></p>
                        <?php else: ?>
                            <p><i class="fas fa-info-circle me-2"></i> <a href="login.php">Faça login</a> para ver informações de contato</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($social_links) && (isset($social_links['linkedin']) || isset($social_links['instagram']) || isset($social_links['facebook']))): ?>
                    <hr>
                    
                    <h6 class="fw-bold">Redes Sociais</h6>
                    <div class="d-flex gap-3">
                        <?php if (!empty($social_links['linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($social_links['linkedin']); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-linkedin"></i> LinkedIn
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($social_links['instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($social_links['instagram']); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-instagram"></i> Instagram
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($social_links['facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($social_links['facebook']); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Right Column - Reviews -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-star me-2"></i>Avaliações</h5>
            </div>
            <div class="card-body">
                <?php if (empty($ratings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <p class="lead">Ainda não há avaliações</p>
                        <p class="text-muted">Este freelancer ainda não recebeu avaliações.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ratings as $rating): ?>
                        <div class="rating-card p-3 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <img src="assets/uploads/company_logos/<?php echo htmlspecialchars($rating['reviewer_picture']); ?>" alt="Company Logo" class="company-logo-small me-3">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($rating['reviewer_name']); ?></h6>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $rating['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($rating['comment'])): ?>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($rating['comment'])); ?></p>
                            <?php endif; ?>
                            
                            <small class="text-muted"><?php echo formatDate($rating['created_at']); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Rate Modal -->
<?php if (isLoggedIn() && getUserRole() === 'COMPANY' && $canRate && !$hasRated): ?>
<div class="modal fade" id="rateModal" tabindex="-1" aria-labelledby="rateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rateModalLabel">Avaliar <?php echo htmlspecialchars($freelancer['full_name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="rate.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="freelancer_id" value="<?php echo $freelancer_id; ?>">
                    <input type="hidden" name="rated_by" value="COMPANY">
                    <input type="hidden" name="return_url" value="profile_freelancer.php?id=<?php echo $freelancer_id; ?>">
                    <input type="hidden" name="rating" id="rating-value" value="5">
                    
                    <div class="mb-4 text-center">
                        <p class="mb-2">Sua avaliação:</p>
                        <div class="rating-stars fs-3">
                            <i class="fas fa-star rating-star text-warning" data-value="1"></i>
                            <i class="fas fa-star rating-star text-warning" data-value="2"></i>
                            <i class="fas fa-star rating-star text-warning" data-value="3"></i>
                            <i class="fas fa-star rating-star text-warning" data-value="4"></i>
                            <i class="fas fa-star rating-star text-warning" data-value="5"></i>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">Comentário (opcional):</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Compartilhe sua experiência com este freelancer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Avaliação</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
include 'includes/footer.php';
?>
