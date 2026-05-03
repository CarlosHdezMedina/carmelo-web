const formElement = document.querySelector("#admin-form");
const statusElement = document.querySelector("#form-status");
const galleryElement = document.querySelector("#admin-gallery");
const formTitleElement = document.querySelector("#form-title");
const submitButton = document.querySelector("#submit-button");
const cancelEditButton = document.querySelector("#cancel-edit");
const imageInput = formElement.elements.image;
const selectedTagsElement = document.querySelector("#selected-tags");
const tagInput = document.querySelector("#tag-input");
const addTagButton = document.querySelector("#add-tag-button");
const existingTagsList = document.querySelector("#existing-tags");
const adminDialog = document.querySelector("#admin-dialog");
const closeAdminDialogButton = document.querySelector("#close-admin-dialog");
const adminDetailImage = document.querySelector("#admin-detail-image");
const detailFormElement = document.querySelector("#admin-detail-form");
const detailStatusElement = document.querySelector("#detail-form-status");
const detailSelectedTagsElement = document.querySelector("#detail-selected-tags");
const detailTagInput = document.querySelector("#detail-tag-input");
const detailAddTagButton = document.querySelector("#detail-add-tag-button");
const deleteArtworkButton = document.querySelector("#delete-artwork-button");

const state = {
  artworks: [],
  editingArtworkId: "",
  createTags: [],
  detailTags: [],
};

async function loadAdminGallery() {
  galleryElement.innerHTML = '<div class="empty-state">Cargando catalogo...</div>';

  try {
    const response = await fetch("/api/artworks", { cache: "no-store" });

    if (!response.ok) {
      throw new Error("No se pudo cargar el catalogo");
    }

    state.artworks = await response.json();
    syncTagSuggestions();
    renderAdminGallery(state.artworks);
  } catch (error) {
    galleryElement.innerHTML =
      '<div class="empty-state">No se ha podido cargar el catalogo. Inicia la web con <code>node server.js</code>.</div>';
  }
}

function renderAdminGallery(artworks) {
  galleryElement.innerHTML = "";

  if (!artworks.length) {
    galleryElement.innerHTML =
      '<div class="empty-state">Todavia no hay obras registradas.</div>';
    return;
  }

  artworks.forEach((artwork) => {
    const card = document.createElement("button");
    card.type = "button";
    card.className = "admin-card admin-card-button";
    card.innerHTML = `
      <img src="${encodeURI(artwork.image)}" alt="${escapeHtml(artwork.title)}" class="admin-thumb" />
      <div class="admin-card-body">
        <h3>${escapeHtml(artwork.title)}</h3>
        <p>${escapeHtml(artwork.dimensions || "Dimensiones pendientes")}</p>
        <p>${escapeHtml(artwork.technique || "Tecnica pendiente")}</p>
        ${artwork.isFeatured ? '<p class="featured-badge">Obra destacada</p>' : ""}
        <div class="tag-list">
          ${artwork.tags.map((tag) => `<span class="tag">${escapeHtml(tag)}</span>`).join("")}
        </div>
        <p class="card-link-label">Haz clic para ver detalle, editar o borrar.</p>
      </div>
    `;

    card.addEventListener("click", () => {
      openArtworkDialog(artwork.id);
    });
    galleryElement.appendChild(card);
  });
}

formElement.addEventListener("submit", async (event) => {
  event.preventDefault();
  const artworkId = String(formElement.elements.artworkId.value || "");
  statusElement.textContent = artworkId ? "Guardando cambios..." : "Guardando obra...";

  try {
    const method = artworkId ? "PUT" : "POST";
    const formData = new FormData(formElement);

    if (!artworkId && !imageInput.files.length) {
      throw new Error("Debes adjuntar una imagen para crear una nueva obra.");
    }

    const response = await fetch(
      artworkId ? `/api/artworks/${encodeURIComponent(artworkId)}` : "/api/artworks",
      {
        method,
        body: formData,
        cache: "no-store",
      }
    );

    if (!response.ok) {
      const errorPayload = await response.json().catch(() => ({}));
      throw new Error(errorPayload.error || "No se pudo guardar la obra");
    }

    resetForm();
    statusElement.textContent = artworkId
      ? "Obra actualizada correctamente."
      : "Obra guardada correctamente.";
    await loadAdminGallery();
  } catch (error) {
    statusElement.textContent = error.message;
  }
});

