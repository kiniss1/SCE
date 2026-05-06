<?php
session_start();

define('AUTH_USER', 'gem');
define('AUTH_PASS', 'gem123');
define('AUTH_SESSION_KEY', 'gem_autenticado');

// Já logado
if (!empty($_SESSION[AUTH_SESSION_KEY])) {
    header('Location: /index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['senha']   ?? '');
    if ($user === AUTH_USER && $pass === AUTH_PASS) {
        $_SESSION[AUTH_SESSION_KEY] = true;
        header('Location: /index.php');
        exit;
    } else {
        $erro = 'Usuário ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login — GEM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --primary:#0b4b80; --primary-light:#1565c0; --primary-dark:#082f52; --danger:#e53935; --border:#d6e8f7; --text:#1a2535; --text-muted:#607080; }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Roboto',Arial,sans-serif; background:linear-gradient(135deg,#082f52 0%,#0b4b80 45%,#1565c0 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
    .login-box { background:#fff; border-radius:16px; box-shadow:0 8px 36px rgba(11,75,128,0.25); width:100%; max-width:380px; overflow:hidden; animation:slideUp 0.3s ease; }
    @keyframes slideUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:none} }
    .login-header { background:linear-gradient(135deg,#082f52,#1565c0); padding:32px 28px 28px; text-align:center; }
    .login-logo { width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px; }
    .login-logo .material-icons { font-size:2rem;color:#fff; }
    .login-header h1 { font-size:1.3rem;font-weight:800;color:#fff; }
    .login-header p { font-size:0.82rem;color:rgba(255,255,255,0.65);margin-top:4px; }
    .login-body { padding:28px; }
    .field { margin-bottom:16px; }
    .field label { display:block;font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px; }
    .field-wrap { position:relative;display:flex;align-items:center; }
    .field-wrap .material-icons { position:absolute;left:12px;font-size:1.1rem;color:var(--text-muted);pointer-events:none; }
    .field input { width:100%;padding:11px 14px 11px 38px;border:1.5px solid var(--border);border-radius:10px;font-size:0.97rem;color:var(--text);background:#fafcff;outline:none;transition:all 0.18s;font-family:inherit; }
    .field input:focus { border-color:var(--primary-light);background:#fff;box-shadow:0 0 0 3px rgba(21,101,192,0.1); }
    .toggle-senha { position:absolute;right:12px;background:none;border:none;cursor:pointer;color:var(--text-muted);display:flex;align-items:center;padding:4px; }
    .erro { background:#ffebee;border:1px solid #ffcdd2;border-radius:9px;padding:10px 14px;font-size:0.87rem;color:var(--danger);font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px; }
    .btn-login { width:100%;padding:13px;background:linear-gradient(135deg,var(--primary-dark),var(--primary-light));color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 14px rgba(21,101,192,0.35);transition:all 0.18s;min-height:48px; }
    .btn-login:hover { box-shadow:0 6px 20px rgba(21,101,192,0.45); }
    .login-footer { text-align:center;padding:14px 28px 20px;font-size:0.78rem;color:var(--text-muted);border-top:1px solid var(--border); }
  </style>
</head>
<body>
<div class="login-box">
  <div class="login-header">
    <div class="login-logo"><span class="material-icons">security</span></div>
    <h1>GEM — Controle de EPI</h1>
    <p>Gestão de Estoque e Materiais</p>
  </div>
  <div class="login-body">
    <?php if ($erro): ?>
    <div class="erro"><span class="material-icons" style="font-size:1rem;">error</span><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="field">
        <label>Usuário</label>
        <div class="field-wrap">
          <span class="material-icons">person</span>
          <input type="text" name="usuario" placeholder="Digite seu usuário" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" autocomplete="username" required autofocus>
        </div>
      </div>
      <div class="field">
        <label>Senha</label>
        <div class="field-wrap">
          <span class="material-icons">lock</span>
          <input type="password" id="campo-senha" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required>
          <button type="button" class="toggle-senha" onclick="toggleSenha()"><span class="material-icons" id="icone-senha">visibility</span></button>
        </div>
      </div>
      <button type="submit" class="btn-login"><span class="material-icons">login</span>Entrar</button>
    </form>
  </div>
  <div class="login-footer">Sistema restrito — acesso autorizado apenas</div>
</div>
<script>
function toggleSenha() {
  const c = document.getElementById('campo-senha');
  const i = document.getElementById('icone-senha');
  c.type = c.type === 'password' ? 'text' : 'password';
  i.textContent = c.type === 'password' ? 'visibility' : 'visibility_off';
}
</script>
</body>
</html>
