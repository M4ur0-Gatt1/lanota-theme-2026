# Icons (Self-hosted)

Place a local Font Awesome build or your preferred icon set here.

Option A: Font Awesome local
- fontawesome.min.css
- webfonts/ (folder with .woff2/.woff)

Keep paths inside the CSS pointing to: `../icons/webfonts/...` or adjust URLs to absolute theme path.

Option B: Custom subset (recommended)
- Create a subset with only the icons you use (SVG/WOFF2) via Font Awesome Kits, IcoMoon, or SVGOMG.
- Update your markup or add a small CSS mapping.

Theme is configured to load local CSS if `assets/icons/fontawesome.min.css` exists; else it falls back to the CDN.
