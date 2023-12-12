<?php

declare(strict_types = 1);

namespace Drupal\d_p_text_blocks\Entity;

use Drupal\d_p\EnableGridInterface;
use Drupal\d_p\EnablePriceInterface;
use Drupal\d_p\EnablePriceTrait;
use Drupal\d_p\EnableTilesInterface;
use Drupal\d_p\MediaBackgroundInterface;
use Drupal\d_p\MediaBackgroundTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for single text block paragraphs.
 */
class SingleTextBlock extends Paragraph implements MediaBackgroundInterface, EnablePriceInterface {

  use MediaBackgroundTrait;
  use EnablePriceTrait;

  /**
   * Determines if parent entity has enabled tiles.
   *
   * @return bool
   *   TRUE if yes, FALSE otherwise.
   */
  public function hasParentEnabledTiles(): bool {
    $parent = $this->getParentEntity();
    if ($parent instanceof EnableTilesInterface) {
      return $parent->hasEnabledTiles();
    }

    return FALSE;
  }

  /**
   * Determines if parent entity has enabled grid.
   *
   * @return bool
   *   TRUE if yes, FALSE otherwise.
   */
  public function hasParentEnabledGrid(): bool {
    $parent = $this->getParentEntity();
    if ($parent instanceof EnableGridInterface) {
      return $parent->hasEnabledGrid();
    }

    return FALSE;
  }

}
