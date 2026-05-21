// protected.js
async function checkAuth() {
  try {
    const response = await fetch("api/protected.php", {
      credentials: "include",
    });

    if (response.status === 401) {
      window.location.href = "login.html";
      return false;
    }

    const result = await response.json();

    const hour = new Date().getHours();
    let salutation;
    if (hour < 12) salutation = "Guten Morgen";
    else if (hour < 18) salutation = "Guten Tag";
    else salutation = "Guten Abend";

    const greetingEl = document.getElementById("greeting");
    if (greetingEl) {
      greetingEl.textContent = `${salutation}, ${result.name}`;
      attachLogout();
      return true;
    }

    return true;

  } catch (error) {
    console.error("Auth check failed:", error);
    window.location.href = "login.html?error=fetch_failed";
    return false;
  }
}

function attachLogout() {
  const btn = document.getElementById("logoutBtn");
  if (!btn) return;
  btn.addEventListener("click", async () => {
    await fetch("api/logout.php", { credentials: "include" });
    window.location.href = "login.html";
  });
}

async function loadNotificationStatus() {
  try {
    const res = await fetch("api/settings_get.php", {
      credentials: "include",
    });
    const data = await res.json();
    const el = document.getElementById("notificationStatus");
    if (!el) return;

    if (!data.enabled || !data.quiet_from || !data.quiet_until) {
      el.textContent = "Immer aktiv";
    } else {
      el.textContent = `${data.quiet_from.slice(0,5)} bis ${data.quiet_until.slice(0,5)}`;
    }
  } catch (err) {
    console.error("Benachrichtigungsstatus konnte nicht geladen werden:", err);
  }
}

async function loadLastWakeTime() {
  try {
    const res  = await fetch("api/wachzeiten_get.php", {
      credentials: "include",
    });
    const data = await res.json();

    const el = document.getElementById("lastWakeTime");
    if (!el) return;

    if (!data.events || data.events.length === 0) {
      el.textContent = "Keine Daten";
      return;
    }

    const last = data.events[0];
    const dt   = new Date(last.triggered_at);
    const time = dt.toTimeString().slice(0, 5);

    const now   = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const d     = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
    const diff  = Math.round((today - d) / 86400000);

    const dateStr = diff === 0 ? "Heute" : diff === 1 ? "Gestern" : `${dt.getDate()}. ${["Jan","Feb","Mär","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"][dt.getMonth()]}`;

    el.textContent = `${dateStr}, ${time}`;
  } catch (err) {
    console.error("Letzte Wachzeit konnte nicht geladen werden:", err);
  }
}

document.addEventListener("DOMContentLoaded", loadLastWakeTime);
document.addEventListener("DOMContentLoaded", checkAuth);
document.addEventListener("DOMContentLoaded", loadNotificationStatus);