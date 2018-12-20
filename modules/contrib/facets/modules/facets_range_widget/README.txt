CONTENTS OF THIS FILE
---------------------
 * Installation
 * FAQ

Installation
------------

 * If you want to use the sliders, you need to add the Slider pips jquery
   plugin:
    - create the /libraries/jquery-ui-slider-pips/dist folder.
    - download the following files from
    https://github.com/simeydotme/jQuery-ui-Slider-Pips/tree/v1.11.3/dist
       - jquery-ui-slider-pips.min.js
       - jquery-ui-slider-pips.min.css

   You can find more information about this jquery plugin on
   http://simeydotme.github.io/jQuery-ui-Slider-Pips/

   Alternatively, you can install from Asset Packagist using Composer.
   If you are using the Lightning or Drupal Commerce base distros, just run
   `composer require "bower-asset/jquery-ui-slider-pips:^1.11"
   If you don't have Asset Packagist configured, see
   https://github.com/drupal-composer/drupal-project/issues/278#issuecomment-300714410
   for instructions.


FAQ
---

Q: Why is this in a submodule?
A: We wanted to add a requirements message when the library was not installed,
   to give a good experience when installing the module. We didn't want everyone
   to have to install the library though.
