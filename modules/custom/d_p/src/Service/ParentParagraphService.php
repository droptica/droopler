<?php

namespace Drupal\d_p\Service;

use Drupal\Core\Entity\ContentEntityInterface;

class ParentParagraphService {

  /**
   * Returns referencing field id (Example: paragraph.d_p_tiles.field_d_media_image)
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $mediaEntity
   *   Media Entity to get parent field info.
   *
   * @return int|string|null
   *   Id of field referencing passed entity.
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
   * Method return referencing field for passed media entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $mediaEntity
   *   Media Entity to get parent field info.
   *
   * @return \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem|null
   *   Field referencing passed media entity.
   */
  protected function getReferencingField(ContentEntityInterface $mediaEntity) {
    if ($mediaEntity && isset($mediaEntity->_referringItem) && method_exists($mediaEntity->_referringItem, 'getParent')) {
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $parentEntity */
      return $mediaEntity->_referringItem->getParent();
    }
    return NULL;
  }

}
