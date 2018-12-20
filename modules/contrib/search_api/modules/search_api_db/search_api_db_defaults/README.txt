Database Search Defaults
------------------------

This module provides a default setup for the Search API, for searching node
content through a view, using a database server for indexing.

By installing this module on your site, the required configuration will be set
up on the site. Other than that, this module has no functionality. You can
(and should, for performance reasons) uninstall it again immediately after
installing, to just get the search set up.

Due to Drupal's configuration model, subsequent updates to the configuration
deployed with this module won't be applied to existing configuration.

The search view will be set up at this path:
  /search/content

You can view (and customize) the installed search configuration under these
paths:
  Server: /admin/config/search/search-api/server/default_server
  Index: /admin/config/search/search-api/index/default_index
  View: /admin/structure/views/view/search_content
    (if the "Views UI" module is installed)
