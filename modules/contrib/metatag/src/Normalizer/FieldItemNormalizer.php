<?php

namespace Drupal\metatag\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts the Metatag field item object structure to METATAG array structure.
 */
class FieldItemNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\metatag\Plugin\Field\FieldType\MetatagFieldItem';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = $field_item->getValue();

    $normalized['value'] = unserialize($values['value']);

    return $normalized;
  }

}
