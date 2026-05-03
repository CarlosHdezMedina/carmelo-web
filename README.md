# Carmelo Espinosa Web

Version PHP + MySQL pensada para hosting compartido con Apache.

## Archivos principales

- `index.php`: portada y galeria publica.
- `obra.php`: ficha individual de cada obra.
- `biografia.php`: pagina con foto, biografia y acceso al statement y CV.
- `login.php`: acceso privado al panel.
- `admin.php`: alta, edicion, borrado y seleccion de obra destacada.
- `logout.php`: cierre de sesion.
- `config.php`: configuracion del proyecto y credenciales de MySQL/admin.
- `database/schema.sql`: estructura de la base de datos.
- `database/seed.sql`: carga inicial del catalogo actual.
- `uploads/artworks/`: imagenes que se suben desde administracion.
- `assets/biography/`: foto, statement y CV.
- `.htaccess`: prioridad de `index.php` y proteccion de archivos internos.

## Preparacion para hosting

1. Crea una base de datos MySQL desde tu hosting.
2. Importa `database/schema.sql`.
3. Si quieres partir del catalogo actual, importa despues `database/seed.sql`.
4. Edita `config.php` con:
   - nombre de la base de datos
   - usuario MySQL
   - contrasena MySQL
   - usuario y contrasena del panel privado
5. Sube todos los archivos al `public_html` o carpeta publica del dominio.
6. Asegurate de que la carpeta `uploads/artworks/` tenga permisos de escritura.

## Accesos

- Web publica: `/index.php` o directamente el dominio.
- Biografia: `/biografia.php`
- Panel privado: `/login.php`

## Funcionalidades incluidas

- Filtro de galeria por etiqueta y por ano.
- Campo `ano` en cada obra.
- Obra destacada editable desde administracion.
- Subida de imagenes y guardado persistente en MySQL.
- Biografia con foto del artista y acceso directo a statement y CV en PDF.

## Nota sobre los PDFs

- El statement y el CV estan integrados como documentos PDF embebidos y descargables.
- La estructura queda preparada para incorporar despues una transcripcion literal editable si se quiere mostrar el texto completo en HTML.
