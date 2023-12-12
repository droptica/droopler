# Updating Droopler

## Droopler 4.0.0

It's a completely new Droopler version. There is no backwards compatibility from Droopler 4.0 to lower.

> You can try to run it on your existing database with lower Droopler version but there is no guarantee it would work.


### Changes

Front-end:
- New theme and STARTERKITs based on Bootstrap 5 and Radix.
- Paragraph templates based on the front-end single directory components (TWIG, SCSS and JS in one directory).
- The style colors, spacers and component parameters are easily changeable by editing the CSS variables.
- There is an easy way to extend the component, overwrite it completely or create a new one.

Back-end:
- Based on Drupal 10.
- Full compatibility with PHP 8.1.
- Unnecessary code has been removed from the custom modules.
- Most of the custom code has been changed to object-oriented.
- Some unused or unnecessary modules have been removed.
- The [Geysir](https://www.drupal.org/project/geysir) has been replaced by the [Frontend editing](https://www.drupal.org/project/frontend_editing) module.
- Blog (`d_blog`) and Product (`d_product`) modules dependencies have been removed from the main `composer.json` file.
- The issues from PHP CodeSniffer have been reviewed and fixed.
- The issues from PHP Stan have been reviewed and fixed.
- Integration with Drupal Commerce (`d_commerce`) has been temporarily removed. Will be restored with the next version.

## Droopler 3.1.0

### PHP 8.x
If you want to run Droopler with PHP 8.x, you should include `cweagans/composer-patches` package in your `composer.json`:
```json
{
    "require": {
        "cweagans/composer-patches": "^1.6"
    },
    "extra": {
        "enable-patching": true,
    }
}
```

### Composer 2.2
If you are using Composer >= 2.2.0, remember to add these lines to your `composer.json`:
```json
{
    "allow-plugins": {
        "dealerdirect/phpcodesniffer-composer-installer": true,
        "composer/installers": true,
        "cweagans/composer-patches": true,
        "drupal/console-extend-plugin": true,
        "drupal/core-composer-scaffold": true,
        "oomphinc/composer-installers-extender": true,
        "zaporylie/composer-drupal-optimizations": true
    }
}
```

### Droopler Commerce
Class `DrooplerProductVariation` has been removed, if you want to restore it for your project, you can find it [here](https://github.com/droptica/droopler/tree/master/modules/custom/d_commerce/modules/d_commerce_product/src/Entity):

You can attach it by using this hook:

```php
function mymodule_entity_type_build(array &$entity_types) {
  $entity_types['commerce_product_variation']->setClass('Drupal\mymodule\Entity\DrooplerProductVariation');
}
```

## Droopler 3.0.0

No significant actions to be done in this update. The main change is **new versioning** and improved D9 compatibility.

Please note that since this version, `droopler_theme` uses `DartSass` instead of deprecated `NodeSass`. You may change it also for your `droopler_subtheme`.

## Droopler 2.2

No significant actions to be done in this update.

You may want to verify your paragraph themes, as we introduced new `d_settings` for each paragraph. The migration script should cover most edge cases.

Starting from this version, we are officially becoming Drupal 9 compatible. You may start considering the upgrade.

## Droopler 2.1

No significant actions to be done in this update.

## Droopler 2.0

**WARNING!**

* If you have a composer-based installation, please apply all the below steps.
* If you have a drupal.org installation, update your `profiles/droopler` directory and make sure the modules in `modules/contrib` and `profiles/droopler/modules/contrib` are not duplicated (they should stay in the profile). Also, change your theme to `droopler_theme` if you did not make any custom overrides to `droopler_subtheme`. This way you won't have to maintain your subtheme anymore. If you have a custom subtheme, please apply all the below steps.

### 1. Update your subtheme regions ##

Please update your `themes/custom/droopler_subtheme.info.yml` file and make sure it has the following regions defined:

```yml
regions:
  header: Header
  secondary_menu: 'Secondary menu'
  primary_menu: 'Primary menu'
  lang_menu: 'Language menu'
  page_top_content: 'Page top'
  page_top: 'Page top'
  page_bottom: 'Page bottom'
  page_bottom_content: 'Page bottom'
  highlighted: Highlighted
  featured_top: 'Featured top'
  breadcrumb: Breadcrumb
  admin_tabs: 'Admin tabs'
  content: Content
  facets_top: 'Facets top'
  facets_left_top: 'Facets left top'
  facets_left: 'Facets left'
  sidebar_left: 'Sidebar left'
  sidebar_right: 'Sidebar right'
  featured_bottom_first: 'Featured bottom first'
  featured_bottom_second: 'Featured bottom second'
  featured_bottom_third: 'Featured bottom third'
  footer_first: 'Footer first'
  footer_second: 'Footer second'
  footer_third: 'Footer third'
  footer_fourth: 'Footer fourth'
  footer_fifth: 'Footer fifth'
  footer_sixth: 'Footer sixth'
  footer_main: 'Footer Main'
  ```

### 2. Update your subtheme files

In the new version, we refactored the process of building assets. To make it work in your subtheme, you have to update the following files in your `themes/custom/droopler_subtheme` directory to the ones [from here](https://github.com/droptica/droopler_project/tree/master/web/themes/custom/droopler_subtheme):

* `gulpfiles.js`
* `package.json`
* also, remove `package-lock.json`

To compile the assets, run the following commands:

* `npm install` in the directory `profiles/contrib/droopler/themes/custom/droopler_theme`,
* `gulp compile` in the directory `profiles/contrib/droopler/themes/custom/droopler_theme`.
* `npm install` in the directory `themes/custom/droopler_subtheme`,
* `gulp compile` in the directory `themes/custom/droopler_subtheme`.

Please note that Droopler 2.0 works with the latest version of node.js.

### 3. Update your composer files (optional)

In 2.0 we changed the composer template to the one provided by Drupal 8.8. You don't have to update your `composer.json`, however, it will simplify your project's maintenance.

### 4. Restore the blue color scheme (optional)
Since version 1.4, the color scheme has changed from blue to red. In case you've grown accustomed to the old Droopler's color scheme, we provide you with a file containing SCSS variables from the older Droopler builds. This file is located in `themes/custom/droopler_theme/scss/config/_old_color_scheme.scss`

In order to restore old color scheme either copy it's contents into the  `themes/custom/droopler_theme/scss/config/_color.scss`,
or modify `themes/custom/droopler_theme/scss/config/_all.scss` like this:

```scss (scss/config/_all.scss)

@import "layout";
@import "colors"; // Remove this line
@import "fonts";
@import "paths";
@import "bootstrap-overrides";

@import "layout";
@import "old_color_scheme"; // Add this new line
@import "fonts";
@import "paths";
@import "bootstrap-overrides";

```

After compiling SCSS, you can enjoy Droopler's 2.0 new functionalities while retaining the old color scheme.
In case you modified the default Droopler color scheme, you might want to use this old color scheme file as a guideline to moving your color scheme onto 2.0
