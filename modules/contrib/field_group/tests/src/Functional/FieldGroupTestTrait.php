<?php

namespace Drupal\Tests\field_group\Functional;

use Drupal\Component\Utility\Unicode;

/**
 * Provides common functionality for the FieldGroup test classes.
 */
trait FieldGroupTestTrait {

  /**
   * Create a new group.
   *
   * @param string $entity_type
   *   The entity type as string.
   * @param string $bundle
   *   The bundle of the enity type
   * @param string $context
   *   The context for the group.
   * @param string $mode
   *   The view/form mode.
   * @param array $data
   *   Data for the field group.
   *
   * @return \stdClass
   *   An object that represents the field group.
   */
  protected function createGroup($entity_type, $bundle, $context, $mode, array $data) {

    if (!isset($data['format_settings'])) {
      $data['format_settings'] = array();
    }

    $data['format_settings'] += _field_group_get_default_formatter_settings($data['format_type'], $context);

    $group_name = 'group_' . Unicode::strtolower($this->randomMachineName());

    $field_group = (object) array(
      'group_name' => $group_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'mode' => $mode,
      'context' => $context,
      'children' => isset($data['children']) ? $data['children'] : array(),
      'parent_name' => isset($data['parent']) ? $data['parent'] : '',
      'weight' => isset($data['weight']) ? $data['weight'] : 0,
      'label' => isset($data['label']) ? $data['label'] : $this->randomString(8),
      'format_type' => $data['format_type'],
      'format_settings' => $data['format_settings'],
      'region' => 'content',
    );

    field_group_group_save($field_group);

    return $field_group;
  }

}
