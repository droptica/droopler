# SCSS Starterkit for Droopler
This is a starter child theme of `droopler_theme`, you may use it as a derivative with your own overrides.

Assume your new theme will be called `foobar`.

1. Copy `STARTERKIT_CSS` to your custom theme location, like `web/themes/custom/foobar`.
2. Change all `STARTERKIT` references to `foobar` (including content and file names).
3. Change `STARTERKIT.rename.yml` to `foobar.info.yml`.
4. Compile `droopler_theme` by running the following commands in its directory:

       npm install --global gulp-cli
       npm install
       gulp compile

5. Compile `foobar` by running the following commands in its directory:

       npm install
       gulp compile

   You may need to adjust some paths in SCSS files.

And that's it! Your theme is visible on `Appearance` admin page.
