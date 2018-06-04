<?php

namespace Drupal\entity_reference_revisions\Normalizer;

use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;

/**
 * Defines a class for normalizing EntityReferenceRevisionItems.
 */
class EntityReferenceRevisionItemNormalizer extends EntityReferenceItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceRevisionsItem::class;

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $value = parent::constructValue($data, $context);
    if ($value) {
      $value['target_revision_id'] = $data['target_revision_id'];
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = array()) {
    $data = parent::normalize($field_item, $format, $context);
    $field_name = $field_item->getParent()->getName();
    $entity = $field_item->getEntity();
    $field_uri = $this->linkManager->getRelationUri($entity->getEntityTypeId(), $entity->bundle(), $field_name, $context);
    $data['_embedded'][$field_uri][0]['target_revision_id'] = $field_item->target_revision_id;
    return $data;
  }

}
