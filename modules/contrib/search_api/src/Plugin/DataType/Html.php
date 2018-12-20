<?php

namespace Drupal\search_api\Plugin\DataType;

/**
 * Defines a data type for fulltext fields containing valid HTML.
 *
 * This data type can be used in addition to "search_api_text" by processors
 * that define properties which always already contain valid HTML (to avoid
 * double-escaping where possible).
 *
 * @DataType(
 *   id = "search_api_html",
 *   label = @Translation("HTML text (Search API)")
 * )
 */
class Html extends Text {}
