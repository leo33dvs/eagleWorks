<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a freelancer
if (!isLoggedIn() || getUserRole() !== 'FREELANCER') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Predefined list of professions
$professions = getAvailableProfessions();

// Get current freelancer data
try {
    $stmt = $pdo->prepare("
        SELECT f.*, u.email FROM freelancers f
        JOIN users u ON f.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freelancer) {
        redirect('dashboard.php');
    }
    
    // Parse JSON fields
    $availability = json_decode($freelancer['availability'], true);
    $social_links = json_decode($freelancer['social_links'], true);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = sanitizeInput($_POST['full_name']);
    $profession = sanitizeInput($_POST['profession']);
    $phone = sanitizeInput($_POST['phone']);
    $availability_text = sanitizeInput($_POST['availability']);
    $resume = sanitizeInput($_POST['resume']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $social_links = [
        'linkedin' => sanitizeInput($_POST['linkedin'] ?? ''),
        'instagram' => sanitizeInput($_POST['instagram'] ?? ''),
        'facebook' => sanitizeInput($_POST['facebook'] ?? ''),
    ];
    
    // Basic validation
    if (empty($full_name)) {
        $errors[] = "Nome completo é obrigatório.";
    }
    
    if (empty($profession)) {
        $errors[] = "Profissão é obrigatória.";
    }
    
    if (empty($availability_text)) {
        $errors[] = "Disponibilidade é obrigatória.";
    }
    
    if (empty($resume)) {
        $errors[] = "Currículo/experiência é obrigatório.";
    }
    
    // Password change validation (if requested)
    $change_password = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        $change_password = true;
        
        if (empty($current_password)) {
            $errors[] = "Senha atual é obrigatória para alteração de senha.";
        }
        
        if (empty($new_password)) {
            $errors[] = "Nova senha é obrigatória.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "Nova senha deve ter pelo menos 8 caracteres.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Confirmação de senha não confere.";
        }
        
        // Verify current password
        if (!empty($current_password)) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = "Senha atual incorreta.";
            }
        }
    }
    
    // Handle profile picture upload
    $profile_picture = $freelancer['profile_picture']; // Keep current picture by default
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $upload = uploadFile($_FILES['profile_picture'], 'assets/uploads/profile_pictures', ['jpg', 'jpeg', 'png']);
        
        if ($upload['success']) {
            $profile_picture = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }
    
    // Handle resume file upload if provided
    $resume_file = '';
    if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === 0) {
        $upload = uploadFile($_FILES['resume_file'], 'assets/uploads/resumes', ['pdf', 'doc', 'docx']);
        
        if ($upload['success']) {
            $resume_file = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }
    
    // If no validation errors, proceed with update
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update freelancer data
            $availability_json = json_encode(['text' => $availability_text]);
            $social_links_json = json_encode($social_links);
            
            $stmt = $pdo->prepare("
                UPDATE freelancers 
                SET full_name = ?, profession = ?, resume = ?, profile_picture = ?,
                    phone = ?, availability = ?, social_links = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $full_name, $profession, $resume, $profile_picture,
                $phone, $availability_json, $social_links_json, $user_id
            ]);
            
            // Update password if requested
            if ($change_password && !empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $user_id]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Set success message
            $success = true;
            
            // Refresh freelancer data
            $stmt = $pdo->prepare("
                SELECT f.*, u.email FROM freelancers f
                JOIN users u ON f.user_id = u.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Re-parse JSON fields
            $availability = json_decode($freelancer['availability'], true);
            $social_links = json_decode($freelancer['social_links'], true);
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors[] = "Erro ao atualizar perfil: " . $e->getMessage();
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="card-title mb-4">Editar Perfil de Freelancer</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Perfil atualizado com sucesso!
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                            Informações Pessoais
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                            Segurança
                        </button>
                    </li>
                </ul>
                
                <form method="POST" action="edit_profile_freelancer.php" enctype="multipart/form-data">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Personal Info Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <!-- Profile Picture -->
                                <div class="col-md-4 mb-4">
                                    <div class="text-center">
                                        <img src="assets/uploads/profile_pictures/<?php echo htmlspecialchars($freelancer['profile_picture']); ?>" 
                                             alt="Profile Picture" 
                                             id="profile-preview"
                                             class="profile-picture mb-3">
                                        
                                        <div class="mb-3">
                                            <label for="profile_picture" class="form-label">Atualizar Foto</label>
                                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png" data-preview="profile-preview">
                                            <div class="form-text">Formato JPG ou PNG, tamanho máximo de 5MB.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Personal Details -->
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="full_name" class="form-label">Nome Completo *</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($freelancer['full_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="profession" class="form-label">Profissão *</label>
                                            <select class="form-select" id="profession" name="profession" required>
                                                <?php foreach ($professions as $prof): ?>
                                                    <option value="<?php echo $prof; ?>" <?php echo ($freelancer['profession'] === $prof) ? 'selected' : ''; ?>>
                                                        <?php echo $prof; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($freelancer['email']); ?>" readonly>
                                            <div class="form-text">O email não pode ser alterado.</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Telefone</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="(xx) xxxxx-xxxx" value="<?php echo htmlspecialchars($freelancer['phone']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="row">
                                <!-- Experience & Availability -->
                                <div class="col-md-12 mb-3">
                                    <label for="resume" class="form-label">Currículo / Experiência Profissional *</label>
                                    <textarea class="form-control" id="resume" name="resume" rows="5" required><?php echo htmlspecialchars($freelancer['resume']); ?></textarea>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="resume_file" class="form-label">Anexar Currículo (opcional)</label>
                                    <input type="file" class="form-control" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx">
                                    <div class="form-text">Formatos aceitos: PDF, DOC ou DOCX, tamanho máximo de 5MB.</div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="availability" class="form-label">Disponibilidade e Região de Atendimento *</label>
                                    <textarea class="form-control" id="availability" name="availability" rows="3" required><?php echo htmlspecialchars($availability['text'] ?? $freelancer['availability']); ?></textarea>
                                    <div class="form-text">Ex: Disponível de segunda a sexta, das 08h às 18h, na região sul de São Paulo.</div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="row">
                                <!-- Social Links -->
                                <div class="col-md-12 mb-2">
                                    <h4 class="h5">Redes Sociais (opcional)</h4>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="linkedin" class="form-label">LinkedIn</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                        <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/seuperfil" value="<?php echo htmlspecialchars($social_links['linkedin'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="instagram" class="form-label">Instagram</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                        <input type="url" class="form-control" id="instagram" name="instagram" placeholder="https://instagram.com/seuperfil" value="<?php echo htmlspecialchars($social_links['instagram'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="facebook" class="form-label">Facebook</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                        <input type="url" class="form-control" id="facebook" name="facebook" placeholder="https://facebook.com/seuperfil" value="<?php echo htmlspecialchars($social_links['facebook'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <h4 class="h5">Alterar Senha</h4>
                                    <p class="text-muted">Preencha os campos abaixo apenas se desejar alterar sua senha atual.</p>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="current_password" class="form-label">Senha Atual</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('current_password')" data-toggle="current_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">Nova Senha</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_password')" data-toggle="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div id="password-strength" class="password-strength progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirm_password')" data-toggle="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="password-match-feedback"></div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <h4 class="h5">Exclusão de Conta</h4>
                                    <p class="text-muted">Ao excluir sua conta, todos os seus dados serão removidos permanentemente.</p>
                                </div>
                                
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                        <i class="fas fa-trash-alt me-2"></i>Excluir Minha Conta
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Confirmação de Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger fw-bold">Atenção: Esta ação não pode ser desfeita!</p>
                <p>Ao excluir sua conta, todos os seus dados, incluindo avaliações e histórico, serão permanentemente removidos do sistema.</p>
                <p>Tem certeza que deseja excluir sua conta?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="delete_account.php" class="btn btn-danger">Confirmar Exclusão</a>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
