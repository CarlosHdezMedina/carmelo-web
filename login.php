<?php

require __DIR__ . '/includes/bootstrap.php';

if (is_admin_logged_in()) {
    redirect('/admin.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (attempt_login($username, $password)) {
        $redirectTo = $_SESSION['login_redirect'] ?? '/admin.php';
        unset($_SESSION['login_redirect']);
        redirect($redirectTo);
    }

    $error = 'Usuario o contrasena incorrectos.';
}

render_header('Acceso administracion', 'admin');
?>
      </header>
      <main>
        <section class="section login-section">
          <div class="login-card">
            <p class="eyebrow">Acceso privado</p>
            <h2 class="login-title">Panel de administracion</h2>
            <p class="section-text">Introduce tus credenciales para gestionar obras, imagen destacada y filtros del catalogo.</p>

            <?php if ($error !== ''): ?>
              <p class="message message-error"><?= e($error) ?></p>
            <?php endif; ?>

            <form class="artwork-form login-form" method="post" action="<?= e(base_url('/login.php')) ?>">
              <label>
                Usuario
                <input type="text" name="username" autocomplete="username" required />
              </label>

              <label>
                Contrasena
                <input type="password" name="password" autocomplete="current-password" required />
              </label>

              <button class="button button-primary form-span" type="submit">Entrar</button>
            </form>
          </div>
        </section>
      </main>
<?php render_footer(); ?>
