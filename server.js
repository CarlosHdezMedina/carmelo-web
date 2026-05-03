const http = require("http");
const fs = require("fs/promises");
const path = require("path");
const crypto = require("crypto");

const rootDir = __dirname;
const dataFile = path.join(rootDir, "data", "artworks.json");
const artworksDir = path.join(rootDir, "assets", "artworks");
const port = process.env.PORT || 3000;
const host = "127.0.0.1";
const adminUser = process.env.ADMIN_USER || "pintor";
const adminPassword = process.env.ADMIN_PASSWORD || "alegriafria";
const sessions = new Map();

const mimeTypes = {
  ".html": "text/html; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".svg": "image/svg+xml",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".png": "image/png",
  ".webp": "image/webp",
  ".gif": "image/gif",
  ".ico": "image/x-icon",
};

async function ensureDataFile() {
  await fs.mkdir(path.dirname(dataFile), { recursive: true });
  await fs.mkdir(artworksDir, { recursive: true });

  try {
    await fs.access(dataFile);
  } catch {
    await fs.writeFile(dataFile, "[]\n", "utf8");
  }
}

async function readArtworks() {
  const content = await fs.readFile(dataFile, "utf8");
  return JSON.parse(content);
}

async function writeArtworks(artworks) {
  await fs.writeFile(dataFile, JSON.stringify(artworks, null, 2) + "\n", "utf8");
}

function slugify(value) {
  return value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 80);
}

function titleFromFilename(filename) {
  const baseName = path.basename(filename, path.extname(filename));
  const spaced = baseName.replace(/([a-z])([A-Z])/g, "$1 $2").replace(/[-_]+/g, " ");
  return spaced.replace(/\b\w/g, (char) => char.toUpperCase());
}

async function syncFolderArtworks() {
  const artworks = await readArtworks();
  const indexedImages = new Set(artworks.map((artwork) => path.basename(artwork.image)));
  const files = await fs.readdir(artworksDir);
  const allowedExtensions = new Set([".jpg", ".jpeg", ".png", ".webp", ".gif", ".svg"]);
  let changed = false;

  for (const file of files) {
    const extension = path.extname(file).toLowerCase();

    if (!allowedExtensions.has(extension) || indexedImages.has(file)) {
      continue;
    }

    artworks.push({
      id: `obra-${slugify(path.basename(file, extension)) || crypto.randomUUID()}`,
      title: titleFromFilename(file),
      dimensions: "Dimensiones no indicadas",
      technique: "Tecnica no indicada",
      description: "Obra detectada automaticamente en la carpeta de imagenes.",
      tags: ["sin clasificar"],
      image: `/assets/artworks/${encodeURIComponent(file)}`,
      isFeatured: false,
      createdAt: new Date().toISOString(),
    });
    changed = true;
  }

  artworks.sort((left, right) => new Date(right.createdAt) - new Date(left.createdAt));

  if (changed) {
    await writeArtworks(artworks);
  }

  return artworks;
}

function normalizeFeaturedArtwork(artworks, featuredArtworkId = "") {
  let hasFeaturedArtwork = false;

  artworks.forEach((artwork) => {
    const shouldBeFeatured = featuredArtworkId
      ? artwork.id === featuredArtworkId
      : Boolean(artwork.isFeatured) && !hasFeaturedArtwork;

    artwork.isFeatured = shouldBeFeatured;
    if (shouldBeFeatured) {
      hasFeaturedArtwork = true;
    }
  });

  return artworks;
}

function sendJson(response, statusCode, payload) {
  response.writeHead(statusCode, {
    "Content-Type": "application/json; charset=utf-8",
    "Cache-Control": "no-store",
  });
  response.end(JSON.stringify(payload));
}

function redirect(response, location, headers = {}) {
  response.writeHead(302, {
    Location: location,
    "Cache-Control": "no-store",
    ...headers,
  });
  response.end();
}

function parseCookies(request) {
  const cookieHeader = request.headers.cookie || "";
  const cookies = {};

  cookieHeader
    .split(";")
    .map((item) => item.trim())
    .filter(Boolean)
    .forEach((item) => {
      const separatorIndex = item.indexOf("=");

      if (separatorIndex === -1) {
        return;
      }

      const key = item.slice(0, separatorIndex).trim();
      const value = item.slice(separatorIndex + 1).trim();
      cookies[key] = decodeURIComponent(value);
    });

  return cookies;
}

