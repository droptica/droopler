<?php

namespace Drupal\paragraphs\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Paragraphs Item source plugin.
 *
 * Available configuration keys:
 * - bundle: (optional) If supplied, this will only return paragraphs
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_paragraphs_item",
 *   source_module = "paragraphs",
 * )
 */
class ParagraphsItem extends FieldableEntity {

  /**
   * Join string for getting current revisions.
   */
  const JOIN = "p.revision_id = pr.revision_id";

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'bundle' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('paragraphs_item', 'p')
      ->fields('p',
        ['item_id',
          'bundle',
          'field_name',
          'archived',
        ])
      ->fields('pr', ['revision_id']);
    $query->innerJoin('paragraphs_item_revision', 'pr', static::JOIN);

    // This configuration item may be set by a deriver to restrict the
    // bundles retrieved.
    if ($this->configuration['bundle']) {
      $query->condition('p.bundle', $this->configuration['bundle']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Get Field API field values.
    $item_id = $row->getSourceProperty('item_id');
    $revision_id = $row->getSourceProperty('revision_id');

    foreach (array_keys($this->getFields('paragraphs_item', $row->getSourceProperty('bundle'))) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('paragraphs_item', $field, $item_id, $revision_id));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('The paragraph_item id'),
      'revision_id' => $this->t('The paragraph_item revision id'),
      'bundle' => $this->t('The paragraph bundle'),
      'field_name' => $this - t('The paragrpah field_name'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'item_id' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];

    return $ids;
  }

}
