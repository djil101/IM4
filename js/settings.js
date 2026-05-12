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