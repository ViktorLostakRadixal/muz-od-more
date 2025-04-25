document.addEventListener('DOMContentLoaded', () => {
    // --- Elementy ---
    const videoElement = document.getElementById('video');
    const authorElement = document.getElementById('author');
    const titleElement = document.getElementById('title');
    // ===== PŘIDÁN ELEMENT PRO VYSTAVUJÍCÍ =====
    const exhibitorsElement = document.getElementById('exhibitors');
    // ==========================================
    const locationDateElement = document.getElementById('location-date');

    console.log("Script loaded. Initializing.");

    if (videoElement) {
        console.log("Setting up initial video.");
        videoElement.addEventListener('error', (e) => {
            console.error('*** Video loading Error:', e);
        });

        // Používáme 'playing' event listener
        videoElement.addEventListener('playing', () => {
            console.log(">>> Video 'playing' event fired.");
            setTimeout(() => {
                 console.log("Short delay finished, calling setupTextContentAnimation.");
                 setupTextContentAnimation();
            }, 100); // Malá prodleva
        }, { once: true });

        videoElement.play().catch(error => {
            console.error("Could not play video initially:", error);
            // setTimeout(setupTextContentAnimation, 100); // Možná spustit i při chybě?
        });

    } else {
        console.error("Video element not found!");
        setTimeout(setupTextContentAnimation, 100);
    }

    // --- Funkce pro animaci textu ---
    function setupTextContentAnimation() {
        console.log("Setting up text content and animation.");

        // 1. Nastavení textu
        if(authorElement) authorElement.textContent = "Jana Písaříková";
        if(titleElement) titleElement.textContent = "Muž od\u00A0moře";
        // ===== NASTAVENÍ TEXTU PRO VYSTAVUJÍCÍ =====
        if(exhibitorsElement) exhibitorsElement.textContent = "Kamila Brůčková, Jakub Hvězda, Eva Jaroňová, Tereza Darmovzalová, Frída Kakao, Kateřina Olivová, Hana Svobodová, Libor Veselý";
        // ==========================================
        if(locationDateElement) locationDateElement.textContent = "Kreativum Teletník 31.\u00A05.\u00A0-\u00A031.\u00A012.\u00A02025";

        // 2. Sekvenční zobrazení s časovači - upravené pořadí a časy
        const authorDelay = 1500;    // 1.5s
        const titleDelay = authorDelay + 2500;     // +2.5s = 4.0s
        const exhibitorsDelay = titleDelay + 2000;   // +2.0s = 6.0s
        const locationDelay = exhibitorsDelay + 1500; // +1.5s = 7.5s

        setTimeout(() => {
            if(authorElement) authorElement.classList.add('visible');
            console.log("Showing author");
        }, authorDelay);

        setTimeout(() => {
            if(titleElement) titleElement.classList.add('visible');
            console.log("Showing title");
        }, titleDelay);

        // ===== PŘIDÁN setTimeout PRO VYSTAVUJÍCÍ =====
        setTimeout(() => {
            if(exhibitorsElement) exhibitorsElement.classList.add('visible');
            console.log("Showing exhibitors");
        }, exhibitorsDelay);
        // =============================================

        setTimeout(() => {
            if(locationDateElement) locationDateElement.classList.add('visible');
            console.log("Showing location/date");
        }, locationDelay);
    }

}); // Konec DOMContentLoaded