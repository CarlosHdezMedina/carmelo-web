<?php

require __DIR__ . '/includes/bootstrap.php';

$allArtworks = fetch_all_artworks();
$selectedTag = isset($_GET['tag']) ? trim((string) $_GET['tag']) : '';
$selectedYear = isset($_GET['year']) ? trim((string) $_GET['year']) : '';
$visibleArtworks = filter_artworks($allArtworks, $selectedTag, $selectedYear);
$featured = featured_artwork($allArtworks);

render_header('Galeria', 'home');
?>
        <section class="hero-content">
          <div class="hero-copy">
            <p class="eyebrow">Pintura contemporanea</p>
            <h1>Obras para recorrer la memoria, el deseo y la imaginacion.</h1>
            <p class="intro">
              La web de Carmelo Espinosa reune su galeria, una obra destacada,
              una pagina de biografia y un acceso privado para la gestion del catalogo.
            </p>
            <div class="hero-actions">
              <a class="button button-primary" href="#galeria">Explorar obras</a>
              <a class="button button-secondary" href="<?= e(base_url('/biografia.php')) ?>">Biografia</a>
            </div>
          </div>

          <aside class="hero-note">
            <p class="note-label">Presentacion breve</p>
            <p>
              Un universo plastico en el que conviven pintura, relato visual y una
              iconografia personal que atraviesa distintos tiempos y atmosferas.
            </p>
          </aside>
        </section>
      </header>

      <main>
        <?php if ($featured): ?>
        <section class="section featured-section">
          <div class="featured-artwork">
            <div class="featured-copy">
              <p class="eyebrow">Obra destacada</p>
              <h2><?= e($featured['title']) ?></h2>
              <p class="section-text"><?= e($featured['description'] ?: 'Una pieza central dentro del universo plastico del artista.') ?></p>
              <a class="button button-primary" href="<?= e(base_url('/obra.php?slug=' . urlencode($featured['slug']))) ?>">Ver obra completa</a>
            </div>
            <a class="featured-image-wrap" href="<?= e(base_url('/obra.php?slug=' . urlencode($featured['slug']))) ?>">
              <img class="featured-image" src="<?= e(base_url($featured['image_path'])) ?>" alt="<?= e($featured['title']) ?>" />
            </a>
          </div>
        </section>
        <?php endif; ?>

        <section class="section section-gallery" id="galeria">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Galeria</p>
              <h2>Todas las obras</h2>
            </div>
            <p class="section-text">
              Filtra por etiqueta o por ano para descubrir la produccion del artista desde distintas entradas.
            </p>
          </div>

          <form class="filter-form" method="get" action="<?= e(base_url('/index.php')) ?>">
            <label>
              Etiqueta
              <select name="tag">
                <option value="">Todas</option>
                <?php foreach (all_tags($allArtworks) as $tag): ?>
                  <option value="<?= e($tag) ?>"<?= $selectedTag === $tag ? ' selected' : '' ?>><?= e($tag) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              Ano
              <select name="year">
                <option value="">Todos</option>
                <?php foreach (all_years($allArtworks) as $year): ?>
                  <option value="<?= e($year) ?>"<?= $selectedYear === $year ? ' selected' : '' ?>><?= e($year) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <div class="filter-actions">
              <button class="button button-primary" type="submit">Filtrar</button>
              <a class="button button-secondary" href="<?= e(base_url('/index.php')) ?>">Limpiar</a>
            </div>
          </form>

          <div class="gallery-grid">
            <?php if (!$visibleArtworks): ?>
              <div class="empty-state">No hay obras para los filtros seleccionados.</div>
            <?php endif; ?>

            <?php foreach ($visibleArtworks as $artwork): ?>
              <a class="artwork-card" href="<?= e(base_url('/obra.php?slug=' . urlencode($artwork['slug']))) ?>">
                <div class="artwork-image-wrap">
                  <img class="artwork-image" src="<?= e(base_url($artwork['image_path'])) ?>" alt="<?= e($artwork['title']) ?>" loading="lazy" />
                </div>
                <div class="artwork-body">
                  <h3 class="artwork-title"><?= e($artwork['title']) ?></h3>
                  <p class="artwork-caption"><?= e($artwork['year'] ?: 'Ano no indicado') ?></p>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
      </main>
<?php render_footer(); ?>
