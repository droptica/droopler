<?php

namespace Drupal\search_api\Utility;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Markup;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\search_api\data_type\value\TextToken;

/**
 * Contains utility methods for the Search API.
 */
class Utility {

  /**
   * Creates a single text token.
   *
   * @param string $value
   *   The word or other token value.
   * @param float $score
   *   (optional) The token's score.
   *
   * @return \Drupal\search_api\Plugin\search_api\data_type\value\TextTokenInterface
   *   A text token object.
   */
  public static function createTextToken($value, $score = 1.0) {
    return new TextToken($value, (float) $score);
  }

  /**
   * Returns a deep copy of the input array.
   *
   * The behavior of PHP regarding arrays with references pointing to it is
   * rather weird. Therefore, this method should be used when making a copy of
   * such an array, or of an array containing references.
   *
   * This method will also omit empty array elements (that is, elements that
   * evaluate to FALSE according to PHP's native rules).
   *
   * @param array $array
   *   The array to copy.
   *
   * @return array
   *   A deep copy of the array.
   */
  public static function deepCopy(array $array) {
    $copy = [];
    foreach ($array as $k => $v) {
      if (is_array($v)) {
        if ($v = static::deepCopy($v)) {
          $copy[$k] = $v;
        }
      }
      elseif (is_object($v)) {
        $copy[$k] = clone $v;
      }
      elseif ($v) {
        $copy[$k] = $v;
      }
    }
    return $copy;
  }

  /**
   * Creates a combined ID from a raw ID and an optional datasource prefix.
   *
   * This can be used to created an internal item ID from a datasource ID and a
   * datasource-specific raw item ID, or a combined property path from a
   * datasource ID and a property path to identify properties index-wide.
   *
   * @param string|null $datasource_id
   *   The ID of the datasource to which the item belongs. Or NULL to return the
   *   raw ID unchanged (option included for compatibility purposes).
   * @param string $raw_id
   *   The datasource-specific raw item ID of the item (or property).
   *
   * @return string
   *   The combined ID, with the datasource prefix separated by
   *   \Drupal\search_api\IndexInterface::DATASOURCE_ID_SEPARATOR.
   */
  public static function createCombinedId($datasource_id, $raw_id) {
    if (!isset($datasource_id)) {
      return $raw_id;
    }
    return $datasource_id . IndexInterface::DATASOURCE_ID_SEPARATOR . $raw_id;
  }

  /**
   * Splits an internal ID into its two parts.
   *
   * Both internal item IDs and combined property paths are prefixed with the
   * corresponding datasource ID. This method will split these IDs up again into
   * their two parts.
   *
   * @param string $combined_id
   *   The internal ID, with an optional datasource prefix separated with
   *   \Drupal\search_api\IndexInterface::DATASOURCE_ID_SEPARATOR from the
   *   raw item ID or property path.
   *
   * @return array
   *   A numeric array, containing the datasource ID in element 0 and the raw
   *   item ID or property path in element 1. In the case of
   *   datasource-independent properties (that is, when there is no prefix),
   *   element 0 will be NULL.
   */
  public static function splitCombinedId($combined_id) {
    if (strpos($combined_id, IndexInterface::DATASOURCE_ID_SEPARATOR) !== FALSE) {
      return explode(IndexInterface::DATASOURCE_ID_SEPARATOR, $combined_id, 2);
    }
    return [NULL, $combined_id];
  }