function createSession() {
  const token = crypto.randomBytes(24).toString("hex");
  sessions.set(token, { createdAt: Date.now() });
  return token;
}

function clearExpiredSessions() {
  const maxAgeMs = 1000 * 60 * 60 * 8;
  const now = Date.now();

  for (const [token, session] of sessions.entries()) {
    if (now - session.createdAt > maxAgeMs) {
      sessions.delete(token);
    }
  }
}

function isAuthorized(request) {
  clearExpiredSessions();
  const cookies = parseCookies(request);
  return Boolean(cookies.admin_session && sessions.has(cookies.admin_session));
}

function isProtectedPath(pathname) {
  return pathname === "/admin.html" || pathname === "/admin.js";
}

function findArtworkIdFromPath(pathname) {
  const match = pathname.match(/^\/api\/artworks\/([^/]+)$/);
  return match ? decodeURIComponent(match[1]) : null;
}

async function parseRequestBody(request) {
  const chunks = [];

  for await (const chunk of request) {
    chunks.push(chunk);
  }

  return Buffer.concat(chunks);
}

function parseUrlEncoded(bodyBuffer) {
  return new URLSearchParams(bodyBuffer.toString("utf8"));
}

function parseMultipart(bodyBuffer, boundary) {
  const boundaryText = `--${boundary}`;
  const bodyText = bodyBuffer.toString("binary");
  const rawParts = bodyText.split(boundaryText).slice(1, -1);
  const fields = {};
  let file = null;

  rawParts.forEach((rawPart) => {
    const cleaned = rawPart.replace(/^\r\n/, "").replace(/\r\n$/, "");
    const separatorIndex = cleaned.indexOf("\r\n\r\n");

    if (separatorIndex === -1) {
      return;
    }

    const rawHeaders = cleaned.slice(0, separatorIndex);
    let content = cleaned.slice(separatorIndex + 4);
    content = content.replace(/\r\n$/, "");

    const nameMatch = rawHeaders.match(/name="([^"]+)"/);

    if (!nameMatch) {
      return;
    }

    const fieldName = nameMatch[1];
    const filenameMatch = rawHeaders.match(/filename="([^"]*)"/);

    if (filenameMatch && filenameMatch[1]) {
      const contentTypeMatch = rawHeaders.match(/Content-Type:\s*([^\r\n]+)/i);
      file = {
        fieldName,
        filename: path.basename(filenameMatch[1]),
        contentType: contentTypeMatch ? contentTypeMatch[1].trim() : "application/octet-stream",
        buffer: Buffer.from(content, "binary"),
      };
      return;
    }

    fields[fieldName] = Buffer.from(content, "binary").toString("utf8").trim();
  });

  return { fields, file };
}

async function handleGetArtworks(_request, response) {
  try {
    const artworks = await syncFolderArtworks();
    sendJson(response, 200, artworks);
  } catch (error) {
    sendJson(response, 500, { error: "No se pudo leer el catalogo." });
  }
}

async function handleLogin(request, response) {
  try {
    const body = await parseRequestBody(request);
    const params = parseUrlEncoded(body);
    const username = (params.get("username") || "").trim();
    const password = params.get("password") || "";

    if (username !== adminUser || password !== adminPassword) {
      redirect(response, "/login.html?error=1");
      return;
    }

    const sessionToken = createSession();
    redirect(response, "/admin.html", {
      "Set-Cookie": `admin_session=${encodeURIComponent(
        sessionToken
      )}; HttpOnly; Path=/; SameSite=Lax`,
    });
  } catch {
    redirect(response, "/login.html?error=1");
  }
}

function handleLogout(request, response) {
  const cookies = parseCookies(request);

  if (cookies.admin_session) {
    sessions.delete(cookies.admin_session);
  }

  redirect(response, "/login.html", {
    "Set-Cookie": "admin_session=; HttpOnly; Path=/; Max-Age=0; SameSite=Lax",
  });
}

