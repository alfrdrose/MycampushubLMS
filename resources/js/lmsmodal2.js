//  CLOSE MODAL HANDLER
document.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-close]");
  if (!btn) return;

  document.getElementById(btn.dataset.close).classList.add("hidden");
});
