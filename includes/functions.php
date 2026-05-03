<?php

function app_config(?string $section = null): mixed
{
    global $config;

    if ($section === null) {
        return $config;
    }

    return $config[$section] ?? null;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $database = app_config('database');
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $database['host'],
        $database['port'],
        $database['name'],
        $database['charset']
    );

    $pdo = new PDO($dsn, $database['user'], $database['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    $baseUrl = rtrim((string) app_config('app')['base_url'], '/');
    return $baseUrl . $path;
}

function normalize_tags(string $rawTags): array
{
    $parts = array_map('trim', explode(',', $rawTags));
    $parts = array_filter($parts, static fn ($tag) => $tag !== '');
    $parts = array_map(static fn ($tag) => mb_strtolower($tag), $parts);
    $parts = array_values(array_unique($parts));
    sort($parts);

    return $parts;
}

function tags_to_json(array $tags): string
{
    return json_encode(array_values($tags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function parse_tags(mixed $value): array
{
    if (is_array($value)) {
        return array_values($value);
    }

    $decoded = json_decode((string) $value, true);
    if (is_array($decoded)) {
        return array_values($decoded);
    }

    return [];
}

function slugify(string $text): string
{
    $text = trim($text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?: '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'obra';
}

function ensure_uploads_dir(): void
{
    $dir = app_config('uploads')['artworks_dir'];
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function create_unique_slug(string $title, ?int $ignoreId = null): string
{
    $slug = slugify($title);
    $candidate = $slug;
    $suffix = 1;

    while (slug_exists($candidate, $ignoreId)) {
        $suffix++;
        $candidate = $slug . '-' . $suffix;
    }

    return $candidate;
}

function slug_exists(string $slug, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM artworks WHERE slug = :slug';
    $params = ['slug' => $slug];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :id';
        $params['id'] = $ignoreId;
    }

    $statement = db()->prepare($sql);
    $statement->execute($params);

    return (int) $statement->fetchColumn() > 0;
}

function map_artwork(array $row): array
{
    $row['tags'] = parse_tags($row['tags_json'] ?? '[]');
    $row['is_featured'] = (bool) ($row['is_featured'] ?? false);
    $row['year'] = $row['year'] !== null ? (int) $row['year'] : null;

    return $row;
}

function fetch_all_artworks(): array
{
    $statement = db()->query('SELECT * FROM artworks ORDER BY is_featured DESC, created_at DESC, id DESC');
    $rows = $statement->fetchAll();

    return array_map('map_artwork', $rows);
}

function fetch_artwork_by_id(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM artworks WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();

    return $row ? map_artwork($row) : null;
}

function fetch_artwork_by_slug(string $slug): ?array
{
    $statement = db()->prepare('SELECT * FROM artworks WHERE slug = :slug LIMIT 1');
    $statement->execute(['slug' => $slug]);
    $row = $statement->fetch();

    return $row ? map_artwork($row) : null;
}

function filter_artworks(array $artworks, ?string $tag, ?string $year): array
{
    return array_values(array_filter($artworks, static function (array $artwork) use ($tag, $year): bool {
        $matchesTag = $tag === null || $tag === '' || in_array($tag, $artwork['tags'], true);
        $matchesYear = $year === null || $year === '' || (string) $artwork['year'] === $year;

        return $matchesTag && $matchesYear;
    }));
}

function all_tags(array $artworks): array
{
    $tags = [];
    foreach ($artworks as $artwork) {
        foreach ($artwork['tags'] as $tag) {
            $tags[$tag] = true;
        }
    }

    $tags = array_keys($tags);
    sort($tags);

    return $tags;
}

function all_years(array $artworks): array
{
    $years = [];
    foreach ($artworks as $artwork) {
        if (!empty($artwork['year'])) {
            $years[(string) $artwork['year']] = true;
        }
    }

    $years = array_keys($years);
    rsort($years);

    return $years;
}

function featured_artwork(array $artworks): ?array
{
    foreach ($artworks as $artwork) {
        if ($artwork['is_featured']) {
            return $artwork;
        }
    }

    return $artworks[0] ?? null;
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'] ?? '/admin.php';
        redirect('/login.php');
    }
}

function attempt_login(string $username, string $password): bool
{
    $admin = app_config('admin');
    $isValid = hash_equals($admin['username'], $username) && hash_equals($admin['password'], $password);

    if ($isValid) {
        $_SESSION['admin_logged_in'] = true;
        session_regenerate_id(true);
    }

    return $isValid;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = compact('type', 'message');
}

function consume_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function save_uploaded_artwork(array $file, string $title): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen.');
    }

    ensure_uploads_dir();

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('El formato de la imagen no es valido.');
    }

    $filename = time() . '-' . slugify($title) . '.' . $extension;
    $target = app_config('uploads')['artworks_dir'] . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('No se pudo guardar la imagen subida.');
    }

    return app_config('uploads')['artworks_public_path'] . '/' . $filename;
}

function delete_image_if_local(?string $imagePath): void
{
    if (!$imagePath || !str_starts_with($imagePath, app_config('uploads')['artworks_public_path'] . '/')) {
        return;
    }

    $filename = basename($imagePath);
    $absolute = app_config('uploads')['artworks_dir'] . '/' . $filename;

    if (is_file($absolute)) {
        unlink($absolute);
    }
}

function enforce_single_featured(?int $featuredId): void
{
    if ($featuredId === null) {
        return;
    }

    $statement = db()->prepare('UPDATE artworks SET is_featured = CASE WHEN id = :id THEN 1 ELSE 0 END');
    $statement->execute(['id' => $featuredId]);
}

