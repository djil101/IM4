// settings.js

const toggle      = document.getElementById("notificationsEnabled");
const quietFrom   = document.getElementById("quietFrom");
const quietUntil  = document.getElementById("quietUntil");
const saveBtn     = document.getElementById("saveBtn");
const feedback    = document.getElementById("saveFeedback");

// Einstellungen beim Laden der Seite aus DB holen
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

    // Zeitfelder beim Laden gleich korrekt ein/ausgrauen
    const wrap = document.querySelector(".card-ruhefenster");
    if (!data.enabled) {
      wrap.classList.add("time-fields-disabled");
    }

  } catch (err) {
    console.error("Einstellungen konnten nicht geladen werden:", err);
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
    console.error("Speichern fehlgeschlagen:", err);
    feedback.textContent = "Etwas ist schiefgelaufen.";
    feedback.style.color = "#c0392b";
  } finally {
    saveBtn.disabled = false;
    setTimeout(() => feedback.textContent = "", 3000);
  }
}

// Toggle direkt speichern ohne Speichern-Button
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

    // Zeitfelder ausgrauen wenn Toggle aus
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

document.addEventListener("DOMContentLoaded", loadSettings);

// Profilname Modal
const nameModal     = document.getElementById("nameModal");
const nameInput     = document.getElementById("nameInput");
const nameSaveBtn   = document.getElementById("nameSaveBtn");
const nameCancelBtn = document.getElementById("nameCancelBtn");
const nameFeedback  = document.getElementById("nameFeedback");

// Modal öffnen – aktuellen Namen vorladen
document.querySelector(".settings-item:not(.danger)").addEventListener("click", async () => {
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

// Modal schliessen
nameCancelBtn.addEventListener("click", () => nameModal.classList.remove("visible"));
nameModal.addEventListener("click", (e) => {
  if (e.target === nameModal) nameModal.classList.remove("visible");
});

// Speichern
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

// Enter-Taste im Inputfeld
nameInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter") nameSaveBtn.click();
});