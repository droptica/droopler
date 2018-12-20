# Droopler template for new project #
<img src="https://demo.droopler.com/themes/custom/droopler_subtheme/logo.svg" width=300 alt="Droopler Logo" />

## What is Droopler? ##
Droopler is a Drupal 8 profile designed to kickstart a new webpage in a few minutes. It's based on the latest frontend  technologies, including Bootstrap 4. The maintainer of Droopler is [Droptica](https://droptica.com).

* **Official website**: [droopler.com](https://droopler.com)
* **Documentation**: [droopler.com/developers](https://droopler.com/developers)
* **Demo**: [demo.droopler.com](https://demo.droopler.com)
* **Profile repository**: [github.com/droptica/droopler](https://github.com/droptica/droopler)
* **Drupal.org project**: [drupal.org/project/droopler](https://www.drupal.org/project/droopler)

For the latest news please subscribe to our [Facebook](https://www.facebook.com/Droopler/) and [Twitter](https://twitter.com/DrooplerCMS).

## What is this Droopler template? ##
It's a skeleton, a boilerplate for new projects based on Droopler. If you wish to use Droopler - fork (or download) this repository. It contains a minimum set of code to start your new website. Threat it the same as [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project).

This code includes:

- **composer.json** with all dependencies required to run Droopler.
- **.gitignore** adjusted to use GIT with Drupal.
- Some scripts for composer to handle new project's installations.
- Boilerplate subtheme with minimal required CSS/SCSS and Javascript. It contains gulpfile.js to speed up development of Drupal's frontend.

## How to build the website? ##

**1) Run Composer**

```sh
$ composer create-project droptica/droopler-project droopler
$ cd droopler
$ composer drupal-scaffold
$ composer install
```

The *composer install* must be run here to put some external assets into proper directories. The scaffold command makes sure that all files like *index.php* are on their place.

**2) Run npm**

Droopler is using Gulp stack to speed up development of new sites. It compiles SCSS to CSS, enables Autoprefixer to deal with browser compatibility and minimizes all JavaScript files. [Install latest node.js and npm](https://nodejs.org/en/download/) on your computer and in the root directory of your project run the following commands:

```sh
$ cd web/themes/custom/droopler_subtheme
$ npm install
$ npm install --global gulp-cli
$ gulp compile
```

**3) Run Drupal installation**

Go to http://yourserver.local/install.php and follow the steps of configuration.

## How to work with the subtheme? ##

First run **gulp watch** in your subtheme's directory. It will track all the changes in theme source files and compile assets in the fly.

```sh
$ cd web/themes/custom/droopler_subtheme
$ gulp watch
```

There are also other Gulp commands for theme developers, here's the full reference:

 - **gulp watch** - watches for changes in SCSS and JS and proceses them on the fly
 - **gulp compile** - cleans derivative files and compiles all SCSS/JS in the subtheme for DEV environment
 - **gulp dist** - cleans derivative files and compiles all SCSS/JS in the subtheme for PROD environment
 - **gulp clean** - cleans derivative files
 - **gulp-debug** - prints Gulp debug information, this comes in handy when something's not working

## SCSS structure ##

 - **style.scss** - combines all SCSS code from base theme and subtheme
 - **print.scss** - combines all SCSS code for printing from base theme and subtheme
 - **config/** - the most important directory that contains the subtheme configuration - you can add your own config files like _foobar.scss, just refer to them in _all.scss.
 - **libraries/** - additional files needed by Drupal

You can use any SCSS structure you like. We recommend dividing files into **layout/** and **components/** directories. Just remember to include your files in **style.scss**.

# SCSS Configuration ##

Droopler is designed to make your work easier. You don't have to override SCSS or CSS code to make your own adjustments. In most cases it is enough to modify the configuration. Just look into variable definitions in the subtheme's **scss/config/_base_theme_overrides.scss** file.

```scss
// Colours - The Greeks
// -------------------------
// $color-odysseus: white;

// Paragraph d_p_banner
// -------------------------
// $d-p-banner-header-color: $color-odysseus;
// $d-p-banner-subheader-color: $color-odysseus;
```

To alter this - uncomment the line and change the value. A you can see - there are many levels of variables, see the comments in _base_theme_overrides.scss to get some more information.

When you save this config file, **gulp watch** will recompile all SCSS with your own config.

## How to install Google Fonts? ##

By default Droopler uses free [Lato](http://www.latofonts.com/) webfont. If you wish to install your own fonts from Google - put their definitions into **droopler_subtheme.libraries.yml** like this:

```yaml
global-styling:
  version: VERSION
  css:
    theme:
      '//fonts.googleapis.com/css?family=Rajdhani:500,600,700|Roboto:400,700&subset=latin-ext': { type: external, minified: true }
      css/style.css: {}
```

## How to install icon fonts? ##

If you wish to install FontAwesome or Glyphicons from the CDN - just grab their URLs and follow the steps described in previous chapter about Google Fonts. You'll find a FontAwesome example in **droopler_subtheme.libraries.yml** and **droopler_subtheme.info.yml**.
