<?php

namespace Drupal\paragraphs_library\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the library does not bypass Paragraphs type restrictions.
 *
 * If a field disallows certain Paragraphs types, this validates that those
 * restrictions are not being circumvented by putting a Paragraphs library item
 * of a disallowed Paragraphs types into a child field.
 *
 */
class ParagraphsLibraryItemHasAllowedParagraphsTypeConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ValidReferenceConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The selection plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->selectionManager = $selection_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$value instanceof EntityReferenceFieldItemListInterface) {
      throw new UnexpectedTypeException($value, EntityReferenceFieldItemListInterface::class);
    }

    $handler = $this->selectionManager->getSelectionHandler($value->getFieldDefinition());
    if (!$handler instanceof ParagraphSelection) {
      return;
    }

    $allowed_paragraphs_types = array_keys($handler->getSortedAllowedTypes());

    // Look for Paragraphs with library item fields in the restricted field.
    foreach ($value->referencedEntities() as $paragraph) {
      if (!$paragraph instanceof ParagraphInterface) {
        continue;
      }

      // Look for library item fields.
      foreach ($paragraph->getFieldDefinitions() as $field_name => $field_definition) {
        if ($field_definition->getType() !== 'entity_reference') {
          continue;
        }

        if ($field_definition->getSetting('target_type') !== 'paragraphs_library_item') {
          continue;
        }

        /** @var \Drupal\paragraphs_library\LibraryItemInterface $library_item_entity */
        foreach ($paragraph->get($field_name) as $entity_reference_item) {
          // Get the Paragraphs type of the library item.
          $library_item_entity = $entity_reference_item->entity;
          $used_paragraphs_type = $library_item_entity->get('paragraphs')->entity->getType();

          // Check if the Paragraphs type of the item is not allowed in the
          // field holding the parent Paragraph.
          if (in_array($used_paragraphs_type, $allowed_paragraphs_types)) {
            continue;
          }

          $paragraphs_type_entity = $this->entityTypeManager->getStorage('paragraphs_type')->load($used_paragraphs_type);

          $this->context->addViolation($constraint->message, [
            '@library_item_field_label' => $field_definition->getLabel(),
            '@paragraphs_type_label' => $paragraphs_type_entity->label(),
            '@paragraph_field_label' => $value->getFieldDefinition()->getLabel(),
          ]);

        }
      }
    }
  }

  /**
   * Returns paragraph type of the library item.
   *
   * @param string $entity_reference_item
   *  Entity reference item.
   *
   * @return string
   *   Paragraphs type.
   */
  protected function getParagraphTypeOfLibraryItem($entity_reference_item) {
    $library_item_entity = $entity_reference_item->entity;
    return $library_item_entity->get('paragraphs')->entity->getType();
  }

}
