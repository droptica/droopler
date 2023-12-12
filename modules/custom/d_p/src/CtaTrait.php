<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Trait for manage cta field.
 *
 * @see \Drupal\d_p\CtaInterface.
 */
trait CtaTrait {

  /**
   * {@inheritdoc}
   */
  public function getLink(): ?string {
    if (!$this->hasField('field_d_cta_link')) {
      return NULL;
    }

    $link_field = $this->get('field_d_cta_link');

    if ($link_field->isEmpty()) {
      return NULL;
    }

    if ($link = $link_field->first()) {
      /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $link */
      return $link->getUrl()->toString();
    }

    return NULL;
  }

}
