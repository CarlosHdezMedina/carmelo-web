<?php

require __DIR__ . '/includes/bootstrap.php';
require_admin();

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editingArtwork = $editingId ? fetch_artwork_by_id($editingId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    try {
        if ($action === 'create') {
            create_artwork([
                'title' => $_POST['title'] ?? '',
                'year' => $_POST['year'] ?? '',
                'dimensions' => $_POST['dimensions'] ?? '',
                'technique' => $_POST['technique'] ?? '',
                'description' => $_POST['description'] ?? '',
                'tags' => $_POST['tags'] ?? '',
                'is_featured' => $_POST['is_featured'] ?? '',
            ], $_FILES['image'] ?? []);

            flash('success', 'Obra creada correctamente.');
            redirect('/admin.php');
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            update_artwork($id, [
                'title' => $_POST['title'] ?? '',
                'year' => $_POST['year'] ?? '',
                'dimensions' => $_POST['dimensions'] ?? '',
                'technique' => $_POST['technique'] ?? '',
                'description' => $_POST['description'] ?? '',
                'tags' => $_POST['tags'] ?? '',
                'is_featured' => $_POST['is_featured'] ?? '',
            ], $_FILES['image'] ?? []);

            flash('success', 'Obra actualizada correctamente.');
            redirect('/admin.php?edit=' . $id);
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            delete_artwork($id);
            flash('success', 'Obra borrada correctamente.');
            redirect('/admin.php');
        }
    } catch (RuntimeException $exception) {
        flash('error', $exception->getMessage());
        redirect('/admin.php' . ($editingId ? '?edit=' . $editingId : ''));
    }
}

$allArtworks = fetch_all_artworks();
$flash = consume_flash();
$allTags = all_tags($allArtworks);

render_header('Administracion', 'admin');
?>
        <section class="hero-content admin-layout">
          <div class="hero-copy">
            <p class="eyebrow">Administracion</p>
            <h1>Gestion privada del catalogo y de la obra destacada.</h1>
            <p class="intro">
              Desde este panel puedes crear, editar y borrar obras, asignar ano,
              tecnica, etiquetas e indicar cual debe aparecer destacada en la portada.
            </p>
          </div>

          <aside class="hero-note">
            <p class="note-label">Sesion activa</p>
            <form action="<?= e(base_url('/logout.php')) ?>" method="post">
              <button class="button button-secondary" type="submit">Cerrar sesion</button>
            </form>
          </aside>
        </section>
      </header>

      <main>
        <?php if ($flash): ?>
          <section class="section">
            <p class="message message-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
          </section>
        <?php endif; ?>

        <section class="section">
          <div class="section-heading">
            <div>
              <p class="eyebrow"><?= $editingArtwork ? 'Editar obra' : 'Nueva obra' ?></p>
              <h2><?= $editingArtwork ? e($editingArtwork['title']) : 'Formulario de alta' ?></h2>
            </div>
            <p class="section-text">La informacion se guarda en MySQL y las imagenes se suben a la carpeta publica del hosting.</p>
          </div>

          <form class="artwork-form" method="post" enctype="multipart/form-data" action="<?= e(base_url('/admin.php' . ($editingArtwork ? '?edit=' . $editingArtwork['id'] : ''))) ?>">
            <input type="hidden" name="action" value="<?= $editingArtwork ? 'update' : 'create' ?>" />
            <?php if ($editingArtwork): ?>
              <input type="hidden" name="id" value="<?= (int) $editingArtwork['id'] ?>" />
            <?php endif; ?>

            <label>
              Titulo
              <input type="text" name="title" value="<?= e($editingArtwork['title'] ?? '') ?>" required />
            </label>

            <label>
              Ano
              <input type="number" name="year" min="1900" max="2100" value="<?= e((string) ($editingArtwork['year'] ?? '')) ?>" />
            </label>

            <label>
              Dimensiones
              <input type="text" name="dimensions" value="<?= e($editingArtwork['dimensions'] ?? '') ?>" required />
            </label>

            <label>
              Tecnica
              <input type="text" name="technique" value="<?= e($editingArtwork['technique'] ?? '') ?>" required />
            </label>

            <label class="form-span">
              Etiquetas
              <input type="text" name="tags" list="existing-tags" value="<?= e(isset($editingArtwork['tags']) ? implode(', ', $editingArtwork['tags']) : '') ?>" placeholder="retratos, amores imposibles, seres carmelianos" required />
              <datalist id="existing-tags">
                <?php foreach ($allTags as $tag): ?>
                  <option value="<?= e($tag) ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </label>

            <label class="form-span">
              Descripcion
              <textarea name="description" rows="5"><?= e($editingArtwork['description'] ?? '') ?></textarea>
            </label>

            <label class="checkbox-field form-span">
              <input type="checkbox" name="is_featured" value="1"<?= !empty($editingArtwork['is_featured']) ? ' checked' : '' ?> />
              <span>Marcar como obra destacada</span>
            </label>

            <label class="file-field form-span">
              <?= $editingArtwork ? 'Sustituir imagen' : 'Imagen del cuadro' ?>
              <input type="file" name="image" accept="image/*"<?= $editingArtwork ? '' : ' required' ?> />
            </label>

            <div class="form-actions form-span">
              <button class="button button-primary" type="submit"><?= $editingArtwork ? 'Guardar cambios' : 'Guardar obra' ?></button>
              <?php if ($editingArtwork): ?>
                <a class="button button-secondary" href="<?= e(base_url('/admin.php')) ?>">Cancelar edicion</a>
              <?php endif; ?>
            </div>
          </form>
        </section>

        <section class="section">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Catalogo actual</p>
              <h2>Obras registradas</h2>
            </div>
            <p class="section-text">La obra destacada aparece identificada dentro del listado.</p>
          </div>

          <div class="admin-grid">
            <?php foreach ($allArtworks as $artwork): ?>
              <article class="admin-card">
                <img class="admin-thumb" src="<?= e(base_url($artwork['image_path'])) ?>" alt="<?= e($artwork['title']) ?>" />
                <div class="admin-card-body">
                  <h3><?= e($artwork['title']) ?></h3>
                  <p><?= e($artwork['year'] ?: 'Ano no indicado') ?></p>
                  <p><?= e($artwork['dimensions']) ?></p>
                  <p><?= e($artwork['technique']) ?></p>
                  <?php if ($artwork['is_featured']): ?>
                    <p class="featured-badge">Obra destacada</p>
                  <?php endif; ?>
                  <div class="tag-list">
                    <?php foreach ($artwork['tags'] as $tag): ?>
                      <span class="tag"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                  </div>
                  <div class="card-actions">
                    <a class="button button-secondary" href="<?= e(base_url('/admin.php?edit=' . $artwork['id'])) ?>">Editar</a>
                    <form method="post" action="<?= e(base_url('/admin.php')) ?>" onsubmit="return confirm('Se borrara esta obra. Quieres continuar?');">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id" value="<?= (int) $artwork['id'] ?>" />
                      <button class="button button-danger" type="submit">Borrar</button>
                    </form>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      </main>
<?php render_footer(); ?>
