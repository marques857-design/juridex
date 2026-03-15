<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Advocacia online bem estruturada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --azul-fundo: #1c1f3b; --azul-vibrante: #0084ff; --texto-claro: #f8f9fa; }
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--texto-claro); padding-top: 80px; }
        
        .navbar-custom { background: var(--azul-fundo); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 15px 0;}
        .contato-top { color: rgba(255,255,255,0.8); font-size: 0.9rem; }
        .contato-top strong { color: white; }
        .contato-top a { color: var(--azul-vibrante); text-decoration: none; font-weight: bold; }
        .contato-top a:hover { color: white; }
        
        .hero-section { background: var(--azul-fundo); padding: 60px 0 100px 0; text-align: center; color: white; position: relative; }
        
        /* O SEGREDO ESTÁ AQUI: mix-blend-mode: lighten APAGA O FUNDO CINZA DA SUA IMAGEM */
        .logo-img-hero { 
            max-height: 220px; 
            width: 100%; 
            object-fit: contain; 
            margin-bottom: 40px; 
            mix-blend-mode: lighten; 
        }
        
        .hero-title { font-size: 3.5rem; font-weight: 900; color: white; letter-spacing: -1px; margin-bottom: 15px; }
        .hero-title span { color: var(--azul-vibrante); }
        .hero-subtitle { font-size: 1.2rem; color: #a9a9a9; max-width: 700px; margin: 0 auto;}
        
        .pricing-section { padding: 80px 0 120px 0; background: var(--texto-claro); }
        .pricing-card { background: white; border: 1px solid #e0e0e0; border-radius: 12px; padding: 40px 30px; text-align: center; transition: 0.3s; height: 100%; display: flex; flex-direction: column; justify-content: space-between;}
        .pricing-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); border-color: var(--azul-vibrante); }
        
        .pricing-card.popular { background: white; border: 3px solid var(--azul-vibrante); box-shadow: 0 20px 40px rgba(0,132,255, 0.15);}
        
        .price { font-size: 3.5rem; font-weight: 900; margin: 15px 0; color: var(--azul-fundo); }
        .popular .price { color: var(--azul-vibrante); }
        .price span { font-size: 1.2rem; font-weight: normal; opacity: 0.7; color: #777; }
        
        .btn-plano { border-radius: 8px; padding: 15px; font-weight: bold; font-size: 1.1rem; transition: 0.3s; width: 100%; border: 2px solid var(--azul-fundo); color: var(--azul-fundo); background: transparent; text-decoration: none; display: inline-block;}
        .btn-plano:hover { background: var(--azul-fundo); color: white; }
        
        .popular .btn-plano { background: var(--azul-vibrante); color: white; border: 2px solid var(--azul-vibrante); }
        .popular .btn-plano:hover { background: #006bce; border-color: #006bce;}
        
        footer { background: var(--azul-fundo); padding: 60px 0 30px 0; color: rgba(255,255,255,0.7); }
        .logo-img-footer { max-height: 80px; object-fit: contain; margin-bottom: 20px; mix-blend-mode: lighten; }
        .footer-title { color: white; font-weight: bold; margin-bottom: 20px; font-size: 1.1rem; }
        
        @media (max-width: 768px) {
            .hero-title { font-size: 2.2rem; }
            .logo-img-hero { max-height: 150px; }
            .contato-top { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="contato-top d-flex align-items-center gap-4">
            <div>
                <span class="d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--azul-vibrante);">Atendimento Comercial</span>
                📞 <strong>(71) 98529-2591</strong> <span class="ms-1">- Fabio Marques</span>
            </div>
            <div class="d-none d-lg-block">
                <span class="d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--azul-vibrante);">E-mail Direto</span>
                ✉️ <strong>juridex@hotmail.com</strong>
            </div>
        </div>
        <div>
            <a class="btn btn-outline-light fw-bold px-4 py-2" href="telas/login.php" style="border-radius: 8px; font-size: 0.95rem;">Acessar Sistema ➔</a>
        </div>
    </div>
</nav>

<section class="hero-section">
    <div class="container">
        <div class="text-center">
            <img src="assets/logo.png" alt="JURIDEX" class="logo-img-hero" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <h1 style="display:none; font-weight: 900; color: #0084ff; font-size: 3rem;">JURIDEX</h1>
        </div>
        <h1 class="hero-title">O seu escritório <span>100% estruturado.</span></h1>
        <p class="hero-subtitle">Gestão de processos, finanças automatizadas e Inteligência Artificial. Simplifique sua advocacia hoje e aumente seus lucros.</p>
    </div>
</section>

<section class="pricing-section" id="planos">
    <div class="container">
        <div class="row g-4 justify-content-center align-items-stretch">
            
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card">
                    <div>
                        <h4 class="fw-bold" style="color: var(--azul-fundo);">Básico</h4>
                        <p class="text-muted small">Para o advogado que está começando.</p>
                        <div class="price">R$ 50<span>/mês</span></div>
                        <hr class="text-muted opacity-25">
                        <ul class="list-unstyled text-start mb-4 mt-4 text-secondary small">
                            <li class="mb-3">✔️ Até 50 Processos Ativos</li>
                            <li class="mb-3">✔️ Cadastro de Clientes CRM</li>
                            <li class="mb-3">✔️ Controle Financeiro Simples</li>
                            <li class="mb-3">✔️ Agenda de Prazos Global</li>
                        </ul>
                    </div>
                    <a href="https://api.whatsapp.com/send?phone=5571985292591&text=Ol%C3%A1%2C%20Fabio!%20Acessei%20o%20site%20do%20JURIDEX%20e%20tenho%20interesse%20em%20assinar%20o%20*Plano%20B%C3%A1sico%20(R%24%2050%2Fm%C3%AAs)*.%20Como%20podemos%20prosseguir%3F" target="_blank" class="btn-plano">Assinar Plano Básico</a>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card popular">
                    <div>
                        <span class="badge bg-primary text-white mb-2 px-3 py-2 fw-bold" style="background-color: var(--azul-vibrante) !important; border-radius: 8px;">O MAIS COMPLETO</span>
                        <h4 class="fw-bold" style="color: var(--azul-fundo);">Pro</h4>
                        <p class="text-muted small">Automação total e IA ativada.</p>
                        <div class="price">R$ 100<span>/mês</span></div>
                        <hr class="text-muted opacity-25">
                        <ul class="list-unstyled text-start mb-4 mt-4 text-secondary small">
                            <li class="mb-3 text-dark">✔️ Processos <b>Ilimitados</b></li>
                            <li class="mb-3 text-dark">✔️ Finanças + <b>WhatsApp PIX</b></li>
                            <li class="mb-3 text-dark">✔️ <b>Acesso à Inteligência Artificial</b></li>
                            <li class="mb-3 text-dark">✔️ Mesclador de PDFs Nativo</li>
                        </ul>
                    </div>
                    <a href="https://api.whatsapp.com/send?phone=5571985292591&text=Ol%C3%A1%2C%20Fabio!%20Acessei%20o%20site%20do%20JURIDEX%20e%20quero%20assinar%20o%20*Plano%20Pro%20(R%24%20100%2Fm%C3%AAs)*%20com%20Intelig%C3%AAncia%20Artificial.%20Como%20fa%C3%A7o%20o%20pagamento%3F" target="_blank" class="btn-plano-popular">Assinar Plano Pro</a>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card">
                    <div>
                        <h4 class="fw-bold" style="color: var(--azul-fundo);">VIP</h4>
                        <p class="text-muted small">Para médias bancas e escritórios.</p>
                        <div class="price">R$ 300<span>/mês</span></div>
                        <hr class="text-muted opacity-25">
                        <ul class="list-unstyled text-start mb-4 mt-4 text-secondary small">
                            <li class="mb-3">✔️ <b>Tudo do plano Pro</b></li>
                            <li class="mb-3">✔️ Multi-usuários (Equipe)</li>
                            <li class="mb-3">✔️ Permissões de Acesso</li>
                            <li class="mb-3">✔️ Suporte Prioritário Direto</li>
                        </ul>
                    </div>
                    <a href="https://api.whatsapp.com/send?phone=5571985292591&text=Ol%C3%A1%2C%20Fabio!%20Acessei%20o%20site%20do%20JURIDEX%20e%20desejo%20assinar%20o%20*Plano%20VIP%20(R%24%20300%2Fm%C3%AAs)*%20para%20o%20meu%20escrit%C3%B3rio.%20Aguardo%20o%20seu%20retorno!" target="_blank" class="btn-plano">Assinar Plano VIP</a>
                </div>
            </div>

        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="row text-start g-4 mb-4">
            <div class="col-md-5">
                <img src="assets/logo.png" alt="JURIDEX" class="logo-img-footer" onerror="this.style.display='none';">
                <p class="small pe-md-4 mt-2">O software jurídico definitivo. Desenvolvido para escritórios modernos que buscam gestão impecável e alta lucratividade através da automação.</p>
            </div>
            <div class="col-md-3">
                <h5 class="footer-title">Links Rápidos</h5>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#planos" style="color: inherit; text-decoration: none;">Planos e Preços</a></li>
                    <li class="mb-2"><a href="telas/login.php" style="color: inherit; text-decoration: none;">Acesso de Clientes</a></li>
                    <li class="mb-2"><a href="#" style="color: inherit; text-decoration: none;">Termos de Uso</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="footer-title">Fale com o Fundador</h5>
                <ul class="list-unstyled small">
                    <li class="mb-3"><strong class="d-block text-white">CEO: Fabio Marques</strong></li>
                    <li class="mb-3">
                        <strong class="text-white">Telefone / WhatsApp:</strong><br>
                        <a href="https://api.whatsapp.com/send?phone=5571985292591" target="_blank" style="color: var(--azul-vibrante); text-decoration: none; font-size: 1.1rem; font-weight: bold;">(71) 98529-2591</a>
                    </li>
                    <li class="mb-2">
                        <strong class="text-white">E-mail Comercial:</strong><br>
                        <a href="mailto:juridex@hotmail.com" style="color: inherit; text-decoration: none;">juridex@hotmail.com</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-top border-secondary pt-4 mt-4 text-center">
            <p class="small mb-0" style="opacity: 0.5;">© <?php echo date('Y'); ?> JURIDEX Software as a Service. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>