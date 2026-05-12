// invite.js
const params    = new URLSearchParams(window.location.search);
const token     = params.get("token");
const tagline   = document.getElementById("inviteTagline");
const form      = document.getElementById("inviteForm");
const invalidEl = document.getElementById("invalidMsg");

// Token prüfen und E-Mail + Familienname laden
async function loadInvite() {
  if (!token) {
    showInvalid();
    return;
  }

  try {
    const res  = await fetch(`api/check_invite.php?token=${token}`);
    const data = await res.json();

    if (data.status !== "valid") {
      showInvalid();
      return;
    }

    tagline.textContent = `Du wurdest zur Familie ${data.family_name} eingeladen`;
    document.getElementById("email").value = data.email;
    form.style.display = "block";

  } catch {
    showInvalid();
  }
}

function showInvalid() {
  tagline.style.display = "none";
  invalidEl.style.display = "block";
}

// Formular absenden
form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const firstName       = document.getElementById("firstName").value.trim();
  const email           = document.getElementById("email").value.trim();
  const password        = document.getElementById("password").value.trim();
  const confirmPassword = document.getElementById("confirmPassword").value.trim();
  const errorMsg        = document.getElementById("errorMsg");

  errorMsg.textContent = "";
  errorMsg.style.display = "none";

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
    const res  = await fetch("api/register_invite.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ token, firstName, email, password }),
    });
    const data = await res.json();

    if (data.status === "success") {
      window.location.href = "protected.html";
    } else {
      errorMsg.textContent = data.message || "Registrierung fehlgeschlagen.";
      errorMsg.style.display = "block";
    }
  } catch {
    errorMsg.textContent = "Etwas ist schiefgelaufen.";
    errorMsg.style.display = "block";
  }
});

loadInvite();