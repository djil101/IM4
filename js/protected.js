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

document.addEventListener("DOMContentLoaded", checkAuth);
document.addEventListener("DOMContentLoaded", loadNotificationStatus);