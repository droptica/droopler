# Droopler profile for Drupal 8 #
<img src="https://droopler-demo.droptica.com/themes/custom/droopler_subtheme/logo.svg" width=300 alt="Droopler Logo" />

## What is Droopler? ##
Droopler is a Drupal 8 profile designed to build corporate websites. It's based on the latest frontend technologies, including Bootstrap 4. The maintainer of Droopler is [Droptica](https://www.droptica.com).

* **Official website**: [droptica.com/droopler](https://www.droptica.com/droopler)
* **Documentation**: [droptica.com/droopler/for-developers](https://www.droptica.com/droopler/for-developers/)
* **Demo**: [droopler-demo.droptica.com](https://droopler-demo.droptica.com)
* **Composer template**: [github.com/droptica/droopler_project](https://github.com/droptica/droopler_project)
* **Drupal.org project**: [drupal.org/project/droopler](https://www.drupal.org/project/droopler)

For the latest news please subscribe to our [Facebook](https://www.facebook.com/Droopler/) and [Twitter](https://twitter.com/DrooplerCMS).

## What's in this repository? ##
This repository contains a Drupal profile. When you put it in your /profiles directory, the Drupal installer gets modified and installs base Droopler theme, some module dependencies, and demo content.

## Installation ##
The Droopler profile should be installed via Composer. We recommend using [Droopler skeleton repository](https://github.com/droptica/droopler_project). If you are starting from the scratch - in the **require** section of your composer.json put:

```json
"require": {
  "droptica/droopler": "^8.2.0"
}
```

And run **composer update**.

In case of unexpected problems please update your main composer.json to comply with the [Droopler skeleton repository](https://github.com/droptica/droopler_project). You may run into some issues with libraries and their directories.

## Commerce ##
Droopler, starting from version 2.1 comes with Drupal Commerce integration modules.

### Setup ###
Firstly you need to download two dependant contrib modules:

 * [Commerce](https://www.drupal.org/project/commerce)
 * [Facets Pretty Paths](https://www.drupal.org/project/facets_pretty_paths)

Using composer run `composer require drupal/commerce drupal/facets_pretty_paths` command.

In the next step, you need to enable `d_commerce` module that comes with Droopler.

Droopler comes with two optional submodules extending Commerce integration:
* `d_commerce_product` - brings Commerce predefined product type with theming applied,
* `d_commerce_products_list` - adds Droopler Products listing page with categories filtering and sorting enabled.

### Configuration
In order to start adding your products you need to configure your Commerce store.
To do that go to `/store/add/online` URL and set up your store details.

### Products
Having configured Commerce store you can start adding products - if enabled `d_commerce_product` module
you'll have an additional type of product simply called "Product". Those product types will be displayed on the page `/shop`
provided by the `d_commerce_products_list` module.
