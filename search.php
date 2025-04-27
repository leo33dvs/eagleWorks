<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get search parameters
$profession = isset($_GET['profession']) ? sanitizeInput($_GET['profession']) : '';
$region = isset($_GET['region']) ? sanitizeInput($_GET['region']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Get available professions for dropdown
$professions = getAvailableProfessions();

// Query to fetch freelancers based on search criteria
try {
    // Base query
    $query = "SELECT * FROM freelancers WHERE 1=1";
    $params = [];
    
    // Add filters
    if (!empty($profession)) {
        $query .= " AND profession = ?";
        $params[] = $profession;
    }
    
    if (!empty($region)) {
        $query .= " AND availability LIKE ?";
        $params[] = '%' . $region . '%';
    }
    
    if (!empty($search)) {
        $query .= " AND (full_name LIKE ? OR profession LIKE ? OR resume LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    // Order by rating
    $query .= " ORDER BY average_rating DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $freelancers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Include header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title mb-4">Buscar Freelancers</h2>
                
                <form action="search.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="profession" class="form-label">Profissão</label>
                        <select class="form-select" id="profession" name="profession">
                            <option value="">Todas as profissões</option>
                            <?php foreach ($professions as $prof): ?>
                                <option value="<?php echo $prof; ?>" <?php echo ($profession === $prof) ? 'selected' : ''; ?>>
                                    <?php echo $prof; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="region" class="form-label">Região</label>
                        <input type="text" class="form-control" id="region" name="region" placeholder="Ex: São Paulo" value="<?php echo htmlspecialchars($region); ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="search" class="form-label">Busca por palavras-chave</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Nome, habilidades, etc." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <a href="search.php" class="btn btn-outline-secondary ms-2">Limpar Filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h3 class="mb-3">Resultados da Busca</h3>
        
        <?php if (empty($freelancers)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Nenhum freelancer encontrado com os critérios especificados.
            </div>
        <?php else: ?>
            <p class="text-muted mb-4"><?php echo count($freelancers); ?> freelancers encontrados</p>
            
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 search-results">
                <?php foreach ($freelancers as $freelancer): ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="assets/uploads/profile_pictures/<?php echo htmlspecialchars($freelancer['profile_picture']); ?>" 
                                         alt="<?php echo htmlspecialchars($freelancer['full_name']); ?>" 
                                         class="profile-picture-small me-3">
                                    <div>
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($freelancer['full_name']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($freelancer['profession']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <?php echo generateStarRating($freelancer['average_rating']); ?>
                                </div>
                                
                                <?php 
                                // Parse availability
                                $availability = json_decode($freelancer['availability'], true);
                                $availabilityText = isset($availability['text']) ? $availability['text'] : $freelancer['availability'];
                                
                                // Truncate if too long
                                if (strlen($availabilityText) > 100) {
                                    $availabilityText = substr($availabilityText, 0, 100) . '...';
                                }
                                ?>
                                
                                <p class="card-text mb-3">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                    <?php echo htmlspecialchars($availabilityText); ?>
                                </p>
                                
                                <div class="d-grid">
                                    <a href="profile_freelancer.php?id=<?php echo $freelancer['id']; ?>" class="btn btn-outline-primary">
                                        Ver Perfil Completo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
