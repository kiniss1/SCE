<?php
session_start();

define('SESSION_TIMEOUT', 1800); // 30 minutos em segundos

// Verificar timeout de inatividade
if (!empty($_SESSION['logado'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: ?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Login
if (empty($_SESSION['logado'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['u'] ?? '') === 'admin' && ($_POST['p'] ?? '') === 'admin') {
        $_SESSION['logado'] = true;
        $_SESSION['last_activity'] = time();
    } else {
        $expired = isset($_GET['expired']);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login — GEM</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Roboto,Arial,sans-serif;background:linear-gradient(135deg,#082f52,#1565c0);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.box{background:#fff;border-radius:16px;padding:36px 32px;width:100%;max-width:380px;box-shadow:0 8px 36px rgba(8,47,82,0.25);animation:up .3s ease;}
@keyframes up{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
.logo{text-align:center;margin-bottom:24px;}
.logo-icon{width:56px;height:56px;background:rgba(21,101,192,0.1);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}
.logo-icon .material-icons{font-size:2rem;color:#1565c0;}
.logo h1{font-size:1.25rem;font-weight:800;color:#0b4b80;}
.logo p{font-size:0.8rem;color:#607080;margin-top:4px;}
label{display:block;font-size:0.74rem;font-weight:700;color:#607080;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;margin-top:14px;}
.field-wrap{position:relative;display:flex;align-items:center;}
.field-wrap .material-icons{position:absolute;left:12px;font-size:1.1rem;color:#b0bec5;pointer-events:none;}
input[type=text],input[type=password]{width:100%;padding:11px 14px 11px 38px;border:1.5px solid #d6e8f7;border-radius:10px;font-size:1rem;outline:none;font-family:inherit;transition:all .18s;}
input:focus{border-color:#1565c0;box-shadow:0 0 0 3px rgba(21,101,192,0.1);}
.toggle-btn{position:absolute;right:10px;background:none;border:none;cursor:pointer;color:#b0bec5;padding:4px;display:flex;}
button[type=submit]{margin-top:22px;width:100%;padding:13px;background:linear-gradient(135deg,#082f52,#1565c0);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 14px rgba(21,101,192,0.35);transition:all .18s;min-height:48px;}
button[type=submit]:hover{box-shadow:0 6px 20px rgba(21,101,192,0.45);}
.erro{background:#ffebee;border:1px solid #ffcdd2;border-radius:9px;padding:10px 14px;font-size:0.87rem;color:#e53935;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.aviso{background:#fff8e1;border:1px solid #ffe082;border-radius:9px;padding:10px 14px;font-size:0.87rem;color:#f57c00;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.footer{text-align:center;padding-top:16px;font-size:0.76rem;color:#b0bec5;border-top:1px solid #f0f5fb;margin-top:16px;}
</style></head><body>
<div class="box">
  <div class="logo">
    <div class="logo-icon"><span class="material-icons">security</span></div>
    <h1>GEM — Controle de EPI</h1>
    <p>Gestão de Estoque e Materiais</p>
  </div>';
        if ($expired) {
            echo '<div class="aviso"><span class="material-icons" style="font-size:1rem;">timer_off</span>Sessão expirada por inatividade. Faça login novamente.</div>';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo '<div class="erro"><span class="material-icons" style="font-size:1rem;">error</span>Usuário ou senha incorretos.</div>';
        }
        echo '
  <form method="POST">
    <label>Usuário</label>
    <div class="field-wrap">
      <span class="material-icons">person</span>
      <input type="text" name="u" autofocus autocomplete="username" placeholder="Digite seu usuário">
    </div>
    <label>Senha</label>
    <div class="field-wrap">
      <span class="material-icons">lock</span>
      <input type="password" id="pwd" name="p" autocomplete="current-password" placeholder="Digite sua senha">
      <button type="button" class="toggle-btn" onclick="var i=document.getElementById(\'pwd\');i.type=i.type===\'password\'?\'text\':\'password\';">
        <span class="material-icons" style="font-size:1.1rem;">visibility</span>
      </button>
    </div>
    <button type="submit"><span class="material-icons">login</span>Entrar</button>
  </form>
  <div class="footer">Sistema restrito — acesso autorizado apenas</div>
</div>
</body></html>';
        exit;
    }
}
// Tempo restante para o frontend
$tempo_restante = SESSION_TIMEOUT - (time() - ($_SESSION['last_activity'] ?? time()));
?>
