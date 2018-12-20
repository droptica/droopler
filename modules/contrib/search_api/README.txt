CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Developers
 * Maintainers

INTRODUCTION
------------
This module provides a framework for easily creating searches on any entity
known to Drupal, using any kind of search engine. For site administrators,
it is a great alternative to other search solutions, since it already
incorporates faceting support (with [1]) and the ability to use the Views module
for displaying search results, filters, etc. Also, with the Apache Solr
integration [2], a high-performance search engine is available for this module.

[1] https://www.drupal.org/project/facets
[2] https://www.drupal.org/project/search_api_solr

Developers, on the other hand, will be impressed by the large flexibility and
numerous ways of extension the module provides. Hence, the growing number of
additional contrib modules, providing additional functionality or helping users
customize some aspects of the search process.
  * For a full description of the module, visit the project page:
   https://www.drupal.org/project/search_api
  * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search_api

REQUIREMENTS
------------
No other modules are required.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. For further
information, see:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules

CONFIGURATION
-------------
After installation, for a quick start, just install the "Database Search
Defaults" module provided with this project. This will automatically set up a
search view for node content, using a database server for indexing.

Otherwise, you need to enable at least a module providing integration with a
search backend (like database, Solr, Elasticsearch, …). Possible options are
listed at [3].

Then, go to
  /admin/config/search/search-api
on your site and create a search server and search index. Afterwards, you can
create a view based on your index to enable users to search the content you
configured to be indexed. More details are available online in the handbook [4].
There, you can also find answers to frequently asked questions and common
pitfalls to avoid.

[3] https://www.drupal.org/docs/8/modules/search-api/getting-started/server-backends-and-features
[4] https://www.drupal.org/docs/8/modules/search-api/getting-started

DEVELOPERS
----------

The Search API provides a lot of ways for developers to extend or customize the
framework.

- Hooks
  All available hooks are listed in search_api.api.php.
- Events
  Currently, only the Search API's task system (for reliably executing necessary
  system tasks) makes use of events. Every time a task is executed, an event
  will be fired based on the task's type and the sub-system that scheduled the
  task is responsible for reacting to it. This system is extensible and can
  therefore also easily be used by contrib modules based on the Search API. For
  details, see the description of the \Drupal\search_api\Task\TaskManager class,
  and the other classes in src/Task for examples.
- Plugins
  The Search API defines several plugin types, all listed in its
  search_api.plugin_type.yml file. Here is a list of them, along with the
  directory in which you can find there definition files (interface, plugin base
  and plugin manager):
  - Backends: src/Backend
  - Datasources: src/Datasource
  - Data types: src/DataType
  - Displays: src/Display
  - Parse modes: src/ParseMode
  - Processors: src/Processor
  - Trackers: src/Tracker
  The display plugins are a bit of a special case there, because they aren't
  really "extending" the framework, but are rather a way of telling the Search
  API (and all modules integrating with it) about search pages your module
  defines. They can then be used to provide, for example, faceting support for
  those pages. Therefore, if your module provides any search pages, it's a good
  idea to provide display plugins for them. For an example (for Views pages),
  see \Drupal\search_api\Plugin\search_api\display\ViewsPage.

The handbook documentation for developers is available at [5].

[5] https://www.drupal.org/docs/8/modules/search-api/developer-documentation

To know which parts of the module can be relied upon as its public API, please
read the "Drupal 8 backwards compatibility and internal API policy" [6] and the
module's issue regarding potential module-specific changes to that policy [7].

[6] https://www.drupal.org/core/d8-bc-policy
[7] https://www.drupal.org/node/2871549

SERVER BACKEND FEATURES
-----------------------

Server backend features are a way for other contrib modules to cleanly define
ways in which the Search API can be extended. For more information, see [8].

[8] https://www.drupal.org/docs/8/modules/search-api/getting-started/server-backends-and-features

The Search API module itself currently defines one feature:

- More Like This (search_api_mlt)
  This feature can be used to retrieve a list of search results that are similar
  to a given indexed item. A backend that supports this plugin has to recognize
  the "search_api_mlt" query option. If present, it contains an associative
  array with the following keys:
  - id: The Search API item ID (consisting of the datasource ID and the
    datasource-specific item ID – passing a plain entity ID will NOT work!) of
    the item for which similar results should be found.
  - fields: A simple array of fields which should be used for determining
    similarity. Backends can choose to ignore this field.
  - "field boosts": (optional) An associative array mapping fields to a numeric
    "boost" value that determines how important they should be considered when
    determining similarity. Backends can choose to ignore this field.
  The feature can be used in the UI via the "More like this" Views contextual
  filter.

MAINTAINERS
-----------
Current maintainers:
  * Thomas Seidl (drunken monkey) - https://www.drupal.org/u/drunken-monkey
