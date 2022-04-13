# Droopler Commerce Modules
Droopler, starting from version 2.1 comes with Drupal Commerce integration modules.
Their goal is to provide an out-of-the-box Commerce experience.
All the views like Cart, Categories, Products, and Orders are supported by `droopler_theme` by default.

## Setup
First, you need to download two dependant contrib modules:

 * [Commerce](https://www.drupal.org/project/commerce)
 * [Facets Pretty Paths](https://www.drupal.org/project/facets_pretty_paths)

Using composer, run `composer require drupal/commerce drupal/facets_pretty_paths` command.

In the next step, you need to enable `d_commerce` module that comes with Droopler.

Droopler comes with two optional submodules extending Commerce integration:
* `d_commerce_product` - brings Commerce predefined product type with theming applied,
* `d_commerce_products_list` - adds Droopler Products listing page with categories filtering and sorting enabled.

## Configuration
To start adding your products you need to configure your Commerce store.
To do that go to `/store/add/online` URL and set up your store details.

## Products
Having configured Commerce store, you can start adding products - if enabled `d_commerce_product` module
you'll have an additional type of product simply called "Product". Those product types will be displayed on the page `/shop`
provided by the `d_commerce_products_list` module.

## Product and variation titles
The way titles are displayed has been changed with the removal of the `DrooplerProductVariation` class. Product and variation titles are not glued together anymore. Information on how to restore it can be found in [UPDATE.md](../../../../../UPDATE.md).
