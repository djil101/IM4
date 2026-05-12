// connect-device.js
document.getElementById("connectForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const serialNr = document.getElementById("serialNr").value.trim().toUpperCase();
  const errorMsg = document.getElementById("errorMsg");

  errorMsg.textContent = "";
  errorMsg.style.display = "none";

  if (!serialNr) {
    errorMsg.textContent = "Bitte gib eine Seriennummer ein.";
    errorMsg.style.display = "block";
    return;
  }

  try {
    const response = await fetch("api/connect_device.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ serial_nr: serialNr }),
    });

    const result = await response.json();

    if (result.status === "success") {
      window.location.href = "protected.html";
    } else {
      errorMsg.textContent = result.message || "Verbindung fehlgeschlagen.";
      errorMsg.style.display = "block";
    }
  } catch (error) {
    console.error("Error:", error);
    errorMsg.textContent = "Etwas ist schiefgelaufen. Bitte versuche es erneut.";
    errorMsg.style.display = "block";
  }
});

// Später überspringen
document.getElementById("skipBtn").addEventListener("click", () => {
  window.location.href = "protected.html";
});

// Fehlermeldung beim Tippen ausblenden
document.getElementById("serialNr").addEventListener("input", () => {
  const errorMsg = document.getElementById("errorMsg");
  errorMsg.textContent = "";
  errorMsg.style.display = "none";
});