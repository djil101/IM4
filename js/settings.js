// settings.js

const toggle      = document.getElementById("notificationsEnabled");
const quietFrom   = document.getElementById("quietFrom");
const quietUntil  = document.getElementById("quietUntil");
const saveBtn     = document.getElementById("saveBtn");
const feedback    = document.getElementById("saveFeedback");

// Einstellungen + Admin-Status laden
async function loadSettings() {
  try {
    const res = await fetch("api/settings_get.php", {
      credentials: "include",
    });

    if (res.status === 401) {
      window.location.href = "login.html";
      return;
    }

    const data = await res.json();
    toggle.checked     = data.enabled;
    quietFrom.value    = data.quiet_from  ?? "";
    quietUntil.value   = data.quiet_until ?? "";

    const wrap = document.querySelector(".card-ruhefenster");
    if (!data.enabled) {
      wrap.classList.add("time-fields-disabled");
    }

  } catch (err) {
    console.error("Einstellungen konnten nicht geladen werden:", err);
  }
}

// Admin-Status prüfen → Familie-Karte anzeigen
async function checkAdmin() {
  try {
    const res  = await fetch("api/protected.php", { credentials: "include" });
    const data = await res.json();

    if (data.is_admin) {
      document.getElementById("familyCard").style.display = "block";
    }
  } catch (err) {
    console.error("Admin-Check fehlgeschlagen:", err);
  }
}

// Einstellungen speichern
async function saveSettings() {
  saveBtn.disabled = true;
  feedback.textContent = "";

  try {
    const res = await fetch("api/settings_save.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        enabled:     toggle.checked ? 1 : 0,
        quiet_from:  quietFrom.value  || null,
        quiet_until: quietUntil.value || null,
      }),
    });

    const data = await res.json();

    if (data.status === "success") {
      feedback.textContent = "✓ Gespeichert";
      feedback.style.color = "#3a9e6e";
    } else {
      feedback.textContent = "Fehler beim Speichern.";
      feedback.style.color = "#c0392b";
    }

  } catch (err) {
    feedback.textContent = "Etwas ist schiefgelaufen.";
    feedback.style.color = "#c0392b";
  } finally {
    saveBtn.disabled = false;
    setTimeout(() => feedback.textContent = "", 3000);
  }
}

// Toggle direkt speichern
toggle.addEventListener("change", async () => {
  try {
    await fetch("api/settings_save.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        enabled:     toggle.checked ? 1 : 0,
        quiet_from:  quietFrom.value  || null,
        quiet_until: quietUntil.value || null,
      }),
    });

    const wrap = document.querySelector(".card-ruhefenster");
    if (toggle.checked) {
      wrap.classList.remove("time-fields-disabled");
    } else {
      wrap.classList.add("time-fields-disabled");
    }
  } catch (err) {
    console.error("Toggle speichern fehlgeschlagen:", err);
  }
});

saveBtn.addEventListener("click", saveSettings);

// Einladung senden
document.getElementById("inviteBtn").addEventListener("click", async () => {
  const email      = document.getElementById("inviteEmail").value.trim();
  const inviteBtn  = document.getElementById("inviteBtn");
  const inviteFb   = document.getElementById("inviteFeedback");

  inviteFb.textContent = "";

  if (!email) {
    inviteFb.textContent = "Bitte eine E-Mail-Adresse eingeben.";
    inviteFb.style.color = "#c0392b";
    return;
  }

  inviteBtn.disabled = true;

  try {
    const res  = await fetch("api/invite_member.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email }),
    });
    const data = await res.json();

    if (data.status === "success") {
      inviteFb.textContent = "✓ Einladung wurde gesendet!";
      inviteFb.style.color = "#3a9e6e";
      document.getElementById("inviteEmail").value = "";
    } else {
      inviteFb.textContent = data.message || "Fehler beim Senden.";
      inviteFb.style.color = "#c0392b";
    }
  } catch {
    inviteFb.textContent = "Etwas ist schiefgelaufen.";
    inviteFb.style.color = "#c0392b";
  } finally {
    inviteBtn.disabled = false;
    setTimeout(() => inviteFb.textContent = "", 4000);
  }
});

// Profilname Modal
const nameModal     = document.getElementById("nameModal");
const nameInput     = document.getElementById("nameInput");
const nameSaveBtn   = document.getElementById("nameSaveBtn");
const nameCancelBtn = document.getElementById("nameCancelBtn");
const nameFeedback  = document.getElementById("nameFeedback");

document.getElementById("editNameBtn").addEventListener("click", async () => {
  try {
    const res  = await fetch("api/protected.php", { credentials: "include" });
    const data = await res.json();
    nameInput.value = data.name ?? "";
  } catch {
    nameInput.value = "";
  }
  nameFeedback.textContent = "";
  nameModal.classList.add("visible");
  nameInput.focus();
});

nameCancelBtn.addEventListener("click", () => nameModal.classList.remove("visible"));
nameModal.addEventListener("click", (e) => {
  if (e.target === nameModal) nameModal.classList.remove("visible");
});

nameSaveBtn.addEventListener("click", async () => {
  const name = nameInput.value.trim();
  if (!name || name.length < 2) {
    nameFeedback.textContent = "Bitte mindestens 2 Zeichen eingeben.";
    nameFeedback.style.color = "#c0392b";
    return;
  }

  nameSaveBtn.disabled = true;

  try {
    const res  = await fetch("api/update_name.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name }),
    });
    const data = await res.json();

    if (data.status === "success") {
      nameModal.classList.remove("visible");
    } else {
      nameFeedback.textContent = data.message ?? "Fehler beim Speichern.";
      nameFeedback.style.color = "#c0392b";
    }
  } catch {
    nameFeedback.textContent = "Etwas ist schiefgelaufen.";
    nameFeedback.style.color = "#c0392b";
  } finally {
    nameSaveBtn.disabled = false;
  }
});

nameInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter") nameSaveBtn.click();
});

document.addEventListener("DOMContentLoaded", () => {
  loadSettings();
  checkAdmin();
});