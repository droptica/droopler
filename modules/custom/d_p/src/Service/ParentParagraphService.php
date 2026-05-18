<?php

declare(strict_types=1);

namespace Drupal\d_p\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Resolves the referencing field of an entity (e.g. media inside a paragraph).
 */
class ParentParagraphService {

  /**
   * Get the field config id that references `$mediaEntity` (if any).
   *
   * Example return: `paragraph.d_p_tiles.field_d_media_image`.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $mediaEntity
   *   Entity whose parent field reference we look up.
   *
   * @return string|null
   *   Field config id, or NULL when no referencing field is available.
   */
  public function getParentParagraphFieldId(ContentEntityInterface $mediaEntity): ?string {
    $parentField = $this->getReferencingField($mediaEntity);
    if ($parentField === NULL) {
      return NULL;
    }

    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $parentField->getFieldDefinition();
    return (string) $field_definition->id();
  }

  /**
   * Resolve the entity reference field item that points to `$mediaEntity`.
   *
   * @return \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem|null
   *   Referencing field item or NULL when not available.
   */
  protected function getReferencingField(ContentEntityInterface $mediaEntity): ?EntityReferenceItem {
    if (!isset($mediaEntity->_referringItem)) {
      return NULL;
    }
    if (!method_exists($mediaEntity->_referringItem, 'getParent')) {
      return NULL;
    }

    $parent = $mediaEntity->_referringItem->getParent();
    return $parent instanceof EntityReferenceItem ? $parent : NULL;
  }

}
