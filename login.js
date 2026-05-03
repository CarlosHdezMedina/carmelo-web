const params = new URLSearchParams(window.location.search);
const errorElement = document.querySelector("#login-error");

if (params.get("error") === "1") {
  errorElement.classList.remove("is-hidden");
}
