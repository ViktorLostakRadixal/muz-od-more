document.addEventListener('DOMContentLoaded', () => {
    const preloader = document.getElementById('preloader');
    const preloaderText = document.getElementById('preloader-text');
    const targetWord = document.getElementById('target-word');
    const censorBar = document.getElementById('censor-bar');

    // --- Sekvence animací ---

    // KROK 1: Zobrazit úvodní text
    setTimeout(() => {
        preloaderText.classList.add('visible');
    }, 500);

    // KROK 2: Zobrazit cenzurní pruh přes slovo "zemřeš"
    setTimeout(() => {
        const rect = targetWord.getBoundingClientRect();
        censorBar.style.top = `${rect.top}px`;
        censorBar.style.left = `${rect.left}px`;
        censorBar.style.width = `${rect.width}px`;
        censorBar.style.height = `${rect.height}px`;
        censorBar.classList.add('visible');
    }, 2000);

    // KROK 3: Skrýt text
    setTimeout(() => {
        preloaderText.classList.add('fade-out');
    }, 3500);

    // KROK 4: Transformovat pruh na tlačítko (dvoufázově)
    setTimeout(() => {
        const rect = censorBar.getBoundingClientRect();
        censorBar.style.top = `${rect.top}px`;
        censorBar.style.left = `${rect.left}px`;
        censorBar.style.width = `${rect.width}px`;
        censorBar.style.height = `${rect.height}px`;
        censorBar.classList.add('fixed-to-corner');

        // S malým zpožděním spustíme samotnou animaci do rohu.
        setTimeout(() => {
            // *** OPRAVA: Odstranění inline stylů před přidáním finální třídy ***
            censorBar.style.top = '';
            censorBar.style.left = '';
            censorBar.style.width = '';
            censorBar.style.height = '';

            censorBar.classList.add('is-button');
        }, 50);

    }, 4000);

    // KROK 5: Skrýt bílý preloader, zůstane jen tlačítko
    setTimeout(() => {
        preloader.classList.add('fade-out');
        preloader.style.pointerEvents = 'none';
    }, 5000);


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
            setupTextContentAnimation();
        });

        // Event listener pro začátek přehrávání videa
        videoElement.addEventListener('playing', () => {
            console.log(">>> Video 'playing' event fired.");
            if (imageBackgroundElement) {
                console.log("Hiding image background for #content.");
                imageBackgroundElement.classList.remove('visible'); // Skryjeme obrázek
            }
            // Krátká prodleva před spuštěním animace textu po startu videa
            // Spouštíme animaci jen jednou, při prvním 'playing' eventu
            if (authorElement && !authorElement.classList.contains('visible')) { // Přidána kontrola existence authorElement
                setTimeout(() => {
                    console.log("Short delay after video playing, calling setupTextContentAnimation.");
                    setupTextContentAnimation();
                }, 100);
            }
        });

        // === NOVÝ Event listener pro konec videa ===
        videoElement.addEventListener('ended', () => {
            console.log(">>> Video 'ended' event fired.");
            if (imageBackgroundElement) {
                console.log("Showing image background for #content again.");
                imageBackgroundElement.classList.add('visible'); // Znovu zobrazíme obrázek
            }
            if (imagePanDulakElement) {
                console.log("Hiding image #pan-dulak.");
                imagePanDulakElement.classList.remove('visible'); // Skryjeme obrázek
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
            if (imagePanDulakElement) {
                console.log("Hiding image #pan-dulak.");
                imagePanDulakElement.classList.remove('visible'); // Skryjeme obrázek
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
        if (exhibitorsElement) exhibitorsElement.textContent = "Kamila\u00A0Brůčková, Jakub\u00A0Hvězda, Eva\u00A0Jaroňová, Tereza\u00A0Darmovzalová, Frída\u00A0Kakao, Kateřina\u00A0Olivová, Hana\u00A0Svobodová, Libor\u00A0Veselý";
        if (locationDateElement) locationDateElement.textContent = "Kreativum Teletník 31.\u00A05.\u00A0-\u00A031.\u00A012.\u00A02025";

        // 2. Sekvenční zobrazení s časovači
        const authorDelay = 1500;
        const titleDelay = authorDelay + 2500;
        const exhibitorsDelay = titleDelay + 2000;
        const locationDelay = exhibitorsDelay + 1500;
        const panDulakDelay = locationDelay + 1000;

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

        setTimeout(() => {
            if (imagePanDulakElement) imagePanDulakElement.classList.add('visible');
            console.log("Showing pan-dulak image"); // Upravený log
        }, panDulakDelay);
    }

    // ===== OBSLUHA TLAČÍTKA "NEMAJÍ WEB" =====
    const chmatowButton = document.getElementById('chmatow-web-button');

    if (chmatowButton) {
        chmatowButton.addEventListener('click', () => {
            // Zobrazí jednoduché alert okno
            alert('Fakt nemají web...');
            // Alternativa: Můžete vytvořit malý textový element a zobrazit/skrýt ho
            // např. pod tlačítkem, pokud nechcete používat alert.
        });
    } else {
        console.log("Button #chmatow-web-button not found."); // Log pro případ chyby
    }
    // ========================================

    // --- Hamburger Menu Logic ---
    const hamburgerButton = document.querySelector('.hamburger-menu');
    const mainNav = document.getElementById('mainNav');

    if (hamburgerButton && mainNav) {
        const navLinks = mainNav.querySelectorAll('a'); // Získání odkazů uvnitř nav

        hamburgerButton.addEventListener('click', () => {
            hamburgerButton.classList.toggle('is-active');
            mainNav.classList.toggle('is-active');
            const isExpanded = hamburgerButton.classList.contains('is-active');
            hamburgerButton.setAttribute('aria-expanded', isExpanded);
            if (isExpanded) {
                document.body.style.overflow = 'hidden'; // Zabrání posouvání stránky, když je menu otevřené
            } else {
                document.body.style.overflow = ''; // Obnoví posouvání
            }
        });

        // Přidání posluchače událostí ke každému odkazu v navigaci pro zavření menu
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (mainNav.classList.contains('is-active')) {
                    hamburgerButton.classList.remove('is-active');
                    mainNav.classList.remove('is-active');
                    hamburgerButton.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = ''; // Obnoví posouvání
                }
            });
        });
        
        // Volitelné: Zavření menu při kliknutí mimo něj
        document.addEventListener('click', (event) => {
            if (mainNav.classList.contains('is-active') &&
                !mainNav.contains(event.target) && // Kliknutí není uvnitř menu
                !hamburgerButton.contains(event.target) && // Kliknutí není na hamburger tlačítko
                 event.target !== hamburgerButton && // Dodatečná kontrola pro samotné tlačítko
                !event.target.closest('.hamburger-menu')) { // Kontrola, zda kliknutí není na potomka hamburgeru
                    hamburgerButton.classList.remove('is-active');
                    mainNav.classList.remove('is-active');
                    hamburgerButton.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
            }
        });

    } else {
        console.log("Hamburger menu elements not found.");
    }
    // ========================================

}); // Konec DOMContentLoaded
