<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a company
if (!isLoggedIn() || getUserRole() !== 'COMPANY') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Get current company data
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.email FROM companies c
        JOIN users u ON c.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        redirect('dashboard.php');
    }
    
    // Parse contact info
    $contact = json_decode($company['contact'], true);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $company_name = sanitizeInput($_POST['company_name']);
    $industry = sanitizeInput($_POST['industry']);
    $cnpj = sanitizeInput($_POST['cnpj']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($company_name)) {
        $errors[] = "Nome da empresa é obrigatório.";
    }
    
    if (empty($industry)) {
        $errors[] = "Área de atuação é obrigatória.";
    }
    
    if (empty($cnpj)) {
        $errors[] = "CNPJ é obrigatório.";
    } elseif (strlen(preg_replace('/[^0-9]/', '', $cnpj)) !== 14) {
        $errors[] = "CNPJ inválido.";
    }
    
    if (empty($address)) {
        $errors[] = "Endereço é obrigatório.";
    }
    
    if (empty($phone)) {
        $errors[] = "Telefone é obrigatório.";
    }
    
    // Check if CNPJ already exists (but allow the company to keep its own CNPJ)
    if ($cnpj !== $company['cnpj']) {
        $stmt = $pdo->prepare("SELECT id FROM companies WHERE cnpj = ? AND id != ?");
        $stmt->execute([$cnpj, $company['id']]);
        if ($stmt->fetch()) {
            $errors[] = "Este CNPJ já está cadastrado para outra empresa.";
        }
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
    
    // Handle logo upload
    $logo = $company['logo']; // Keep current logo by default
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $upload = uploadFile($_FILES['logo'], 'assets/uploads/company_logos', ['jpg', 'jpeg', 'png']);
        
        if ($upload['success']) {
            $logo = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }
    
    // If no validation errors, proceed with update
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update contact info
            $contact = json_encode(['email' => $company['email'], 'phone' => $phone]);
            
            // Update company data
            $stmt = $pdo->prepare("
                UPDATE companies 
                SET name = ?, industry = ?, cnpj = ?, address = ?,
                    logo = ?, contact = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $company_name, $industry, $cnpj, $address,
                $logo, $contact, $user_id
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
            
            // Refresh company data
            $stmt = $pdo->prepare("
                SELECT c.*, u.email FROM companies c
                JOIN users u ON c.user_id = u.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Re-parse contact info
            $contact = json_decode($company['contact'], true);
            
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
                <h1 class="card-title mb-4">Editar Perfil da Empresa</h1>
                
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
                
                <ul class="nav nav-tabs mb-4" id="companyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                            Informações da Empresa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                            Segurança
                        </button>
                    </li>
                </ul>
                
                <form method="POST" action="edit_profile_company.php" enctype="multipart/form-data">
                    <div class="tab-content" id="companyTabsContent">
                        <!-- Company Info Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <!-- Logo -->
                                <div class="col-md-4 mb-4">
                                    <div class="text-center">
                                        <img src="assets/uploads/company_logos/<?php echo htmlspecialchars($company['logo']); ?>" 
                                             alt="Company Logo" 
                                             id="logo-preview"
                                             class="company-logo mb-3">
                                        
                                        <div class="mb-3">
                                            <label for="logo" class="form-label">Atualizar Logo</label>
                                            <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png" data-preview="logo-preview">
                                            <div class="form-text">Formato JPG ou PNG, tamanho máximo de 5MB.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Company Details -->
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="company_name" class="form-label">Nome da Empresa *</label>
                                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company['name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="industry" class="form-label">Área de Atuação *</label>
                                            <input type="text" class="form-control" id="industry" name="industry" value="<?php echo htmlspecialchars($company['industry']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="cnpj" class="form-label">CNPJ *</label>
                                            <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="XX.XXX.XXX/XXXX-XX" value="<?php echo htmlspecialchars($company['cnpj']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($company['email']); ?>" readonly>
                                            <div class="form-text">O email não pode ser alterado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="row">
                                <!-- Contact Information -->
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Telefone *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="(xx) xxxxx-xxxx" value="<?php echo htmlspecialchars($contact['phone'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="address" class="form-label">Endereço Completo *</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($company['address']); ?></textarea>
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
                                    <p class="text-muted">Ao excluir sua conta, todos os dados da empresa serão removidos permanentemente.</p>
                                </div>
                                
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                        <i class="fas fa-trash-alt me-2"></i>Excluir Conta da Empresa
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
                <p>Ao excluir a conta da empresa, todos os dados, incluindo avaliações e histórico, serão permanentemente removidos do sistema.</p>
                <p>Tem certeza que deseja excluir a conta da empresa?</p>
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
