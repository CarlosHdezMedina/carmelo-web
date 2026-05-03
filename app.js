const galleryElement = document.querySelector("#gallery");
const filtersElement = document.querySelector("#filters");
const detailDialog = document.querySelector("#artwork-dialog");
const detailImage = document.querySelector("#detail-image");
const detailTitle = document.querySelector("#detail-title");
const detailDimensions = document.querySelector("#detail-dimensions");
const detailTechnique = document.querySelector("#detail-technique");
const detailTags = document.querySelector("#detail-tags");
const detailDescription = document.querySelector("#detail-description");
const closeDialogButton = document.querySelector("#close-dialog");
const featuredArtworkElement = document.querySelector("#featured-artwork");

const state = {
  artworks: [],
  activeTag: "todas",
};

async function loadArtworks() {
  galleryElement.innerHTML = '<div class="empty-state">Cargando obras...</div>';

  try {
    const response = await fetch("/api/artworks", { cache: "no-store" });

    if (!response.ok) {
      throw new Error("No se pudo cargar la galeria");
    }

    state.artworks = await response.json();
    renderFilters();
    renderFeaturedArtwork();
    renderGallery();
  } catch (error) {
    galleryElement.innerHTML =
      '<div class="empty-state">No se ha podido cargar la galeria. Inicia la web con <code>node server.js</code>.</div>';
  }
}

function getAllTags() {
  const tags = new Set(["todas"]);

  state.artworks.forEach((artwork) => {
    artwork.tags.forEach((tag) => tags.add(tag));
  });

  return [...tags];
}

function renderFilters() {
  filtersElement.innerHTML = "";

  getAllTags().forEach((tag) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "filter-chip";
    button.textContent = tag;

    if (state.activeTag === tag) {
      button.classList.add("is-active");
    }

    button.addEventListener("click", () => {
      state.activeTag = tag;
      renderFilters();
      renderGallery();
    });

    filtersElement.appendChild(button);
  });
}

function renderFeaturedArtwork() {
  const artwork = state.artworks.find((item) => item.isFeatured) || state.artworks[0];

  if (!artwork) {
    featuredArtworkElement.innerHTML = "";
    return;
  }

  featuredArtworkElement.innerHTML = `
    <div class="featured-copy">
      <p class="eyebrow">Obra destacada</p>
      <h2>${escapeHtml(artwork.title)}</h2>
      <p class="section-text">${escapeHtml(
        artwork.description || "Una obra que resume el universo pictorico del artista."
      )}</p>
      <button class="button button-primary" type="button" id="featured-open">
        Ver obra completa
      </button>
    </div>
    <button class="featured-image-wrap" type="button" id="featured-image-button">
      <img src="${encodeURI(artwork.image)}" alt="${escapeHtml(artwork.title)}" class="featured-image" />
    </button>
  `;

  const openButton = document.querySelector("#featured-open");
  const imageButton = document.querySelector("#featured-image-button");
  openButton.addEventListener("click", () => openArtworkDetail(artwork.id));
  imageButton.addEventListener("click", () => openArtworkDetail(artwork.id));
}

function renderGallery() {
  const visibleArtworks =
    state.activeTag === "todas"
      ? state.artworks
      : state.artworks.filter((artwork) => artwork.tags.includes(state.activeTag));

  galleryElement.innerHTML = "";

  if (!visibleArtworks.length) {
    galleryElement.innerHTML =
      '<div class="empty-state">No hay obras con esta etiqueta todavia.</div>';
    return;
  }

  visibleArtworks.forEach((artwork) => {
    const card = document.createElement("button");
    card.type = "button";
    card.className = "artwork-card";
    card.innerHTML = `
      <div class="artwork-image-wrap">
        <img class="artwork-image" src="${encodeURI(artwork.image)}" alt="${escapeHtml(
          artwork.title
        )}" loading="lazy" />
      </div>
      <div class="artwork-body">
        <h3 class="artwork-title">${escapeHtml(artwork.title)}</h3>
      </div>
    `;

    card.addEventListener("click", () => openArtworkDetail(artwork.id));
    galleryElement.appendChild(card);
  });
}

function openArtworkDetail(artworkId) {
  const artwork = state.artworks.find((item) => item.id === artworkId);

  if (!artwork) {
    return;
  }

  detailImage.src = artwork.image;
  detailImage.alt = artwork.title;
  detailTitle.textContent = artwork.title;
  detailDimensions.textContent = artwork.dimensions || "Dimensiones no indicadas";
  detailTechnique.textContent = artwork.technique || "Tecnica no indicada";
  detailDescription.textContent =
    artwork.description || "Sin descripcion adicional para esta obra.";
  detailTags.innerHTML = "";

  artwork.tags.forEach((tag) => {
    const item = document.createElement("span");
    item.className = "tag";
    item.textContent = tag;
    detailTags.appendChild(item);
  });

  detailDialog.showModal();
}

function escapeHtml(value) {
  return value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

closeDialogButton.addEventListener("click", () => detailDialog.close());
detailDialog.addEventListener("click", (event) => {
  const bounds = detailDialog.getBoundingClientRect();
  const isBackdrop =
    event.clientX < bounds.left ||
    event.clientX > bounds.right ||
    event.clientY < bounds.top ||
    event.clientY > bounds.bottom;

  if (isBackdrop) {
    detailDialog.close();
  }
});

loadArtworks();

window.addEventListener("pageshow", () => {
  loadArtworks();
});
