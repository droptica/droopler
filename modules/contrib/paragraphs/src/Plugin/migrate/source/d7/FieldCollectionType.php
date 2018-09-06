<?php

namespace Drupal\paragraphs\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\paragraphs\Plugin\migrate\field\FieldCollection;
use Drupal\paragraphs\Plugin\migrate\source\DrupalSqlBase;

/**
 * Field Collection Type source plugin.
 *
 * Available configuration keys:
 * - add_description: (bool) (optional) If enabled this will add a default
 *   description to the source data. default:FALSE.
 *
 * @MigrateSource(
 *   id = "d7_field_collection_type",
 *   source_module = "field_collection"
 * )
 */
class FieldCollectionType extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'add_description' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config', 'fc')
      ->fields('fc');
    $query->condition('fc.type', 'field_collection');
    $query->condition('fc.active', TRUE);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    $name = $row->getSourceProperty('field_name');

    // Remove field_ prefix for new bundle.
    $bundle = substr($name, FieldCollection::FIELD_COLLECTION_PREFIX_LENGTH);
    $row->setSourceProperty('bundle', $bundle);

    // Field collections don't have descriptions, optionally add one.
    if ($this->configuration['add_description']) {
      $row->setSourceProperty('description', 'Migrated from field_collection ' . $name);
    }
    else {
      $row->setSourceProperty('description', '');
    }

    // Set label from bundle because we don't have a label in D7 field
    // collections.
    $row->setSourceProperty('name', ucfirst(preg_replace('/_/', ' ', $bundle)));

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('Original field collection bundle/field_name'),
      'bundle' => $this->t('Paragraph type machine name'),
      'name' => $this->t('Paragraph type label'),
      'description' => $this->t('Paragraph type description'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['field_name']['type'] = 'string';

    return $ids;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated addDescription configuration key is deprecated in 8.x-1.0 and
   * will be removed prior to 9.x.  Use add_description instead.
   *
   * @see https://www.drupal.org/project/paragraphs/issues/2911242
   */
  public function setConfiguration(array $configuration) {

    // Configuration keys should be snake_case, not CamelCase. Adding a BC
    // layer for addDescription which should be deprecated.
    if (isset($configuration['addDescription'])) {
      @trigger_error("addDescription configuration key is deprecated in 8.x-1.0 and will be removed prior to 9.x.  Use add_description instead. See https://www.drupal.org/project/paragraphs/issues/2911242", E_USER_DEPRECATED);
      if (!isset($configuration['add_description'])) {
        $configuration['add_description'] = $configuration['addDescription'];
      }
      unset($configuration['addDescription']);
    }

    parent::setConfiguration($configuration);
  }

}
