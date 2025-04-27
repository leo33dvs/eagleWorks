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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $company_name = sanitizeInput($_POST['company_name']);
    $cnpj = sanitizeInput($_POST['cnpj']);
    $industry = sanitizeInput($_POST['industry']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone']);
    
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
    
    if (empty($company_name)) {
        $errors[] = "Nome da empresa é obrigatório.";
    }
    
    if (empty($cnpj)) {
        $errors[] = "CNPJ é obrigatório.";
    } elseif (strlen(preg_replace('/[^0-9]/', '', $cnpj)) !== 14) {
        $errors[] = "CNPJ inválido.";
    }
    
    if (empty($industry)) {
        $errors[] = "Área de atuação é obrigatória.";
    }
    
    if (empty($address)) {
        $errors[] = "Endereço é obrigatório.";
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Este email já está em uso.";
    }
    
    // Check if CNPJ already exists
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE cnpj = ?");
    $stmt->execute([$cnpj]);
    if ($stmt->fetch()) {
        $errors[] = "Este CNPJ já está cadastrado.";
    }
    
    // Handle logo upload
    $logo = 'default-company.jpg'; // Default image
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $upload = uploadFile($_FILES['logo'], 'assets/uploads/company_logos', ['jpg', 'jpeg', 'png']);
        
        if ($upload['success']) {
            $logo = $upload['filename'];
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
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role, created_at) VALUES (?, ?, 'COMPANY', NOW())");
            $stmt->execute([$email, $hash]);
            $user_id = $pdo->lastInsertId();
            
            // Prepare contact JSON
            $contact = json_encode(['email' => $email, 'phone' => $phone]);
            
            // Insert into companies table
            $stmt = $pdo->prepare("
                INSERT INTO companies (
                    user_id, name, cnpj, industry, address, 
                    logo, contact, average_rating
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([
                $user_id, $company_name, $cnpj, $industry, $address, 
                $logo, $contact
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            // Set success message
            $success = true;
            
            // Auto-login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'COMPANY';
            
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
                <h1 class="card-title text-center mb-4">Cadastro de Empresa</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading">Cadastro realizado com sucesso!</h4>
                        <p>Sua empresa foi cadastrada e você já está conectado na plataforma EagleWorks.</p>
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
                
                <form method="POST" action="signup_company.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <!-- Account Information -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Informações da Conta</h3>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Corporativo *</label>
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
                        
                        <!-- Company Information -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Informações da Empresa</h3>
                            
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Nome da Empresa *</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo isset($company_name) ? htmlspecialchars($company_name) : ''; ?>" required>
                                <div class="invalid-feedback">Nome da empresa é obrigatório.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cnpj" class="form-label">CNPJ *</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="XX.XXX.XXX/XXXX-XX" value="<?php echo isset($cnpj) ? htmlspecialchars($cnpj) : ''; ?>" required>
                                <div class="invalid-feedback">CNPJ é obrigatório e deve ter 14 dígitos.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="industry" class="form-label">Área de Atuação *</label>
                                <input type="text" class="form-control" id="industry" name="industry" value="<?php echo isset($industry) ? htmlspecialchars($industry) : ''; ?>" required>
                                <div class="invalid-feedback">Área de atuação é obrigatória.</div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <!-- Logo Upload -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Logo da Empresa</h3>
                            
                            <div class="mb-3">
                                <label for="logo" class="form-label">Logo (opcional)</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png" data-preview="logo-preview">
                                <div class="form-text">Formato JPG ou PNG, tamanho máximo de 5MB.</div>
                            </div>
                            
                            <div class="mb-3 text-center">
                                <img id="logo-preview" src="https://via.placeholder.com/150" alt="Preview" class="img-thumbnail company-logo" style="display: none;">
                            </div>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">Informações de Contato</h3>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Endereço Completo *</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                                <div class="invalid-feedback">Endereço é obrigatório.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="(xx) xxxxx-xxxx" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required>
                                <div class="invalid-feedback">Telefone é obrigatório.</div>
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
                    <p>Ou <a href="signup_freelancer.php">cadastre-se como freelancer</a>.</p>
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
