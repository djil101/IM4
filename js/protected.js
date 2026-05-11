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
    if (greetingEl) greetingEl.textContent = `${salutation}, ${result.name}`;

    return true;
  } catch (error) {
    console.error("Auth check failed:", error);
    return false;
  }
}

window.addEventListener("load", checkAuth);
