document.addEventListener("DOMContentLoaded", () => {
  const usernameInput = document.getElementById("username");
  const passwordInput = document.getElementById("password");
  const rememberCheck = document.getElementById("remember");

  const usernameError = document.getElementById("usernameError");
  const passwordError = document.getElementById("passwordError");

  // Load saved username
  const saved = localStorage.getItem("savedUsername");
  if (saved && usernameInput && rememberCheck) {
    usernameInput.value = saved;
    rememberCheck.checked = true;
  }

  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
      e.preventDefault();
      let valid = true;

      // Reset errors
      if (usernameError) {
        usernameError.textContent = "";
        usernameError.classList.remove("show");
      }
      if (passwordError) {
        passwordError.textContent = "";
        passwordError.classList.remove("show");
      }

      const username = usernameInput.value.trim();
      const password = passwordInput.value.trim();

      // Username rules
      if (username === "") {
        usernameError.textContent = "Username cannot be empty.";
        usernameError.classList.add("show");
        valid = false;
      } else if (!/^[a-zA-Z0-9]+$/.test(username)) {
        usernameError.textContent = "Only letters and numbers allowed.";
        usernameError.classList.add("show");
        valid = false;
      } else if (username.length > 20) {
        usernameError.textContent = "Maximum 20 characters allowed.";
        usernameError.classList.add("show");
        valid = false;
      }

      // Password rules
      if (password === "") {
        passwordError.textContent = "Password cannot be empty.";
        passwordError.classList.add("show");
        valid = false;
      }

      // If valid, redirect to admin dashboard
      if (valid) {
        if (rememberCheck.checked) {
          localStorage.setItem("savedUsername", username);
        } else {
          localStorage.removeItem("savedUsername");
        }

        // Redirect to dashboard route
        window.location.href = "/admin/dashboard";
      }
    });
  }
});
