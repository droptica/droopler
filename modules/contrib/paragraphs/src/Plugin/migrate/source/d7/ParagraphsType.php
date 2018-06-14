<?php

namespace Drupal\paragraphs\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\paragraphs\Plugin\migrate\source\DrupalSqlBase;

/**
 * Paragraphs Type source plugin.
 *
 * Available configuration keys:
 * - add_description: (bool) (optional) If enabled this will add a default
 *   description to the source data. default:FALSE.
 *
 * @MigrateSource(
 *   id = "d7_paragraphs_type",
 *   source_module = "paragraphs"
 * )
 */
class ParagraphsType extends DrupalSqlBase {

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
    $query = $this->select('paragraphs_bundle', 'pb')
      ->fields('pb');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Paragraph bundles did not have descriptions in d7, optionally add one.
    if ($this->configuration['add_description']) {
      $name = $row->getSourceProperty('name');
      $row->setSourceProperty('description', 'Migrated from paragraph bundle ' . $name);
    }
    else {
      $row->setSourceProperty('description', '');
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'bundle' => $this->t('Paragraph type machine name'),
      'name' => $this->t('Paragraph type label'),
      'description' => $this->t('Paragraph type description'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['bundle']['type'] = 'string';

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
