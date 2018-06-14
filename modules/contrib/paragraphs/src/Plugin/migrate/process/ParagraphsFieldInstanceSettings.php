<?php

namespace Drupal\paragraphs\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Configure field instance settings for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_field_instance_settings"
 * )
 */
class ParagraphsFieldInstanceSettings extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $type = $row->getSourceProperty('type');

    if ($type == 'paragraphs') {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
      $target_bundles = [];

      if (!empty($value['allowed_bundles'])) {

        $target_bundles = array_filter($value['allowed_bundles'], function ($a) {
          return $a != -1;
        });
        $value['handler_settings']['negate'] = 0;
        if (empty($target_bundles)) {
          $value['handler_settings']['target_bundles'] = NULL;
        }
        else {
          $value['handler_settings']['target_bundles'] = $target_bundles;
        }
        unset($value['allowed_bundles']);
      }

      if (!empty($value['bundle_weights'])) {

        // Copy the existing weights, and add any new bundles (either from
        // a field collection migration happening now, or pre-existing on the
        // site at the bottom.
        foreach ($value['bundle_weights'] as $bundle_name => $weight) {
          $value['handler_settings']['target_bundles_drag_drop'][$bundle_name] = [
            'enabled' => array_key_exists($bundle_name, $target_bundles),
            'weight' => $weight,
          ];
        }
        $other_bundles = array_keys(array_diff_key($bundles, $value['bundle_weights']));
        $weight = max($value['bundle_weights']);
        foreach ($other_bundles as $bundle_name) {
          $value['handler_settings']['target_bundles_drag_drop'][$bundle_name] = [
            'enabled' => array_key_exists($bundle_name, $target_bundles),
            'weight' => ++$weight,
          ];
        }
        unset($value['bundle_weights']);
      }
    }
    return $value;
  }

}