cancelEditButton.addEventListener("click", () => {
  resetForm();
  statusElement.textContent = "Edicion cancelada.";
});

async function deleteArtwork(artworkId) {
  const artwork = state.artworks.find((item) => item.id === artworkId);

  if (!artwork) {
    return;
  }

  const confirmed = window.confirm(`Se borrara "${artwork.title}" del catalogo. Quieres continuar?`);

  if (!confirmed) {
    return;
  }

  statusElement.textContent = "Borrando obra...";

  try {
    const response = await fetch(`/api/artworks/${encodeURIComponent(artworkId)}`, {
      method: "DELETE",
      cache: "no-store",
    });

    if (!response.ok) {
      const errorPayload = await response.json().catch(() => ({}));
      throw new Error(errorPayload.error || "No se pudo borrar la obra");
    }

    if (state.editingArtworkId === artworkId) {
      resetForm();
    }

    statusElement.textContent = "Obra borrada correctamente.";
    await loadAdminGallery();
  } catch (error) {
    statusElement.textContent = error.message;
  }
}

function resetForm() {
  state.editingArtworkId = "";
  state.createTags = [];
  formElement.reset();
  formElement.elements.artworkId.value = "";
  formElement.elements.tags.value = "";
  formElement.elements.isFeatured.checked = false;
  imageInput.required = false;
  formTitleElement.textContent = "Formulario de alta";
  submitButton.textContent = "Guardar obra";
  cancelEditButton.classList.add("is-hidden");
  renderSelectedTags(selectedTagsElement, state.createTags, "create");
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function syncTagSuggestions() {
  const tags = new Set();

  state.artworks.forEach((artwork) => {
    (artwork.tags || []).forEach((tag) => tags.add(tag));
  });

  existingTagsList.innerHTML = [...tags]
    .sort((left, right) => left.localeCompare(right))
    .map((tag) => `<option value="${escapeHtml(tag)}"></option>`)
    .join("");
}

function normalizeTag(value) {
  return value.trim().toLowerCase();
}

function addTagToCollection(source, inputElement, hiddenFieldName, targetElement, scope) {
  const nextTag = normalizeTag(inputElement.value || "");

  if (!nextTag || source.includes(nextTag)) {
    inputElement.value = "";
    return;
  }

  source.push(nextTag);
  inputElement.value = "";
  updateTagField(hiddenFieldName, source);
  renderSelectedTags(targetElement, source, scope);
}

function updateTagField(hiddenFieldName, tags) {
  formElement.elements[hiddenFieldName] &&
    (formElement.elements[hiddenFieldName].value = tags.join(", "));
  detailFormElement.elements[hiddenFieldName] &&
    (detailFormElement.elements[hiddenFieldName].value = tags.join(", "));
}

function renderSelectedTags(targetElement, tags, scope) {
  targetElement.innerHTML = "";

  if (!tags.length) {
    targetElement.innerHTML = '<span class="tag tag-muted">Sin etiquetas todavia</span>';
    return;
  }

  tags.forEach((tag) => {
    const item = document.createElement("button");
    item.type = "button";
    item.className = "tag tag-removable";
    item.innerHTML = `${escapeHtml(tag)} <span aria-hidden="true">x</span>`;
    item.addEventListener("click", () => {
      removeTag(tag, scope);
    });
    targetElement.appendChild(item);
  });
}

function removeTag(tagToRemove, scope) {
  const key = scope === "create" ? "createTags" : "detailTags";
  state[key] = state[key].filter((tag) => tag !== tagToRemove);

  if (scope === "create") {
    formElement.elements.tags.value = state.createTags.join(", ");
    renderSelectedTags(selectedTagsElement, state.createTags, "create");
    return;
  }

  detailFormElement.elements.tags.value = state.detailTags.join(", ");
  renderSelectedTags(detailSelectedTagsElement, state.detailTags, "detail");
}

function openArtworkDialog(artworkId) {
  const artwork = state.artworks.find((item) => item.id === artworkId);

  if (!artwork) {
    return;
  }

  state.editingArtworkId = artwork.id;
  state.detailTags = [...(artwork.tags || [])];
  adminDetailImage.src = artwork.image;
  adminDetailImage.alt = artwork.title;
  detailFormElement.elements.artworkId.value = artwork.id;
  detailFormElement.elements.title.value = artwork.title || "";
  detailFormElement.elements.dimensions.value = artwork.dimensions || "";
  detailFormElement.elements.technique.value = artwork.technique || "";
  detailFormElement.elements.description.value = artwork.description || "";
  detailFormElement.elements.tags.value = state.detailTags.join(", ");
  detailFormElement.elements.isFeatured.checked = Boolean(artwork.isFeatured);
  detailFormElement.elements.image.value = "";
  renderSelectedTags(detailSelectedTagsElement, state.detailTags, "detail");
  detailStatusElement.textContent = "";
  adminDialog.showModal();
}

detailFormElement.addEventListener("submit", async (event) => {
  event.preventDefault();
  const artworkId = String(detailFormElement.elements.artworkId.value || "");
  detailStatusElement.textContent = "Guardando cambios...";

  try {
    const response = await fetch(`/api/artworks/${encodeURIComponent(artworkId)}`, {
      method: "PUT",
      body: new FormData(detailFormElement),
      cache: "no-store",
    });

    if (!response.ok) {
      const errorPayload = await response.json().catch(() => ({}));
      throw new Error(errorPayload.error || "No se pudo guardar la obra");
    }

    detailStatusElement.textContent = "Obra actualizada correctamente.";
    await loadAdminGallery();
    openArtworkDialog(artworkId);
  } catch (error) {
    detailStatusElement.textContent = error.message;
  }
});

deleteArtworkButton.addEventListener("click", async () => {
  const artworkId = String(detailFormElement.elements.artworkId.value || "");
  await deleteArtwork(artworkId);

  if (!state.artworks.some((artwork) => artwork.id === artworkId)) {
    adminDialog.close();
  }
});

closeAdminDialogButton.addEventListener("click", () => adminDialog.close());
adminDialog.addEventListener("click", (event) => {
  const bounds = adminDialog.getBoundingClientRect();
  const isBackdrop =
    event.clientX < bounds.left ||
    event.clientX > bounds.right ||
    event.clientY < bounds.top ||
    event.clientY > bounds.bottom;

  if (isBackdrop) {
    adminDialog.close();
  }
});

addTagButton.addEventListener("click", () => {
  addTagToCollection(state.createTags, tagInput, "tags", selectedTagsElement, "create");
});

tagInput.addEventListener("keydown", (event) => {
  if (event.key === "Enter") {
    event.preventDefault();
    addTagToCollection(state.createTags, tagInput, "tags", selectedTagsElement, "create");
  }
});

detailAddTagButton.addEventListener("click", () => {
  addTagToCollection(state.detailTags, detailTagInput, "tags", detailSelectedTagsElement, "detail");
});

detailTagInput.addEventListener("keydown", (event) => {
  if (event.key === "Enter") {
    event.preventDefault();
    addTagToCollection(state.detailTags, detailTagInput, "tags", detailSelectedTagsElement, "detail");
  }
});

resetForm();
loadAdminGallery();
