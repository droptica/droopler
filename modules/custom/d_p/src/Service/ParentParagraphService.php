<?php

namespace Drupal\d_p\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

class ParentParagraphService {

  /**
   * @param $mediaEntity
   *
   * @return int|string|null
   */
  public function getParentParagraphFieldId($mediaEntity) {
    $parentField = $this->getReferencingField($mediaEntity);

    if ($parentField) {
      /** @var \Drupal\field\Entity\FieldConfig $fieldDefinition */
      $fieldDefinition = $parentField->getFieldDefinition();

      return $fieldDefinition->id();
    }

    return NULL;
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $mediaEntity
   *
   * @return EntityReferenceItem|null
   */
  protected function getReferencingField(ContentEntityInterface $mediaEntity) {
    if ($mediaEntity && isset($mediaEntity->_referringItem) && method_exists($mediaEntity->_referringItem, 'getParent')) {
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $parentEntity */
      return $mediaEntity->_referringItem->getParent();
    }
    return NULL;
  }
}