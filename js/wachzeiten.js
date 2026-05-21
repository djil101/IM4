// wachzeiten.js

async function loadWachzeiten() {
  try {
    const res = await fetch("api/wachzeiten_get.php", {
      credentials: "include",
    });

    if (res.status === 401) {
      window.location.href = "login.html";
      return;
    }

    const data = await res.json();

    // ── Woche ──
    const weekGrid = document.querySelector(".week-grid");
    weekGrid.innerHTML = "";
    data.week.forEach(d => {
      const div = document.createElement("div");
      div.className = "week-day";
      div.innerHTML = `
        <span class="week-time">${d.time ?? "–"}</span>
        <span class="week-name">${d.day}</span>
      `;
      weekGrid.appendChild(div);
    });

    // ── Stats ──
    document.querySelector(".stat-card:nth-child(1) .stat-value").textContent =
      data.avg_time ?? "–";
    const triggerMap = { "Bewegung": "Bewegung", "Laerm": "Lärm", "Beides": "Bewegung & Lärm" };
document.querySelector(".stat-card:nth-child(2) .stat-value").textContent =
  triggerMap[data.top_trigger] ?? data.top_trigger ?? "–";

    // ── Events ──
    const dashboard = document.querySelector(".dashboard");
    // Alte Event-Cards entfernen
    document.querySelectorAll(".event-card").forEach(e => e.remove());

    if (data.events.length === 0) {
      const empty = document.createElement("p");
      empty.style.cssText = "color:#9bb0be;text-align:center;padding:24px 0;";
      empty.textContent = "Noch keine Wachereignisse vorhanden.";
      dashboard.appendChild(empty);
      return;
    }

    data.events.forEach(e => {
      const dt       = new Date(e.triggered_at);
      const time     = dt.toTimeString().slice(0, 5);
      const dateStr  = formatDate(dt);
      const isLaerm  = e.trigger_type === "laerm";
      const isBeides = e.trigger_type === "beides";
      const inQuiet  = e.in_quiet;

      const triggerLabel = isLaerm ? "Lärm" : isBeides ? "Bewegung & Lärm" : "Bewegung";

      const icon = (isLaerm || isBeides)
        ? `<svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M11 5L6 9H2v6h4l5 4V5z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07" stroke="white" stroke-width="2" stroke-linecap="round"/>
           </svg>`
        : `<svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
           </svg>`;

      const badge = inQuiet
        ? `<span class="event-badge blue">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Innerhalb Ruhefenster
           </span>`
        : `<span class="event-badge green">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/><polyline points="9 12 11 14 15 10" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Bestätigt
           </span>`;

      const card = document.createElement("div");
      card.className = "event-card";
      card.innerHTML = `
        <div class="event-icon">${icon}</div>
        <div class="event-body">
          <p class="event-title">Wachereignis bestätigt</p>
          <p class="event-cause">Auslöser: ${triggerLabel}</p>
          ${badge}
        </div>
        <div class="event-time">
          <span class="event-clock">${time}</span>
          <span class="event-date">${dateStr}</span>
        </div>
      `;
      dashboard.appendChild(card);
    });

  } catch (err) {
    console.error("Wachzeiten konnten nicht geladen werden:", err);
  }
}

function formatDate(dt) {
  const now   = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const d     = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
  const diff  = Math.round((today - d) / 86400000);

  if (diff === 0) return "Heute";
  if (diff === 1) return "Gestern";

  const months = ["Jan","Feb","Mär","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"];
  return `${dt.getDate()}. ${months[dt.getMonth()]}`;
}

document.addEventListener("DOMContentLoaded", loadWachzeiten);