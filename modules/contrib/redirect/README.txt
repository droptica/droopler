CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Redirect module provides a unified redirection API (also replaces
path_redirect and globalredirect).


 * For a full description of the module visit:
   https://www.drupal.org/project/redirect

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/redirect


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Redirect module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Search and Metadata > URL
       redirects for configuration.
    3. Select "Add redirect" and in the "Path" field add the old path.
    4. In the "To" field, start typing the title of a piece of content to select
       it. You can also enter an internal path such as /node/add or an external
       URL such as http://example.com. Enter <front> to link to the front page.
    5. Select the Redirect status: 300 Multiple Choices, 301 Moved Permanently,
       302 Found, 303 See Other, 304 Not Modified, 305 Use Proxy, or 307
       Temporary Redirect. Save.
    6. Once a redirect has been added, it will be listed in the URL Redirects
       vertical tab group on the content's edit page.


MAINTAINERS
-----------

Supporting organization for 8.x-1.x port:

 * MD Systems - https://www.drupal.org/md-systems
