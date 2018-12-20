<?php

namespace Drupal\search_api\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Defines a data type for fulltext fields.
 *
 * The default Drupal "string" data type doesn't allow us to differentiate
 * between machine names (e.g., content type) and real-language text that we
 * want to index as "Fulltext" by default (e.g., node title and body).
 *
 * Therefore, we define this special data type so Search API processors can use
 * it for their property definitions.
 *
 * @DataType(
 *   id = "search_api_text",
 *   label = @Translation("Text (Search API)")
 * )
 */
class Text extends StringData {}
