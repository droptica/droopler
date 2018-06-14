<?php

namespace Drupal\paragraphs\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;

/**
 * Runs a migration process on a value if a condition is met.
 *
 * The process will run if a source value is equal to an expected value.
 * Otherwise returns the original value unchanged.
 *
 * Configuration Keys:
 *
 * source_value: (required) string. The source property to check against.
 * expected_value: (required) string. The value to check against.  If the
 *   source property described by source_value matches this value, the process
 *   will be executed.
 * process: (required) array.  The process array to execute if the source
 *   property matches the expected value.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_process_on_value"
 * )
 */
class ParagraphsProcessOnValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['source_value'])) {
      throw new \InvalidArgumentException("Required argument 'source_value' not set for paragraphs_process_on_value plugin");
    }
    if (!isset($this->configuration['expected_value'])) {
      throw new \InvalidArgumentException("Required argument 'expected_value' not set for paragraphs_process_on_value plugin");
    }
    if (empty($this->configuration['process']) || !is_array($this->configuration['process'])) {
      throw new \InvalidArgumentException("Required argument 'process' not set or invalid for paragraphs_process_on_value plugin");
    }
    $source_value = $row->getSourceProperty($this->configuration['source_value']);
    if (is_null($source_value)) {
      throw new MigrateSkipRowException('Argument source_value is not valid for ProcessOnValue plugin');
    }
    if ($source_value === $this->configuration['expected_value']) {
      $process = $this->configuration['process'];

      // Append the current working value to the new source we are creating.
      $source = $row->getSource();
      $source['paragraphs_process_on_value_source_field'] = $value;

      // If there is a single process plugin, add the source field.  If there
      // is an array of process plugins, add the source field to the first one.
      if (array_key_exists('plugin', $process)) {
        if (empty($process['source'])) {
          $process['source'] = 'paragraphs_process_on_value_source_field';
        }
      }
      else {
        if (empty($process[0]['source'])) {
          $process[0]['source'] = 'paragraphs_process_on_value_source_field';
        }
      }
      $source = $row->getSource();
      $source['paragraphs_process_on_value_source_field'] = $value;
      $new_row = new Row($source, []);
      $migrate_executable->processRow($new_row, [$destination_property => $process]);
      return $new_row->getDestinationProperty($destination_property);
    }
    else {
      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_value' => '',
      'expected_value' => '',
      'process' => [],
    ] + parent::defaultConfiguration();
  }

}
