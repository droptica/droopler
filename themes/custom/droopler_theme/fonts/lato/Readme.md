# Updating Lato
## Prepare new Lato Webfont without LineGap

  - download new Lato TTF package from `https://www.latofonts.com/pl/lato-free-fonts/`
  - go to `https://www.fontsquirrel.com/tools/webfont-generator` and upload font `Lato-Regural.ttf` from the downloaded package; it should have at least 3031 glyphs
  - set generator to EXPERT:
    - Font Formats: TrueType, WOFF, WOFF2, EOT Compressed
    - Truetype Hinting: Keep Existing
    - Rendering: Fix GASP Table
    - Vertical Metrics: Custom Adjustments: LineGap: 0
    - Fix Missing Glyphs: Spaces, Hyphens
    - X-height Matching: none
    - Subsetting: No Subsetting
  - Download KIT and unzip
  - Make sure that files with font have correct names and replace them in `web/profiles/contrib/droopler/themes/custom/droopler_theme/fonts/lato`
  - Reset browser cache and check on Shortcodes page if everything is ok. Especially if the font in buttons is vertically centered.
