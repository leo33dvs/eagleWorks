<?php
include 'includes/header.php';
?>

<!-- Hero Section with Eagle Background -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="mb-3">Conectando Talentos e Oportunidades</h1>
            <p class="lead mb-4">Encontre os melhores freelancers ou contrate talentos para sua empresa.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="signup_freelancer.php" class="btn btn-light btn-lg">Sou Freelancer</a>
                <a href="signup_company.php" class="btn btn-gold btn-lg">Sou Empresa</a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Por que escolher a EagleWorks?</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-search fa-3x text-primary mb-3"></i>
                        <h3 class="card-title">Busca Inteligente</h3>
                        <p class="card-text">Encontre o profissional ideal para sua necessidade com nosso sistema de busca avançado.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-star fa-3x text-gold mb-3"></i>
                        <h3 class="card-title">Avaliações Verificadas</h3>
                        <p class="card-text">Conheça a reputação de cada profissional através de avaliações reais de clientes anteriores.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h3 class="card-title">Segurança</h3>
                        <p class="card-text">Garantimos a segurança dos seus dados e a autenticidade dos perfis cadastrados.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Como Funciona</h2>
        
        <div class="row align-items-center mb-5">
            <div class="col-md-6 order-md-2">
                <img src="https://via.placeholder.com/600x400" alt="Cadastre-se" class="img-fluid rounded shadow">
            </div>
            <div class="col-md-6 order-md-1">
                <h3 class="mb-3"><span class="badge bg-primary rounded-circle me-2">1</span> Cadastre-se</h3>
                <p class="lead">Crie sua conta como freelancer ou empresa em poucos minutos.</p>
                <p>Preencha seus dados, adicione informações sobre sua experiência ou empresa, e comece a utilizar nossa plataforma.</p>
            </div>
        </div>
        
        <div class="row align-items-center mb-5">
            <div class="col-md-6">
                <img src="https://via.placeholder.com/600x400" alt="Conecte-se" class="img-fluid rounded shadow">
            </div>
            <div class="col-md-6">
                <h3 class="mb-3"><span class="badge bg-primary rounded-circle me-2">2</span> Conecte-se</h3>
                <p class="lead">Encontre oportunidades ou profissionais qualificados.</p>
                <p>Use nossa ferramenta de busca para encontrar freelancers por profissão e região, ou aguarde empresas encontrarem seu perfil.</p>
            </div>
        </div>
        
        <div class="row align-items-center">
            <div class="col-md-6 order-md-2">
                <img src="https://via.placeholder.com/600x400" alt="Avalie" class="img-fluid rounded shadow">
            </div>
            <div class="col-md-6 order-md-1">
                <h3 class="mb-3"><span class="badge bg-primary rounded-circle me-2">3</span> Avalie</h3>
                <p class="lead">Contribua para a comunidade com avaliações honestas.</p>
                <p>Após uma experiência, avalie a empresa ou o freelancer para ajudar outros usuários a tomarem decisões melhores.</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Professions -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Categorias Populares</h2>
        
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <a href="search.php?profession=Barman" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <i class="fas fa-cocktail fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Barman</h5>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-6 col-md-3">
                <a href="search.php?profession=Garçom" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <i class="fas fa-concierge-bell fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Garçom</h5>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-6 col-md-3">
                <a href="search.php?profession=Cozinheiro" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <i class="fas fa-utensils fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Cozinheiro</h5>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-6 col-md-3">
                <a href="search.php?profession=Diarista" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <i class="fas fa-broom fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Diarista</h5>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-6 col-md-3">
                <a href="search.php?profession=Designer" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <i class="fas fa-palette fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Designer</h5>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-6 col-md-3">
                <a href="search.php?profession=Desenvolvedor" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <i class="fas fa-laptop-code fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Desenvolvedor</h5>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-6 col-md-3">
                <a href="search.php?profession=Fotógrafo" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <i class="fas fa-camera fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Fotógrafo</h5>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-6 col-md-3">
                <a href="search.php" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <i class="fas fa-ellipsis-h fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Ver Todos</h5>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-light">
    <div class="container text-center">
        <h2 class="mb-4">Pronto para começar?</h2>
        <p class="lead mb-4">Junte-se a milhares de freelancers e empresas que já estão conectados na EagleWorks.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="signup_freelancer.php" class="btn btn-light btn-lg">Cadastrar como Freelancer</a>
            <a href="signup_company.php" class="btn btn-gold btn-lg">Cadastrar como Empresa</a>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
?>