function ensure_featured_after_delete(): void
{
    $featuredCount = (int) db()->query('SELECT COUNT(*) FROM artworks WHERE is_featured = 1')->fetchColumn();
    if ($featuredCount > 0) {
        return;
    }

    $nextId = db()->query('SELECT id FROM artworks ORDER BY created_at DESC, id DESC LIMIT 1')->fetchColumn();
    if ($nextId) {
        enforce_single_featured((int) $nextId);
    }
}

function create_artwork(array $data, array $file): int
{
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $dimensions = trim($data['dimensions'] ?? '');
    $technique = trim($data['technique'] ?? '');
    $year = trim($data['year'] ?? '');
    $tags = normalize_tags($data['tags'] ?? '');
    $isFeatured = !empty($data['is_featured']);

    if ($title === '' || $dimensions === '' || $technique === '' || empty($tags)) {
        throw new RuntimeException('Completa titulo, dimensiones, tecnica y etiquetas.');
    }

    $imagePath = save_uploaded_artwork($file, $title);
    if ($imagePath === null) {
        throw new RuntimeException('Debes adjuntar una imagen para la obra.');
    }

    $slug = create_unique_slug($title);
    $statement = db()->prepare(
        'INSERT INTO artworks (slug, title, year, dimensions, technique, description, tags_json, image_path, is_featured, created_at, updated_at)
         VALUES (:slug, :title, :year, :dimensions, :technique, :description, :tags_json, :image_path, :is_featured, NOW(), NOW())'
    );

    $statement->execute([
        'slug' => $slug,
        'title' => $title,
        'year' => $year !== '' ? (int) $year : null,
        'dimensions' => $dimensions,
        'technique' => $technique,
        'description' => $description,
        'tags_json' => tags_to_json($tags),
        'image_path' => $imagePath,
        'is_featured' => $isFeatured ? 1 : 0,
    ]);

    $artworkId = (int) db()->lastInsertId();

    if ($isFeatured) {
        enforce_single_featured($artworkId);
    }

    return $artworkId;
}

function update_artwork(int $id, array $data, array $file): void
{
    $existing = fetch_artwork_by_id($id);
    if (!$existing) {
        throw new RuntimeException('La obra no existe.');
    }

    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $dimensions = trim($data['dimensions'] ?? '');
    $technique = trim($data['technique'] ?? '');
    $year = trim($data['year'] ?? '');
    $tags = normalize_tags($data['tags'] ?? '');
    $isFeatured = !empty($data['is_featured']);

    if ($title === '' || $dimensions === '' || $technique === '' || empty($tags)) {
        throw new RuntimeException('Completa titulo, dimensiones, tecnica y etiquetas.');
    }

    $imagePath = save_uploaded_artwork($file, $title);
    if ($imagePath !== null && $imagePath !== $existing['image_path']) {
        delete_image_if_local($existing['image_path']);
    }

    $statement = db()->prepare(
        'UPDATE artworks
         SET slug = :slug,
             title = :title,
             year = :year,
             dimensions = :dimensions,
             technique = :technique,
             description = :description,
             tags_json = :tags_json,
             image_path = :image_path,
             is_featured = :is_featured,
             updated_at = NOW()
         WHERE id = :id'
    );

    $statement->execute([
        'id' => $id,
        'slug' => create_unique_slug($title, $id),
        'title' => $title,
        'year' => $year !== '' ? (int) $year : null,
        'dimensions' => $dimensions,
        'technique' => $technique,
        'description' => $description,
        'tags_json' => tags_to_json($tags),
        'image_path' => $imagePath ?? $existing['image_path'],
        'is_featured' => $isFeatured ? 1 : 0,
    ]);

    if ($isFeatured) {
        enforce_single_featured($id);
    }
}

function delete_artwork(int $id): void
{
    $existing = fetch_artwork_by_id($id);
    if (!$existing) {
        throw new RuntimeException('La obra no existe.');
    }

    $statement = db()->prepare('DELETE FROM artworks WHERE id = :id');
    $statement->execute(['id' => $id]);

    delete_image_if_local($existing['image_path']);
    ensure_featured_after_delete();
}

function render_header(string $title, string $active = 'home'): void
{
    $appName = app_config('app')['name'];
    ?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($title) ?> | <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= e(base_url('/styles.css')) ?>" />
  </head>
  <body>
    <div class="page-shell">
      <header class="hero">
        <nav class="topbar">
          <a class="brand-link" href="<?= e(base_url('/index.php')) ?>"><?= e($appName) ?></a>
          <div class="topbar-links">
            <a
              class="instagram-link"
              href="https://www.instagram.com/alegriafria1/"
              target="_blank"
              rel="noreferrer"
              aria-label="Abrir Instagram de Carmelo Espinosa en una nueva ventana"
            >
              <svg class="instagram-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5a4.25 4.25 0 0 0 4.25-4.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5Zm8.88 1.12a1.13 1.13 0 1 1 0 2.26 1.13 1.13 0 0 1 0-2.26ZM12 6.5A5.5 5.5 0 1 1 6.5 12 5.5 5.5 0 0 1 12 6.5Zm0 1.5A4 4 0 1 0 16 12a4 4 0 0 0-4-4Z"/>
              </svg>
            </a>
            <a class="admin-link<?= $active === 'home' ? ' is-active-link' : '' ?>" href="<?= e(base_url('/index.php')) ?>">Galeria</a>
            <a class="admin-link<?= $active === 'biography' ? ' is-active-link' : '' ?>" href="<?= e(base_url('/biografia.php')) ?>">Biografia</a>
            <a class="admin-link<?= $active === 'admin' ? ' is-active-link' : '' ?>" href="<?= e(base_url('/login.php')) ?>">Administracion</a>
          </div>
        </nav>
<?php
}

function render_footer(): void
{
    ?>
    </div>
  </body>
</html>
<?php
}