  /**
   * Splits a property path into two parts along a path separator (:).
   *
   * The path is split into one part with a single property name, and one part
   * with the complete rest of the property path (which might be empty).
   * Depending on $separate_last the returned single property key will be the
   * first (FALSE) or last (TRUE) property of the path.
   *
   * @param string $property_path
   *   The property path to split.
   * @param bool $separate_last
   *   (optional) If FALSE, separate the first property of the path. By default,
   *   the last property is separated from the rest.
   * @param string $separator
   *   (optional) The separator to use.
   *
   * @return string[]
   *   An array with indexes 0 and 1, 0 containing the first part of the
   *   property path and 1 the second. If $separate_last is FALSE, index 0 will
   *   always contain a single property name (without any colons) and index 1
   *   might be NULL. If $separate_last is TRUE it's the exact other way round.
   */
  public static function splitPropertyPath($property_path, $separate_last = TRUE, $separator = ':') {
    $function = $separate_last ? 'strrpos' : 'strpos';
    $pos = $function($property_path, $separator);
    if ($pos !== FALSE) {
      return [
        substr($property_path, 0, $pos),
        substr($property_path, $pos + 1),
      ];
    }

    if ($separate_last) {
      return [NULL, $property_path];
    }
    return [$property_path, NULL];
  }

  /**
   * Retrieves all overridden property values for the given config entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The config entity to check for overrides.
   *
   * @return array
   *   An associative array mapping property names to their overridden values.
   */
  public static function getConfigOverrides(EntityInterface $entity) {
    $entity_type = $entity->getEntityType();
    if (!($entity_type instanceof ConfigEntityTypeInterface)) {
      return [];
    }

    $config_key = $entity_type->getConfigPrefix() . '.' . $entity->id();
    $config = \Drupal::config($config_key);
    if (!$config->hasOverrides()) {
      return [];
    }

    return static::collectOverrides($config, $config->get());
  }

  /**
   * Collects overrides from a config object.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config object.
   * @param array $values
   *   The array of values for the given $prefix.
   * @param array $overrides
   *   (optional) The overrides collected so far. Internal use only.
   * @param string $prefix
   *   (optional) The config key prefix for the current call level.  Internal
   *   use only.
   *
   * @return array
   *   An associative array mapping property names to their overridden values.
   */
  protected static function collectOverrides(Config $config, array $values, array $overrides = [], $prefix = '') {
    foreach ($values as $key => $value) {
      $key = "$prefix$key";
      if (!$config->hasOverrides($key)) {
        continue;
      }
      if (is_array($value)) {
        NestedArray::setValue($overrides, explode('.', $key), []);
        $overrides = static::collectOverrides($config, $value, $overrides, "$key.");
      }
      else {
        NestedArray::setValue($overrides, explode('.', $key), $value);
      }
    }

    return $overrides;
  }

  /**
   * Determines whether this PHP process is running on the command line.
   *
   * @return bool
   *   TRUE if this PHP process is running via CLI, FALSE otherwise.
   */
  public static function isRunningInCli() {
    return php_sapi_name() === 'cli';
  }

  /**
   * Checks whether a certain value matches the configuration.
   *
   * This unifies checking for matches with the common configuration pattern of
   * having one "All except those selected"/"Only the selected" option
   * ("default") and a list of options to select.
   *
   * @param mixed $value
   *   The value to check.
   * @param array $settings
   *   The settings to check against, as an associative array with the following
   *   keys:
   *   - default: Boolean defining the default for not-selected items. TRUE
   *     means "All except those selected", FALSE means "Only the selected".
   *     Defaults to TRUE.
   *   - selected: A numerically indexed array of the selected options. Defaults
   *     to an empty array.
   *
   * @return bool
   *   TRUE if the value matches according to the configuration, FALSE
   *   otherwise.
   */
  public static function matches($value, array $settings) {
    $settings += [
      'default' => TRUE,
      'selected' => [],
    ];
    return in_array($value, $settings['selected']) != $settings['default'];
  }

  /**
   * Escapes HTML special characters in plain text, if necessary.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $text
   *   The text to escape.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   If a markup object was passed as $text, it is returned as-is. Otherwise,
   *   the text is escaped and returned
   */
  public static function escapeHtml($text) {
    if ($text instanceof MarkupInterface) {
      return $text;
    }

    return Markup::create(Html::escape((string) $text));
  }

}
