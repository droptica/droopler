<?php

namespace Drupal\config_update;

use Drupal\Component\Diff\Diff;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Provides methods related to config differences.
 */
class ConfigDiffer implements ConfigDiffInterface {

  use StringTranslationTrait;

  /**
   * List of elements to ignore on top level when comparing config.
   *
   * @var string[]
   *
   * @see ConfigDiffer::format().
   */
  protected $ignore;

  /**
   * Prefix to use to indicate config hierarchy.
   *
   * @var string
   *
   * @see ConfigDiffer::format().
   */
  protected $hierarchyPrefix;

  /**
   * Prefix to use to indicate config values.
   *
   * @var string
   *
   * @see ConfigDiffer::format().
   */
  protected $valuePrefix;

  /**
   * Constructs a ConfigDiffer.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   String translation service.
   * @param string[] $ignore
   *   Config components to ignore at the top level.
   * @param string $hierarchy_prefix
   *   Prefix to use in diffs for array hierarchy.
   * @param string $value_prefix
   *   Prefix to use in diffs for array value.
   */
  public function __construct(TranslationInterface $translation, array $ignore = ['uuid', '_core'], $hierarchy_prefix = '::', $value_prefix = ' : ') {
    $this->stringTranslation = $translation;
    $this->hierarchyPrefix = $hierarchy_prefix;
    $this->valuePrefix = $value_prefix;
    $this->ignore = $ignore;
  }

  /**
   * Normalizes config for comparison.
   *
   * Removes elements in the ignore list from the top level of configuration,
   * and at each level of the array, removes empty arrays and sorts by array
   * key, so that config from different storage can be compared meaningfully.
   *
   * @param array|null $config
   *   Configuration array to normalize.
   *
   * @return array
   *   Normalized configuration array.
   *
   * @see ConfigDiffer::format()
   * @see ConfigDiffer::$ignore
   */
  protected function normalize($config) {
    if (empty($config)) {
      return [];
    }

    // Remove "ignore" elements, only at the top level.
    foreach ($this->ignore as $element) {
      unset($config[$element]);
    }

    // Recursively normalize and return.
    return $this->normalizeArray($config);
  }

  /**
   * Recursively sorts an array by key, and removes empty arrays.
   *
   * @param array $array
   *   An array to normalize.
   *
   * @return array
   *   An array that is sorted by key, at each level of the array, with empty
   *   arrays removed.
   */
  protected function normalizeArray(array $array) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $new = $this->normalizeArray($value);
        if (count($new)) {
          $array[$key] = $new;
        }
        else {
          unset($array[$key]);
        }
      }
    }

    ksort($array);
    return $array;
  }

  /**
   * {@inheritdoc}
   */
  public function same($source, $target) {
    $source = $this->normalize($source);
    $target = $this->normalize($target);
    return $source === $target;
  }

  /**
   * Formats config for showing differences.
   *
   * To compute differences, we need to separate the config into lines and use
   * line-by-line differencer. The obvious way to split into lines is:
   * @code
   * explode("\n", Yaml::encode($config))
   * @endcode
   * But this would highlight meaningless differences due to the often different
   * order of config files, and also loses the indentation and context of the
   * config hierarchy when differences are computed, making the difference
   * difficult to interpret.
   *
   * So, what we do instead is to take the YAML hierarchy and format it so that
   * the hierarchy is shown on each line. So, if you're in element
   * $config['foo']['bar'] and the value is 'value', you will see
   * 'foo::bar : value'.
   *
   * @param array $config
   *   Config array to format. Normalize it first if you want to do diffs.
   * @param string $prefix
   *   (optional) When called recursively, the prefix to put on each line. Omit
   *   when initially calling this function.
   *
   * @return string[]
   *   Array of config lines formatted so that a line-by-line diff will show the
   *   context in each line, and meaningful differences will be computed.
   *
   * @see ConfigDiffer::normalize()
   * @see ConfigDiffer::$hierarchyPrefix
   * @see ConfigDiffer::$valuePrefix
   */
  protected function format(array $config, $prefix = '') {
    $lines = [];

    foreach ($config as $key => $value) {
      $section_prefix = ($prefix) ? $prefix . $this->hierarchyPrefix . $key : $key;

      if (is_array($value)) {
        $lines[] = $section_prefix;
        $newlines = $this->format($value, $section_prefix);
        foreach ($newlines as $line) {
          $lines[] = $line;
        }
      }
      else {
        $lines[] = $section_prefix . $this->valuePrefix . Yaml::encode($value);
      }
    }

    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  public function diff($source, $target) {
    $source = $this->normalize($source);
    $target = $this->normalize($target);

    $source_lines = $this->format($source);
    $target_lines = $this->format($target);

    return new Diff($source_lines, $target_lines);
  }

}