async function handleCreateArtwork(request, response) {
  try {
    const contentType = request.headers["content-type"] || "";
    const boundaryMatch = contentType.match(/boundary=(.+)$/);

    if (!boundaryMatch) {
      sendJson(response, 400, { error: "Formato de envio no valido." });
      return;
    }

    const body = await parseRequestBody(request);
    const { fields, file } = parseMultipart(body, boundaryMatch[1]);

    if (!file || !file.buffer.length) {
      sendJson(response, 400, { error: "Debes adjuntar una imagen." });
      return;
    }

    const title = fields.title?.trim();
    const dimensions = fields.dimensions?.trim();
    const technique = fields.technique?.trim();
    const description = fields.description?.trim() || "";
    const isFeatured = fields.isFeatured === "true";
    const tags = (fields.tags || "")
      .split(",")
      .map((tag) => tag.trim().toLowerCase())
      .filter(Boolean);

    if (!title || !dimensions || !technique || !tags.length) {
      sendJson(response, 400, { error: "Faltan datos obligatorios de la obra." });
      return;
    }

    const extension = path.extname(file.filename).toLowerCase() || ".jpg";
    const fileName = `${Date.now()}-${slugify(title) || crypto.randomUUID()}${extension}`;
    const targetPath = path.join(artworksDir, fileName);
    await fs.writeFile(targetPath, file.buffer);

    const artworks = await syncFolderArtworks();
    const newArtwork = {
      id: `obra-${slugify(title)}-${Date.now()}`,
      title,
      dimensions,
      technique,
      description,
      tags,
      image: `/assets/artworks/${encodeURIComponent(fileName)}`,
      isFeatured,
      createdAt: new Date().toISOString(),
    };

    const existingIndex = artworks.findIndex((artwork) => artwork.image === newArtwork.image);

    if (existingIndex >= 0) {
      artworks[existingIndex] = newArtwork;
    } else {
      artworks.unshift(newArtwork);
    }

    normalizeFeaturedArtwork(artworks, isFeatured ? newArtwork.id : "");
    artworks.sort((left, right) => new Date(right.createdAt) - new Date(left.createdAt));
    await writeArtworks(artworks);
    sendJson(response, 201, newArtwork);
  } catch (error) {
    sendJson(response, 500, { error: "No se pudo guardar la obra." });
  }
}

async function saveUploadedFile(file, title) {
  const extension = path.extname(file.filename).toLowerCase() || ".jpg";
  const fileName = `${Date.now()}-${slugify(title) || crypto.randomUUID()}${extension}`;
  const targetPath = path.join(artworksDir, fileName);
  await fs.writeFile(targetPath, file.buffer);
  return `/assets/artworks/${encodeURIComponent(fileName)}`;
}

async function maybeDeleteImage(imagePath, artworks, artworkIdToIgnore = "") {
  if (!imagePath || !imagePath.startsWith("/assets/artworks/")) {
    return;
  }

  const isReferencedElsewhere = artworks.some(
    (artwork) => artwork.id !== artworkIdToIgnore && artwork.image === imagePath
  );

  if (isReferencedElsewhere) {
    return;
  }

  const fileName = decodeURIComponent(path.basename(imagePath));
  const targetPath = path.join(artworksDir, fileName);

  try {
    await fs.unlink(targetPath);
  } catch (error) {
    if (error.code !== "ENOENT") {
      throw error;
    }
  }
}

