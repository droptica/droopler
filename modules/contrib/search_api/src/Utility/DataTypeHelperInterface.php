<?php

namespace Drupal\search_api\Utility;

use Drupal\search_api\IndexInterface;

/**
 * Provides an interface for implementations of the data type helper service.
 */
interface DataTypeHelperInterface {

  /**
   * Determines whether fields of the given type contain fulltext data.
   *
   * @param string $type
   *   The type to check.
   * @param string[] $textTypes
   *   (optional) An array of types to be considered as text.
   *
   * @return bool
   *   TRUE if $type is one of the specified types, FALSE otherwise.
   */
  public function isTextType($type, array $textTypes = ['text']);

  /**
   * Retrieves the mapping for known data types to Search API's internal types.
   *
   * @return string[]
   *   An array mapping all known (and supported) Drupal data types to their
   *   corresponding Search API data types. Empty values mean that fields of
   *   that type should be ignored by the Search API.
   *
   * @see hook_search_api_field_type_mapping_alter()
   */
  public function getFieldTypeMapping();

  /**
   * Retrieves the necessary type fallbacks for an index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to return the type fallbacks.
   *
   * @return string[]
   *   An array containing the IDs of all custom data types that are not
   *   supported by the index's current server, mapped to their fallback types.
   */
  public function getDataTypeFallbackMapping(IndexInterface $index);

}
