# Droopler profile for Drupal
<img src="https://droopler-demo.droptica.com/themes/custom/droopler_subtheme/logo.svg" width=300 alt="Droopler Logo" />

## What is Droopler?
Droopler is a Drupal 11 / 12 installation profile designed to build corporate websites. It targets PHP 8.3+ and is based on Bootstrap 4 on the front-end. The maintainer of Droopler is [Droptica](https://www.droptica.com).

[![Build Status](https://github.com/droptica/droopler/workflows/Drupal%20coding%20standards/badge.svg?branch=master)](https://github.com/droptica/droopler/actions)

* **Official website**: [droptica.com/droopler](https://www.droptica.com/droopler)
* **Tutorials**: [droptica.com/droopler/tutorials](https://www.droptica.com/droopler/tutorials/)
* **Demo**: [droopler-demo.droptica.com](https://droopler-demo.droptica.com)
* **Composer template**: [github.com/droptica/droopler_project](https://github.com/droptica/droopler_project)
* **Drupal.org project**: [drupal.org/project/droopler](https://www.drupal.org/project/droopler)

For the latest news please subscribe to our [Facebook](https://www.facebook.com/Droopler/) and [X](https://x.com/DrooplerCMS).

## What's in this repository?
This repository contains a Drupal profile. When you put it in the `/profiles/contrib/droopler` directory, the Drupal installer gets modified and installs base Droopler theme, some module dependencies, and demo content.

## Requirements
* Drupal `^11.3 || ^12`
* PHP `>= 8.3` (CI matrix: 8.3 / 8.4 / 8.5)
* Composer 2.2+

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

## Drupal 11 / 12 + PHP 8.3+ modernization

This branch brings Droopler in line with Drupal 11.3+ / 12 and PHP 8.3+:

* Every legacy procedural hook that Drupal core allows to be class-based has been moved to `#[Hook]` attribute classes under each module's `src/Hook/` namespace. Hooks that Drupal still requires to stay procedural (`hook_install`, `hook_schema`, `hook_update_N`, `hook_requirements`, `template_preprocess_*`, etc.) are left untouched on purpose.
* All source files declare `declare(strict_types=1);` and use constructor property promotion (`protected readonly ...`) for DI. Services are autowired where possible.
* Twitter/X rebranding applied to the bundled social-media configuration and theme (icon + label).
* Static analysis is enforced in CI on every PR: **PHPCS** (Drupal + DrupalPractice + Slevomat) and **PHPStan level 6** (`mglaman/phpstan-drupal`), both running across the PHP 8.3 / 8.4 / 8.5 matrix. No baseline file — the profile is clean on its own.
* `composer.json` declares `composer/installers` + `extra.installer-paths` so that running `composer install` directly inside the profile (e.g. for local phpcs/phpstan work) lands contrib modules at `modules/contrib/`. When Droopler is consumed as a dependency, the root project's installer-paths win — this entry is a no-op for end users.

### Known install caveat: pathauto 1.15+ on Drupal 11.3

Droopler requires `drupal/pathauto: ^1.14`, which under default Composer resolution will still pick up 1.15+ (whatever is the latest tag). That is intentional — runtime is unaffected — but **pathauto 1.15 (released after the OOP-hooks rework) crashes the very first `install_configure_form` submit** on Drupal 11.3.x with:

```
Symfony\Component\DependencyInjection\Exception\RuntimeException:
  You have requested a synthetic service ("kernel"). The DIC does not know how to construct this service.
```

What is happening:

* During `SiteConfigureForm::submitForm()` Drupal calls `User::load(1)`. Loading the user fires `hook_entity_base_field_info`.
* Pathauto 1.15 collects that hook on the OOP class `Drupal\pathauto\Hook\PathautoEntityHooks`, so Drupal asks the container for the class instance.
* The container at this point is still the install-time `ContainerBuilder`. Resolving the constructor graph eventually touches `theme.registry`, whose 9th constructor argument is `@kernel`. `kernel` is registered as a synthetic service but the instance has not been attached to *this* builder yet — boom.
* Pathauto 1.14 keeps `pathauto_entity_base_field_info()` as a procedural function. Procedural hooks are dispatched directly by `ModuleHandler` without going through `ClassResolver`, so the broken chain is never walked during install.

Practical impact:

* `drush site-install droopler ...` fails on the first run on pathauto 1.15+.
* In the UI installer the configure-site step shows the error page, **but refreshing continues the install** — by the time you refresh, the container has been rebuilt with synthetics fully attached and the hook resolves fine. From that point on the site runs normally.

Workarounds during install on pathauto 1.15+:

* **UI installer**: when the error page appears at the configure-site step, refresh the browser. The container has been rebuilt fully by then, so the install continues normally from the same task.
* **Drush installer**: run `drush site-install ... -y` once (it will fail with the synthetic-kernel error), then run it a second time. The second invocation reuses the warmed container and finishes.
* **Pin to 1.14**: if you cannot afford the manual retry (e.g. CI/CD seeding), constrain `drupal/pathauto: "~1.14.0"` in your root `composer.json` (or add a `conflict` on `drupal/pathauto: 1.15.0`). Runtime is identical between 1.14 and 1.15.

This is a one-shot install-time glitch only; once the site is up, both pathauto versions behave the same.

### Upgrading custom code

If you maintain custom modules built on top of Droopler, the breaking changes you are most likely to hit are:

* Constructor signatures of several core Droopler services (`Updater`, `UpdateChecklist`, `ProviderManager`, etc.) gained typed properties and DI via constructor promotion. If you extended them, update the parent call.
* `Updater::writeEntityConfig()` now asserts `ConfigEntityTypeInterface` — pass config-entity storages only.
* Plugin manager `d_media.provider_manager` now wires a cache backend (`d_media_providers`). Custom Provider plugins that relied on the unwired manager get caching for free.
* The `d_p_subscribe_file` module ships a custom `SubscribeFileHtmlMail` mail plugin so download notifications render HTML. Override the `d_p_subscribe_file` mail plugin in your settings if you need a different transport.
