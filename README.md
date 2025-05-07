# Droopler CMS 5.x - SEO Friendly CMS & Site Builder

## Repositories and branches

### Version 5


This branch is for 5.1.x version of Droopler.
The newest code for 5.1.x is always on [Drupal.org/project/droopler](https://www.drupal.org/project/droopler).

Default development branch is 5.1.x.

Last releases/tags you can find here https://git.drupalcode.org/project/droopler/-/tags

### Version 3

If you are looking for 3.x code check these branches and repositories:
* https://github.com/droptica/droopler/tree/3.x
* https://github.com/droptica/droopler_project/tree/3.x

## About Droopler CMS 5

Droopler CMS is an SEO-friendly CMS and site builder based on Drupal. It’s a starter kit that, once installed, gives you a ready-to-use website with content types, components (paragraphs), and a sleek frontend theme. It also comes equipped with numerous SEO tools and modules, making it easy to optimize your site for search engines. You can easily customize and extend it just like any other Drupal website.

The maintainer of Droopler CMS is [Droptica](https://www.droptica.com).

## Pre-requisites

To install Droopler you need [DDEV](https://ddev.com) installed on your machine.

## Installation

1. Clone this repository
2. `cd` into the project directory
3. Run the command `./launch-droopler-cms.sh`

### Installation options

After the script finishes you can choose to run the Droopler installation via the browser or the command line.

Type `ddev launch` to open the Droopler installation in your browser.

#### To install Droopler via the command line:

Type `ddev drush site-install droopler` to install clean Droopler.

Type `ddev drush site-install droopler install_configure_form.enable_demo_content=1` if you wish to install demo content.

## Issues

If you encounter any issues, please [create an issue in the Droopler project](http://drupal.org/project/droopler/).


