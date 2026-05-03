<?php

require __DIR__ . '/includes/bootstrap.php';
$bio = require __DIR__ . '/content/biography.php';

render_header('Biografia', 'biography');
?>
        <section class="hero-content">
          <div class="hero-copy">
            <p class="eyebrow">Biografia</p>
            <h1>Trayectoria, documentos y contexto del artista.</h1>
            <p class="intro">
              Una pagina dedicada a presentar a Carmelo Espinosa, su imagen de autor
              y la documentacion que acompana a su trabajo.
            </p>
          </div>

          <aside class="hero-note">
            <p class="note-label">Documentacion</p>
            <p>
              Aqui se integran su fotografia, un texto de presentacion y acceso al
              statement y al curriculum.
            </p>
          </aside>
        </section>
      </header>

      <main>
        <section class="section biography-section">
          <div class="biography-layout">
            <div class="biography-image-wrap">
              <img class="biography-image" src="<?= e(base_url('/assets/biography/carmelo-espinosa.jpg')) ?>" alt="Retrato de Carmelo Espinosa" />
            </div>
            <div class="biography-text">
              <p class="eyebrow">Carmelo Espinosa</p>
              <h2>Biografia</h2>
              <?php foreach ($bio['intro'] as $paragraph): ?>
                <p class="section-text"><?= e($paragraph) ?></p>
              <?php endforeach; ?>
            </div>
          </div>
        </section>

        <section class="section biography-docs">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Statement</p>
              <h2>Texto del artista</h2>
            </div>
            <p class="section-text"><?= e($bio['statement_intro']) ?></p>
          </div>

          <div class="biography-text statement-text">
            <?php foreach ($bio['statement_paragraphs'] as $paragraph): ?>
              <p class="section-text"><?= e($paragraph) ?></p>
            <?php endforeach; ?>
          </div>

          <div class="doc-actions">
            <a class="button button-primary" target="_blank" rel="noreferrer" href="<?= e(base_url('/assets/biography/statement-carmelo-espinosa.pdf')) ?>">Abrir statement en PDF</a>
          </div>

          <object class="pdf-embed" data="<?= e(base_url('/assets/biography/statement-carmelo-espinosa.pdf')) ?>" type="application/pdf">
            <p>No se pudo mostrar el PDF. Puedes descargarlo desde el enlace superior.</p>
          </object>
        </section>

        <section class="section biography-docs">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Curriculum</p>
              <h2>CV del artista</h2>
            </div>
            <p class="section-text"><?= e($bio['cv_intro']) ?></p>
          </div>

          <div class="biography-text statement-text">
            <p class="eyebrow">Perfil</p>
            <p class="section-text"><?= e($bio['cv_profile']) ?></p>

            <p class="eyebrow">Contacto</p>
            <ul class="bio-list">
              <?php foreach ($bio['cv_contact'] as $item): ?>
                <li><?= e($item) ?></li>
              <?php endforeach; ?>
            </ul>

            <p class="eyebrow">Exposiciones</p>
            <ul class="bio-list">
              <?php foreach ($bio['cv_exhibitions'] as $item): ?>
                <li><?= e($item) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="doc-actions">
            <a class="button button-primary" target="_blank" rel="noreferrer" href="<?= e(base_url('/assets/biography/cv-carmelo-espinosa.pdf')) ?>">Abrir CV en PDF</a>
          </div>

          <object class="pdf-embed" data="<?= e(base_url('/assets/biography/cv-carmelo-espinosa.pdf')) ?>" type="application/pdf">
            <p>No se pudo mostrar el PDF. Puedes descargarlo desde el enlace superior.</p>
          </object>
        </section>
      </main>
<?php render_footer(); ?>
