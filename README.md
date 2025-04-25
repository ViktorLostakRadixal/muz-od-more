# Muž od moře - Webová prezentace výstavy

Toto je repozitář pro webovou prezentaci kolektivní výstavy "Muž od moře", kurátorky Jany Písaříkové[cite: 26], konané v prostoru Kreativum Teletník v Runářově od 31. 5. do 31. 12. 2025.

Výstava zkoumá možnosti vyprávění životního příběhu Adriana "Chocho" Sitty[cite: 19], bloggera a nomáda, který tragicky zahynul počátkem roku 2025. Projekt propojuje díla současných umělců, performance, dokumentační materiály a výstupy umělé inteligence[cite: 20]. Tematizuje smysl cestování, vyrovnávání se se ztrátou a křehkost existence v kontextu moderních technologií[cite: 21].

Webová stránka využívá celoobrazovkové video pozadí, sekvenční animaci textu a vertikální členění obsahu do sekcí s informacemi o výstavě, vystavujících a místu konání.

## Live Demo

[http://muz-od-more.cz](http://muz-od-more.cz)

_(Odkaz bude funkční po nasazení a nastavení domény)_

## Použité technologie

* HTML5
* CSS3
    * Flexbox
    * CSS Transitions & Animations (@keyframes)
    * Responzivní jednotky (vh, vw, svh)
    * `clamp()` pro fluidní typografii
* Vanilla JavaScript (ES6+)
    * DOM manipulace
    * Event Listeners (DOMContentLoaded, playing)
    * `setTimeout` pro animace
* Google Fonts (Cinzel, Cormorant Garamond)

## Struktura repozitáře

Doporučená struktura souborů pro přehlednost:

/
|-- index.html             # Hlavní HTML soubor
|-- style.css              # Hlavní CSS soubor (např. style.css?v=X)
|-- script.js              # Hlavní JavaScript soubor (např. script.js?v=X)
|-- assets/                # Složka pro média
|   |-- video/
|   |   |-- muz-od-more-pg-bkg.mp4  # Soubor s videem na pozadí
|-- README.md              # Tento soubor
|-- (.gitignore)           # Volitelně: Pro ignorování nepotřebných souborů


**Poznámka:** Pokud jste video umístili do `assets/video/`, ujistěte se, že cesta v atributu `src` elementu `<video>` v `index.html` je správně upravena (`src="assets/video/muz-od-more-pg-bkg.mp4"`).

## Deployment

Tento projekt je statický web (pouze HTML, CSS, JS a video) a je ideální pro nasazení na platformách jako:

* **Vercel (doporučeno):** Nabízí snadné propojení s GitHub repozitářem, automatické nasazování, globální CDN a štědrý bezplatný plán.
* Netlify: Podobná platforma jako Vercel.
* GitHub Pages: Další bezplatná možnost přímo od GitHubu.

Pro nasazení na Vercelu stačí propojit váš GitHub účet, vybrat tento repozitář a Vercel by měl automaticky rozpoznat nastavení (root directory, žádný build command). Následně můžete v nastavení Vercelu nakonfigurovat vlastní doménu `muz-od-more.cz`.

## Lokální spuštění

Pro zobrazení webu lokálně stačí otevřít soubor `index.html` v jakémkoli moderním webovém prohlížeči. Není potřeba žádný lokální server ani build proces.

---

*Tento README soubor byl vygenerován na základě konverzace a poskytnutých informací.*
