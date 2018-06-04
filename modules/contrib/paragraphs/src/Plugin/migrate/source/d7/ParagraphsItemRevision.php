<?php

namespace Drupal\paragraphs\Plugin\migrate\source\d7;

/**
 * Paragraphs Item Revision source plugin.
 *
 * Available configuration keys:
 * - bundle: (optional) If supplied, this will only return paragraphs
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_paragraphs_item_revision",
 *   source_module = "paragraphs",
 * )
 */
class ParagraphsItemRevision extends ParagraphsItem {

  /**
   * Join string for getting all except the current revisions.
   */
  const JOIN = "p.item_id=pr.item_id AND p.revision_id <> pr.revision_id";

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'revision_id' => [
        'type' => 'integer',
        'alias' => 'pr',
      ],
    ];

    return $ids;
  }

}
