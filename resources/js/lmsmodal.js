// GLOBAL MODAL HANDLER
window.LMSModal = {
  open({ title = "Modal", content = "" }) {
    const modal = document.getElementById("lmsmodal");
    const titleEl = document.getElementById("lmsmodaltitle");
    const contentEl = document.getElementById("lmsmodalcontent");

    if (!modal || !titleEl || !contentEl) return;

    titleEl.textContent = title;
    contentEl.innerHTML = content;
    modal.classList.remove("hidden");
  },

  close() {
    const modal = document.getElementById("lmsmodal");
    if (!modal) return;
    modal.classList.add("hidden");
    document.getElementById("lmsmodaltitle").textContent = "Default Title";
    document.getElementById("lmsmodalcontent").innerHTML = "";
  },
};

// CLOSE MODAL HANDLER (click-based)
document.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-close]");
  if (!btn) return;
  const modal = document.getElementById(btn.dataset.close);
  if (modal) modal.classList.add("hidden");
});