async function handleUpdateArtwork(request, response, artworkId) {
  try {
    const contentType = request.headers["content-type"] || "";
    const boundaryMatch = contentType.match(/boundary=(.+)$/);

    if (!boundaryMatch) {
      sendJson(response, 400, { error: "Formato de envio no valido." });
      return;
    }

    const body = await parseRequestBody(request);
    const { fields, file } = parseMultipart(body, boundaryMatch[1]);
    const artworks = await syncFolderArtworks();
    const artworkIndex = artworks.findIndex((artwork) => artwork.id === artworkId);

    if (artworkIndex === -1) {
      sendJson(response, 404, { error: "La obra no existe." });
      return;
    }

    const existingArtwork = artworks[artworkIndex];
    const title = fields.title?.trim();
    const dimensions = fields.dimensions?.trim();
    const technique = fields.technique?.trim();
    const description = fields.description?.trim() || "";
    const isFeatured = fields.isFeatured === "true";
    const tags = (fields.tags || "")
      .split(",")
      .map((tag) => tag.trim().toLowerCase())
      .filter(Boolean);

    if (!title || !dimensions || !technique || !tags.length) {
      sendJson(response, 400, { error: "Faltan datos obligatorios de la obra." });
      return;
    }

    let image = existingArtwork.image;

    if (file && file.buffer.length) {
      image = await saveUploadedFile(file, title);
      await maybeDeleteImage(existingArtwork.image, artworks, artworkId);
    }

    const updatedArtwork = {
      ...existingArtwork,
      title,
      dimensions,
      technique,
      description,
      tags,
      image,
      isFeatured,
    };

    artworks[artworkIndex] = updatedArtwork;
    normalizeFeaturedArtwork(artworks, isFeatured ? updatedArtwork.id : "");
    await writeArtworks(artworks);
    sendJson(response, 200, updatedArtwork);
  } catch (error) {
    sendJson(response, 500, { error: "No se pudo actualizar la obra." });
  }
}

async function handleDeleteArtwork(_request, response, artworkId) {
  try {
    const artworks = await syncFolderArtworks();
    const artwork = artworks.find((item) => item.id === artworkId);

    if (!artwork) {
      sendJson(response, 404, { error: "La obra no existe." });
      return;
    }

    const remainingArtworks = artworks.filter((item) => item.id !== artworkId);
    await maybeDeleteImage(artwork.image, remainingArtworks);
    await writeArtworks(remainingArtworks);
    sendJson(response, 200, { ok: true });
  } catch (error) {
    sendJson(response, 500, { error: "No se pudo borrar la obra." });
  }
}

async function serveStatic(requestPath, response) {
  const safePath = requestPath === "/" ? "/index.html" : requestPath;
  const targetPath = path.normalize(path.join(rootDir, safePath));

  if (!targetPath.startsWith(rootDir)) {
    response.writeHead(403);
    response.end("Forbidden");
    return;
  }

  try {
    const content = await fs.readFile(targetPath);
    const extension = path.extname(targetPath).toLowerCase();
    response.writeHead(200, {
      "Content-Type": mimeTypes[extension] || "application/octet-stream",
      "Cache-Control":
        extension === ".html" || extension === ".js" || extension === ".css"
          ? "no-store"
          : "public, max-age=3600",
    });
    response.end(content);
  } catch {
    response.writeHead(404, { "Content-Type": "text/plain; charset=utf-8" });
    response.end("No encontrado");
  }
}

async function start() {
  await ensureDataFile();
  await syncFolderArtworks();

  const server = http.createServer(async (request, response) => {
    const url = new URL(request.url, `http://${request.headers.host}`);
    const artworkId = findArtworkIdFromPath(url.pathname);

    if (request.method === "POST" && url.pathname === "/login") {
      await handleLogin(request, response);
      return;
    }

    if (request.method === "POST" && url.pathname === "/logout") {
      handleLogout(request, response);
      return;
    }

    if (url.pathname === "/login.html" && isAuthorized(request)) {
      redirect(response, "/admin.html");
      return;
    }

    if (isProtectedPath(url.pathname) && !isAuthorized(request)) {
      redirect(response, "/login.html");
      return;
    }

    if (request.method === "GET" && url.pathname === "/api/artworks") {
      await handleGetArtworks(request, response);
      return;
    }

    if (request.method === "POST" && url.pathname === "/api/artworks") {
      if (!isAuthorized(request)) {
        sendUnauthorized(response);
        return;
      }
      await handleCreateArtwork(request, response);
      return;
    }

    if (request.method === "PUT" && artworkId) {
      if (!isAuthorized(request)) {
        sendUnauthorized(response);
        return;
      }
      await handleUpdateArtwork(request, response, artworkId);
      return;
    }

    if (request.method === "DELETE" && artworkId) {
      if (!isAuthorized(request)) {
        sendUnauthorized(response);
        return;
      }
      await handleDeleteArtwork(request, response, artworkId);
      return;
    }

    await serveStatic(url.pathname, response);
  });

  server.listen(port, host, () => {
    console.log(`Alegria Fria Web disponible en http://${host}:${port}`);
  });
}

start().catch((error) => {
  console.error(error);
  process.exit(1);
});
