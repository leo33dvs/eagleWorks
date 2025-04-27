<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$success = false;

// Predefined list of professions
$professions = getAvailableProfessions();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitizeInput($_POST['full_name']);
    $profession = sanitizeInput($_POST['profession']);
    $phone = sanitizeInput($_POST['phone']);
    $availability = sanitizeInput($_POST['availability']);
    $resume = sanitizeInput($_POST['resume']);
    $social_links = [
        'linkedin' => sanitizeInput($_POST['linkedin'] ?? ''),
        'instagram' => sanitizeInput($_POST['instagram'] ?? ''),
        'facebook' => sanitizeInput($_POST['facebook'] ?? ''),
    ];
    
    // Basic validation
    if (empty($email)) {
        $errors[] = "Email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido.";
    }
    
    if (empty($password)) {
        $errors[] = "Senha é obrigatória.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Senha deve ter pelo menos 8 caracteres.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "As senhas não coincidem.";
    }
    
    if (empty($full_name)) {
        $errors[] = "Nome completo é obrigatório.";
    }
    
    if (empty($profession)) {
        $errors[] = "Profissão é obrigatória.";
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Este email já está em uso.";
    }
    
    // Handle file upload
    $profile_picture = 'default-profile.jpg'; // Default image
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
    
    // If no validation errors, proceed with registration
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert into users table
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role, created_at) VALUES (?, ?, 'FREELANCER', NOW())");
            $stmt->execute([$email, $hash]);
            $user_id = $pdo->lastInsertId();
            
            // Insert into freelancers table
            $availability_json = json_encode(['text' => $availability]);
            $social_links_json = json_encode($social_links);
            $stmt = $pdo->prepare("
                INSERT INTO freelancers (
                    user_id, full_name, profession, resume, profile_picture, 
                    phone, availability, social_links, average_rating
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([
                $user_id, $full_name, $profession, $resume, $profile_picture, 
                $phone, $availability_json, $social_links_json
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            // Set success message
            $success = true;
            
            // Auto-login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'FREELANCER';
            
            // Redirect after a short delay
            header("refresh:3;url=dashboard.php");
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors[] = "Erro no cadastro: " . $e->getMessage();
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="card-title text-center mb-4">Cadastro de Freelancer</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading">Cadastro realizado com sucesso!</h4>
                        <p>Seu cadastro foi concluído e você já está conectado na plataforma EagleWorks.</p>
                        <hr>
                        <p class="mb-0">Você será redirecionado para o Dashboard em instantes...</p>
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
                
                <form method="POST" action="signup_freelancer.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <!-- Account Information -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Informações da Conta</h3>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                <div class="invalid-feedback">Email é obrigatório.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Senha *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password')" data-toggle="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Senha deve ter pelo menos 8 caracteres.</div>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div id="password-strength" class="password-strength progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Senha *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirm_password')" data-toggle="confirm_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="password-match-feedback"></div>
                            </div>
                        </div>
                        
                        <!-- Personal Information -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Informações Pessoais</h3>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
                                <div class="invalid-feedback">Nome completo é obrigatório.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profession" class="form-label">Profissão *</label>
                                <select class="form-select" id="profession" name="profession" required>
                                    <option value="" selected disabled>Selecione sua profissão</option>
                                    <?php foreach ($professions as $prof): ?>
                                        <option value="<?php echo $prof; ?>" <?php echo (isset($profession) && $profession === $prof) ? 'selected' : ''; ?>>
                                            <?php echo $prof; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Selecione uma profissão.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="(xx) xxxxx-xxxx" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <!-- Profile Picture -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Foto de Perfil</h3>
                            
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Foto (opcional)</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png" data-preview="profile-preview">
                                <div class="form-text">Formato JPG ou PNG, tamanho máximo de 5MB.</div>
                            </div>
                            
                            <div class="mb-3 text-center">
                                <img id="profile-preview" src="https://via.placeholder.com/150" alt="Preview" class="img-thumbnail rounded-circle profile-picture" style="display: none;">
                            </div>
                        </div>
                        
                        <!-- Professional Information -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Informações Profissionais</h3>
                            
                            <div class="mb-3">
                                <label for="resume" class="form-label">Currículo / Experiência Profissional *</label>
                                <textarea class="form-control" id="resume" name="resume" rows="5" required><?php echo isset($resume) ? htmlspecialchars($resume) : ''; ?></textarea>
                                <div class="invalid-feedback">Por favor, descreva sua experiência profissional.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="resume_file" class="form-label">Anexar Currículo (opcional)</label>
                                <input type="file" class="form-control" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx">
                                <div class="form-text">Formatos aceitos: PDF, DOC ou DOCX, tamanho máximo de 5MB.</div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <!-- Availability -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Disponibilidade</h3>
                            
                            <div class="mb-3">
                                <label for="availability" class="form-label">Dias, Horários e Região de Atendimento *</label>
                                <textarea class="form-control" id="availability" name="availability" rows="3" required><?php echo isset($availability) ? htmlspecialchars($availability) : ''; ?></textarea>
                                <div class="form-text">Ex: Disponível de segunda a sexta, das 08h às 18h, na região sul de São Paulo.</div>
                                <div class="invalid-feedback">Por favor, informe sua disponibilidade.</div>
                            </div>
                        </div>
                        
                        <!-- Social Links -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Redes Sociais (opcional)</h3>
                            
                            <div class="mb-3">
                                <label for="linkedin" class="form-label">LinkedIn</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                    <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/seuperfil" value="<?php echo isset($social_links['linkedin']) ? htmlspecialchars($social_links['linkedin']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="instagram" class="form-label">Instagram</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                    <input type="url" class="form-control" id="instagram" name="instagram" placeholder="https://instagram.com/seuperfil" value="<?php echo isset($social_links['instagram']) ? htmlspecialchars($social_links['instagram']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="facebook" class="form-label">Facebook</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                    <input type="url" class="form-control" id="facebook" name="facebook" placeholder="https://facebook.com/seuperfil" value="<?php echo isset($social_links['facebook']) ? htmlspecialchars($social_links['facebook']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            Concordo com os <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Termos de Uso</a> e <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Política de Privacidade</a>.
                        </label>
                        <div class="invalid-feedback">
                            Você deve concordar com os termos para prosseguir.
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Criar Conta</button>
                    </div>
                </form>
                
                <div class="mt-4 text-center">
                    <p>Já possui uma conta? <a href="login.php">Faça login</a></p>
                    <p>Ou <a href="signup_company.php">cadastre-se como empresa</a>.</p>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Termos de Uso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4>Termos de Uso da Plataforma EagleWorks</h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in dui mauris. Vivamus hendrerit arcu sed erat molestie vehicula.</p>
                <p>Sed auctor neque eu tellus rhoncus ut eleifend nibh porttitor. Ut in nulla enim. Phasellus molestie magna non est bibendum non venenatis nisl tempor.</p>
                <p>Suspendisse dictum feugiat nisl ut dapibus. Mauris iaculis porttitor posuere. Praesent id metus massa, ut blandit odio.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Política de Privacidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4>Política de Privacidade da Plataforma EagleWorks</h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in dui mauris. Vivamus hendrerit arcu sed erat molestie vehicula.</p>
                <p>Sed auctor neque eu tellus rhoncus ut eleifend nibh porttitor. Ut in nulla enim. Phasellus molestie magna non est bibendum non venenatis nisl tempor.</p>
                <p>Suspendisse dictum feugiat nisl ut dapibus. Mauris iaculis porttitor posuere. Praesent id metus massa, ut blandit odio.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
