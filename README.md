# Droopler template for new project #
[![N|Solid](https://www.droopler.pl/sites/default/files/logo_droopler.jpg)](http://droopler.pl)

## What is Droopler? ##
Droopler is a Drupal 8 profile designed to kickstart a new webpage in a few minutes. It's based on the latest frontend technologies, including Bootstrap 4. The maintainer of Droopler is [Droptica](https://droptica.com).

## What is this Droopler template? ##
It's a skeleton, a boilerplate for new projects based on Droopler. If you wish to use Droopler - fork (or download) this repository. It contains a minimum set of code to start your new website. Threat it the same as [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project).

This code includes:

- **composer.json** with all dependencies required to run Droopler.
- **.gitignore** adjusted to use GIT with Drupal.
- Some scripts for composer to handle new project's installations.
- Boilerplate subtheme with minimal required CSS/SCSS and Javascript. It contains gulpfile.js to speed up development of Drupal's frontend.

## How to build the website? ##

First download this repository and set webserver's web root to **/web** directory. Then perform the folowing steps:

**1) Run Composer**

```sh
$ composer install
$ composer drupal-scaffold
$ composer install
```

No, it's not a mistake. Run the composer twice to put some external assets into proper directories. The scaffold thing between makes sure that all files are on their place.

**2) Run npm**

Droopler is using Gulp stack to speed up development of new sites. It compiles SCSS to CSS, enables Autoprefixer to deal with browser compatibility and minimizes all JavaScript files. [Install latest node.js and npm](https://nodejs.org/en/download/) on your computer and in the root directory of your project run the following commands:

```sh
$ cd web/themes/custom/droopler_subtheme
$ npm install
$ npm install --global gulp-cli
$ gulp compile
```

**3) Run Drupal installation**

Go to http://yourserver.dev/install.php, choose **Droopler** install profile. Follow the steps of configuration.

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

Droopler is designed to make your work easier. You don't have to override SCSS or CSS code to make your own adjustments. In most cases it is enough to modify the configuration. Just look into base theme's SCSS files. They often start with some variable definitions like:

```scss
// Variables used in this file
$banner-text-color: $color-odysseus !default;
$banner-background: $color-cassandra !default;
$banner-border: $color-odysseus !default;
$banner-font-size: 2rem !default;

// Banner component
.banner {
	background: $banner-background;
	border: 1px solid $banner-border;
	color: $banner-text-color;
	font-size: $banner-font-size;
}
```

To alter this you have to create a new config file (let's name it **config/_foo.scss**) and add this line into **config/_all.scss**:

```scss
@import "foo";
```

Then fill **config/_foo.scss** with your modifications:

```scss
// My overrides
$color-cassandra: white;
$banner-text-color: red;
$banner-font-size: 3rem;
```

What happens here?
 - You set a variable **$color-cassandra**. The trojan name means it's a colour from the palette, used in many places of the website. What are the trojan heroes doing here? They are meant not to suggest any specific color. If we change a color from white to black we don't have to change variable name.
 - You set **$banner-text-color** to red. This will overwrite one single element on the website. Your color will be used instead of pallete color **$color-odysseus**. But **$color-odysseus** will stay intact on other elements.
 - You set a font size.

When you save this config file, **gulp watch** will recompile all SCSS with your own config.

## How to install Google Fonts? ##

By default Droopler uses free [Lato](http://www.latofonts.com/) webfont. If you wish to install your own fonts from Google - put their definitions into web/themes/custom/droopler_subtheme/droopler_subtheme.libraries.yml like this:

```yaml
global-styling:
  version: VERSION
  css:
    theme:
      '//fonts.googleapis.com/css?family=Rajdhani:500,600,700|Roboto:400,700&subset=latin-ext': { type: external, minified: true }
      css/style.css: {}
```

## How to install icon fonts? ##

If you wish to install FontAwesome or Glyphicons from the CDN - just grab their URLs and follow the steps described in previous chapter about Google Fonts.
