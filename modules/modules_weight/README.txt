CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Sometimes we need to modify modules execution order, and some people could write
a code that execute the query to modify the weight of a module in the system 
table, some one might go straight to his favorite SQL client and modify the 
record directly. This module provides an interface to reorder the modules
weight.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/modules_weight

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/modules_weight


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

 * Drush Help (https://www.drupal.org/project/drush_help):
   Improves the module help page showing information about the module drush
   commands.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.


CONFIGURATION
-------------

 * Configure the module settings in Administration » Configuration » System »
   Modules Weight » Settings:

   - You can choose if you can to show or not the system modules. For this you
     need the 'Administer Modules Weight' permission.

 * Set the modules weight in Administration » Configuration » System »
   Modules Weight:

   - You can select the weight for all the installed and compatible modules
     according to the module settings. For this you need the
     'Administer Modules Weight' permission.

 * Drush commands

   - mw-show-system-modules

     Configures if we can reorder the core modules.

   - mw-reorder

     Configures the modules weight.

   - mw-list

     Shows the modules weight list.


MAINTAINERS
-----------

Current maintainers:
 * Adrian Cid Almaguer (adriancid) - https://www.drupal.org/u/adriancid
 * Ma'moun Othman (artofeclipse) - https://www.drupal.org/u/artofeclipse
