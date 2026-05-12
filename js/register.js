// register.js
document.getElementById("registerForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const firstName       = document.getElementById("firstName").value.trim();
  const lastName        = document.getElementById("lastName").value.trim();
  const email           = document.getElementById("email").value.trim();
  const password        = document.getElementById("password").value.trim();
  const confirmPassword = document.getElementById("confirmPassword").value.trim();
  const errorMsg        = document.getElementById("errorMsg");

  errorMsg.textContent = "";
  errorMsg.style.display = "none";

  // Client-seitige Validierung
  if (password !== confirmPassword) {
    errorMsg.textContent = "Die Passwörter stimmen nicht überein.";
    errorMsg.style.display = "block";
    return;
  }

  if (password.length < 8) {
    errorMsg.textContent = "Passwort muss mindestens 8 Zeichen lang sein.";
    errorMsg.style.display = "block";
    return;
  }

  try {
    const response = await fetch("api/register.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ firstName, lastName, email, password }),
    });

    const result = await response.json();

    if (result.status === "success") {
      window.location.href = "connect-device.html";
    } else {
      errorMsg.textContent = result.message || "Registrierung fehlgeschlagen.";
      errorMsg.style.display = "block";
    }
  } catch (error) {
    console.error("Error:", error);
    errorMsg.textContent = "Etwas ist schiefgelaufen. Bitte versuche es erneut.";
    errorMsg.style.display = "block";
  }
});

// Fehlermeldung beim Tippen ausblenden
document.querySelectorAll("input").forEach(input =>
  input.addEventListener("input", () => {
    const errorMsg = document.getElementById("errorMsg");
    errorMsg.textContent = "";
    errorMsg.style.display = "none";
  })
);