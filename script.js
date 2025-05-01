document.addEventListener('DOMContentLoaded', () => {
    // --- Elementy ---
    const videoElement = document.getElementById('video');
    const imageBackgroundElement = document.querySelector('#content #image-background');
    const imagePanDulakElement = document.querySelector('#pan-dulak');
    const authorElement = document.getElementById('author');
    const titleElement = document.getElementById('title');
    const exhibitorsElement = document.getElementById('exhibitors');
    const locationDateElement = document.getElementById('location-date');

    console.log("Script loaded. Initializing.");

    // --- Logika pro pozadí (Obrázek / Video) ---
    if (imageBackgroundElement) {
        console.log("Showing initial image background for #content.");
        imageBackgroundElement.classList.add('visible'); // Zobrazíme obrázek hned
    } else {
        console.error("Image background element (#image-background inside #content) not found!");
    }

    if (videoElement) {
        console.log("Setting up video listeners.");

        // Event listener pro chybu videa
        videoElement.addEventListener('error', (e) => {
            console.error('*** Video loading/playing Error:', e);
            console.log("Video error, keeping image background visible.");
            // Zajistíme, aby i při chybě byl obrázek vidět (pro jistotu)
            if (imageBackgroundElement && !imageBackgroundElement.classList.contains('visible')) {
                imageBackgroundElement.classList.add('visible');
            }
            if (imagePanDulakElement && !imagePanDulakElement.classList.contains('visible')) {
                imagePanDulakElement.classList.add('visible');
            }
            setupTextContentAnimation();
        });

        // Event listener pro začátek přehrávání videa
        videoElement.addEventListener('playing', () => {
            console.log(">>> Video 'playing' event fired.");
            if (imageBackgroundElement) {
                console.log("Hiding image background for #content.");
                imageBackgroundElement.classList.remove('visible'); // Skryjeme obrázek
            }
            if (imagePanDulakElement) {
                console.log("Hiding image #pan-dulak.");
                imagePanDulakElement.classList.remove('visible'); // Skryjeme obrázek
            }
            // Krátká prodleva před spuštěním animace textu po startu videa
            // Spouštíme animaci jen jednou, při prvním 'playing' eventu
            if (!authorElement.classList.contains('visible')) {
                setTimeout(() => {
                    console.log("Short delay after video playing, calling setupTextContentAnimation.");
                    setupTextContentAnimation();
                }, 100);
            }
        }); // Odebráno { once: true } - může být potřeba reagovat vícekrát, pokud by video bylo např. pozastaveno a znovu spuštěno

        // === NOVÝ Event listener pro konec videa ===
        videoElement.addEventListener('ended', () => {
            console.log(">>> Video 'ended' event fired.");
            if (imageBackgroundElement) {
                console.log("Showing image background for #content again.");
                imageBackgroundElement.classList.add('visible'); // Znovu zobrazíme obrázek
            }
            if (imagePanDulakElement) {
                console.log("Showing image background for #content again.");
                imagePanDulakElement.classList.add('visible'); // Znovu zobrazíme obrázek
            }
        });
        // === Konec nového listeneru ===

        // Pokus o spuštění videa
        videoElement.play().catch(error => {
            console.error("Could not play video initially (maybe browser policy):", error);
            console.log("Video autoplay failed, keeping image background visible.");
            // Zajistíme, aby i při selhání autoplay byl obrázek vidět
            if (imageBackgroundElement && !imageBackgroundElement.classList.contains('visible')) {
                imageBackgroundElement.classList.add('visible');
            }
            if (imagePanDulakElement && !imagePanDulakElement.classList.contains('visible')) {
                imagePanDulakElement.classList.add('visible');
            }
            setupTextContentAnimation(); // Spustíme animaci textu
        });

    } else {
        console.error("Video element not found!");
        setupTextContentAnimation();
    }

    // --- Funkce pro animaci textu ---
    function setupTextContentAnimation() {
        // Kontrola, zda už animace neběží/neproběhla
        if (authorElement && authorElement.classList.contains('visible')) {
            console.log("Text animation already triggered or running, skipping.");
            return;
        }
        console.log("Setting up text content and animation.");

        // 1. Nastavení textu
        if (authorElement) authorElement.textContent = "Jana Písaříková";
        if (titleElement) titleElement.textContent = "Muž od\u00A0moře";
        if (exhibitorsElement) exhibitorsElement.textContent = "Kamila Brůčková, Jakub Hvězda, Eva Jaroňová, Tereza Darmovzalová, Frída Kakao, Kateřina Olivová, Hana Svobodová, Libor Veselý";
        if (locationDateElement) locationDateElement.textContent = "Kreativum Teletník 31.\u00A05.\u00A0-\u00A031.\u00A012.\u00A02025";

        // 2. Sekvenční zobrazení s časovači
        const authorDelay = 1500;
        const titleDelay = authorDelay + 2500;
        const exhibitorsDelay = titleDelay + 2000;
        const locationDelay = exhibitorsDelay + 1500;

        setTimeout(() => {
            if (authorElement) authorElement.classList.add('visible');
            console.log("Showing author");
        }, authorDelay);

        setTimeout(() => {
            if (titleElement) titleElement.classList.add('visible');
            console.log("Showing title");
        }, titleDelay);

        setTimeout(() => {
            if (exhibitorsElement) exhibitorsElement.classList.add('visible');
            console.log("Showing exhibitors");
        }, exhibitorsDelay);

        setTimeout(() => {
            if (locationDateElement) locationDateElement.classList.add('visible');
            console.log("Showing location/date");
        }, locationDelay);
    }

}); // Konec DOMContentLoaded