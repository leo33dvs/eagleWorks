<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$profile_id = null;
$user_data = null;
$ratings = [];

try {
    // Get user data based on role
    $user_data = getUserData($pdo, $user_id, $role);
    
    if ($role === 'FREELANCER') {
        $profile_id = $user_data['id'];
        $ratings = getRatings($pdo, $profile_id, 'FREELANCER');
    } else {
        $profile_id = $user_data['id'];
        $ratings = getRatings($pdo, $profile_id, 'COMPANY');
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Include header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card profile-header">
            <div class="card-body">
                <div class="row align-items-center">
                    <?php if ($role === 'FREELANCER'): ?>
                        <div class="col-md-2 text-center">
                            <img src="assets/uploads/profile_pictures/<?php echo htmlspecialchars($user_data['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($user_data['full_name']); ?>" 
                                 class="profile-picture">
                        </div>
                        <div class="col-md-7">
                            <h2 class="mb-1"><?php echo htmlspecialchars($user_data['full_name']); ?></h2>
                            <p class="lead mb-2"><?php echo htmlspecialchars($user_data['profession']); ?></p>
                            <div class="mb-2">
                                <?php echo generateStarRating($user_data['average_rating']); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-md-2 text-center">
                            <img src="assets/uploads/company_logos/<?php echo htmlspecialchars($user_data['logo']); ?>" 
                                 alt="<?php echo htmlspecialchars($user_data['name']); ?>" 
                                 class="company-logo">
                        </div>
                        <div class="col-md-7">
                            <h2 class="mb-1"><?php echo htmlspecialchars($user_data['name']); ?></h2>
                            <p class="lead mb-2"><?php echo htmlspecialchars($user_data['industry']); ?></p>
                            <div class="mb-2">
                                <?php echo generateStarRating($user_data['average_rating']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                        <?php if ($role === 'FREELANCER'): ?>
                            <a href="edit_profile_freelancer.php" class="btn btn-light">
                                <i class="fas fa-edit me-2"></i>Editar Perfil
                            </a>
                            <a href="profile_freelancer.php?id=<?php echo $profile_id; ?>" class="btn btn-gold mt-2">
                                <i class="fas fa-eye me-2"></i>Ver Perfil Público
                            </a>
                        <?php else: ?>
                            <a href="edit_profile_company.php" class="btn btn-light">
                                <i class="fas fa-edit me-2"></i>Editar Perfil
                            </a>
                            <a href="profile_company.php?id=<?php echo $profile_id; ?>" class="btn btn-gold mt-2">
                                <i class="fas fa-eye me-2"></i>Ver Perfil Público
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column - Dashboard -->
    <div class="col-lg-8">
        <!-- Dashboard Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="card dashboard-card text-center h-100">
                    <div class="card-body">
                        <div class="dashboard-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="card-title"><?php echo number_format($user_data['average_rating'], 1); ?></h3>
                        <p class="card-text">Avaliação Média</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="card dashboard-card text-center h-100">
                    <div class="card-body">
                        <div class="dashboard-icon">
                            <i class="fas fa-comment"></i>
                        </div>
                        <h3 class="card-title"><?php echo count($ratings); ?></h3>
                        <p class="card-text">Avaliações Recebidas</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card dashboard-card text-center h-100">
                    <div class="card-body">
                        <div class="dashboard-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3 class="card-title"><?php echo date('d/m/Y', strtotime($user_data['created_at'] ?? date('Y-m-d'))); ?></h3>
                        <p class="card-text">Data de Registro</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Ações Rápidas</h4>
                
                <div class="row g-3">
                    <?php if ($role === 'COMPANY'): ?>
                        <div class="col-md-6">
                            <a href="search.php" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Buscar Freelancers
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-md-6">
                        <a href="<?php echo ($role === 'FREELANCER') ? 'edit_profile_freelancer.php' : 'edit_profile_company.php'; ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-cog me-2"></i>Configurações de Perfil
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($role === 'FREELANCER'): ?>
            <!-- Tips for Freelancers -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-lightbulb me-2"></i>Dicas para Freelancers</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">Mantenha seu perfil atualizado com suas experiências mais recentes.</li>
                        <li class="mb-2">Detalhe sua disponibilidade para aumentar suas chances de ser contratado.</li>
                        <li class="mb-2">Responda rapidamente às propostas de empresas interessadas.</li>
                        <li class="mb-2">Solicite avaliações às empresas após concluir um trabalho.</li>
                        <li>Use palavras-chave relevantes em seu currículo para melhorar sua visibilidade nas buscas.</li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <!-- Tips for Companies -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-lightbulb me-2"></i>Dicas para Empresas</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">Use filtros específicos para encontrar os freelancers mais adequados para suas necessidades.</li>
                        <li class="mb-2">Verifique as avaliações e portfólio dos freelancers antes de contatá-los.</li>
                        <li class="mb-2">Forneça detalhes claros sobre o projeto ao entrar em contato com freelancers.</li>
                        <li class="mb-2">Avalie os freelancers após a conclusão dos trabalhos para ajudar outros contratantes.</li>
                        <li>Mantenha seu perfil da empresa atualizado para atrair os melhores talentos.</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Column - Recent Reviews -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-comments me-2"></i>Avaliações Recentes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($ratings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <p class="lead">Ainda não há avaliações</p>
                        <p class="text-muted">
                            <?php if ($role === 'FREELANCER'): ?>
                                Você ainda não recebeu avaliações de empresas.
                            <?php else: ?>
                                Sua empresa ainda não recebeu avaliações de freelancers.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php 
                    // Show only the 5 most recent ratings
                    $ratings = array_slice($ratings, 0, 5);
                    ?>
                    
                    <?php foreach ($ratings as $rating): ?>
                        <div class="rating-card p-3 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <?php if ($role === 'FREELANCER'): ?>
                                    <img src="assets/uploads/company_logos/<?php echo htmlspecialchars($rating['reviewer_picture']); ?>" alt="Company Logo" class="company-logo-small me-3">
                                <?php else: ?>
                                    <img src="assets/uploads/profile_pictures/<?php echo htmlspecialchars($rating['reviewer_picture']); ?>" alt="Freelancer Profile" class="profile-picture-small me-3">
                                <?php endif; ?>
                                
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
                    
                    <?php if (count($ratings) >= 5): ?>
                        <div class="text-center mt-3">
                            <a href="<?php echo ($role === 'FREELANCER') ? 'profile_freelancer.php?id=' . $profile_id : 'profile_company.php?id=' . $profile_id; ?>" class="btn btn-outline-primary btn-sm">
                                Ver Todas as Avaliações
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Platform News -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-newspaper me-2"></i>Novidades da Plataforma</h5>
            </div>
            <div class="card-body">
                <div class="news-item mb-3 pb-3 border-bottom">
                    <h6 class="mb-1">Novo Sistema de Avaliações</h6>
                    <p class="mb-1">Agora você pode avaliar e ser avaliado de forma mais detalhada.</p>
                    <small class="text-muted">26/10/2023</small>
                </div>
                
                <div class="news-item mb-3 pb-3 border-bottom">
                    <h6 class="mb-1">Filtros de Busca Aprimorados</h6>
                    <p class="mb-1">Adicionamos novos filtros para facilitar a busca por freelancers.</p>
                    <small class="text-muted">15/10/2023</small>
                </div>
                
                <div class="news-item">
                    <h6 class="mb-1">Bem-vindo à EagleWorks!</h6>
                    <p class="mb-1">Plataforma lançada oficialmente. Conectando talentos e oportunidades.</p>
                    <small class="text-muted">01/10/2023</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
