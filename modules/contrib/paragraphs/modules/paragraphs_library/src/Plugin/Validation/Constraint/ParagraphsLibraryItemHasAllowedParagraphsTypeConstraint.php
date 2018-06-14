<?php

namespace Drupal\paragraphs_library\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the library does not bypass Paragraphs type restrictions.
 *
 * A common usecase is to have a "From library" Paragraphs type with an entity
 * reference field to Paragraphs library items.
 * The Paragraphs type of library item that the "From library" Paragraph
 * references must be allowed in the field that holds "From library" Paragraph,
 * if that field has this constraint.
 *
 * @Constraint(
 *   id = "ParagraphsLibraryItemHasAllowedParagraphsType",
 *   label = @Translation("Paragraphs type of Paragraphs library item's root is allowed in the parent field.", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class ParagraphsLibraryItemHasAllowedParagraphsTypeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The @library_item_field_label field cannot contain a @paragraphs_type_label paragraph, because the parent @paragraph_field_label field disallows it.';

}
