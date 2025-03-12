# Droopler profile for Drupal
<img src="https://droopler-demo.droptica.com/themes/custom/droopler_subtheme/logo.svg" width=300 alt="Droopler Logo" />

## What is Droopler?
Droopler is a Drupal 10 profile designed to build corporate websites. It's based on the latest frontend technologies, including Bootstrap 4. The maintainer of Droopler is [Droptica](https://www.droptica.com).

[![Build Status](https://github.com/droptica/droopler/workflows/Drupal%20coding%20standards/badge.svg?branch=master)](https://github.com/droptica/droopler/actions)

* **Official website**: [droptica.com/droopler](https://www.droptica.com/droopler)
* **Tutorials**: [droptica.com/droopler/tutorials](https://www.droptica.com/droopler/tutorials/)
* **Demo**: [droopler-demo.droptica.com](https://droopler-demo.droptica.com)
* **Composer template**: [github.com/droptica/droopler_project](https://github.com/droptica/droopler_project)
* **Drupal.org project**: [drupal.org/project/droopler](https://www.drupal.org/project/droopler)

For the latest news please subscribe to our [Facebook](https://www.facebook.com/Droopler/) and [Twitter](https://twitter.com/DrooplerCMS).

## What's in this repository?
This repository contains a Drupal profile. When you put it in the `/profiles/contrib/droopler` directory, the Drupal installer gets modified and installs base Droopler theme, some module dependencies, and demo content.

## Installation
The Droopler profile should be installed via Composer. We recommend using [Droopler skeleton repository](https://github.com/droptica/droopler_project). If you are starting from the scratch - in the **require** section of your composer.json put:

```json
"require": {
  "droptica/droopler": "^3"
}
```

And run **composer update**.

In case of unexpected problems please update your main composer.json to comply with the latest [Droopler skeleton repository](https://github.com/droptica/droopler_project). You may run into some issues with libraries and their directories.

# Documentation
* [Droopler Commerce](modules/custom/d_commerce/README.md) - This distribution provides full Drupal Commerce integration.
* [Updating Droopler](UPDATE.md) - A guide on updating the distribution between major versions.
* [Using d_settings](modules/custom/d_p/README.md) - How to create new paragraph settings and modify existing ones.
* [Using SCSS](https://github.com/droptica/droopler_project/blob/master/README.md) - How to handle SCSS using Node.
* [Creating CSS subtheme](themes/custom/droopler_theme/STARTERKIT_CSS/README.md) - How to create a simple subtheme with CSS inheritance.
* [Creating SCSS subtheme](themes/custom/droopler_theme/STARTERKIT_SCSS/README.md) - How to create a complex subtheme with SCSS variables.

## How to upgrade Droopler from 3.3.x to 3.5.x for Drupal 11

1. Run `composer require droptica/droopler:^3.4.0 --with-all-dependencies`.
2. Run `drush updb`.
3. Run `drush cr`.
4. Run `composer require droptica/droopler:^3.5.0 --no-update`.
5. Update the remaining required packages for compatibility with Drupal 11, e.g., `drupal/core-recommended: ^11.0`.
6. Run `composer update`.
7. Run `drush updb`.
8. Run `drush cr`. 

### Drupal 11 compatibility (droopler 3.5.x update)

#### jQuery
Since Drupal 11 is using jQuery 4.x and Droopler is using Bootstrap 4 which needs jQuery 3.x, we need to keep jQuery 3.x compatibility. There is a patch included in the repository to make it work. However, if you can't apply the patch, feel free to patches-ignore in your project's composer.json and apply your own patch.

If you are seeing jQuery errors after updating to droopler 3.5.x, disable drupal js aggregation, clear cache, and enable it again.

#### Features
At the time of writing this, the features module is not compatible with Drupal 11. We are using mglaman/composer-drupal-lenient composer plugin to install it and apply the patch.

Before you update to droopler 3.5.x, please make sure you have added the following to your composer.json:

```json
"config": {
    "allow-plugins": {
        "mglaman/composer-drupal-lenient": true
    }
},
```

and 

```json
"extra": {
    "drupal-lenient": {
        "allowed-list": ["drupal/features"]
    }
}
```

More information about the mglaman/composer-drupal-lenient plugin can be found here: https://www.drupal.org/docs/develop/using-composer/using-the-lenient-composer-plugin

#### SCSS
Droopler was started many years ago with SCSS support. Since then SCSS has evolved and a lot of things have changed, some got deprecated and some got removed. Because version 3.x of droopler is not under active development, we will not update it to the latest version of sass. However, feel free to contribute if you want to have it updated.

If you see in your subtheme warnings about deprecated SCSS functions, please take a look at the package.json file in the droopler_theme directory. You will see that we are using gulp-dart-sass 1.0.2 and older version of sass to avoid warnings. You can do the same in your subtheme to avoid warnings.
