<?php

require __DIR__ . '/includes/bootstrap.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$artwork = $slug !== '' ? fetch_artwork_by_slug($slug) : null;

if (!$artwork) {
    http_response_code(404);
    render_header('Obra no encontrada', 'home');
    ?>
      </header>
      <main>
        <section class="section">
          <div class="empty-state">La obra solicitada no existe o ya no esta disponible.</div>
        </section>
      </main>
    <?php
    render_footer();
    exit;
}

render_header($artwork['title'], 'home');
?>
      </header>
      <main>
        <section class="section artwork-detail-section">
          <div class="artwork-detail-layout">
            <div class="artwork-detail-image-wrap">
              <img class="artwork-detail-image" src="<?= e(base_url($artwork['image_path'])) ?>" alt="<?= e($artwork['title']) ?>" />
            </div>
            <div class="dialog-body">
              <p class="eyebrow">Ficha de la obra</p>
              <h2><?= e($artwork['title']) ?></h2>
              <p class="dialog-meta"><strong>Ano:</strong> <?= e($artwork['year'] ?: 'No indicado') ?></p>
              <p class="dialog-meta"><strong>Dimensiones:</strong> <?= e($artwork['dimensions']) ?></p>
              <p class="dialog-meta"><strong>Tecnica:</strong> <?= e($artwork['technique']) ?></p>
              <div class="tag-list">
                <?php foreach ($artwork['tags'] as $tag): ?>
                  <span class="tag"><?= e($tag) ?></span>
                <?php endforeach; ?>
              </div>
              <p class="dialog-description"><?= nl2br(e($artwork['description'])) ?></p>
              <a class="button button-secondary" href="<?= e(base_url('/index.php')) ?>">Volver a la galeria</a>
            </div>
          </div>
        </section>
      </main>
<?php render_footer(); ?>
