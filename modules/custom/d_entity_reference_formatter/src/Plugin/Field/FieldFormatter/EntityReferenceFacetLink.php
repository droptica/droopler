<?php

declare(strict_types = 1);

namespace Drupal\d_entity_reference_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'entity reference facet link' formatter.
 *
 * @FieldFormatter(
 *   id = "d_entity_reference_facet_link",
 *   label = @Translation("Facet link"),
 *   description = @Translation("Display the label of the referenced entities linked to a faceted search."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceFacetLink extends EntityReferenceFacetFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function buildElement(Url $url, EntityInterface $entity): array {
    return [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $url,
    ];
  }

}
