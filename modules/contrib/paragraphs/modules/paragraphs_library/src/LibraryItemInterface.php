<?php

namespace Drupal\paragraphs_library;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a paragraphs entity.
 */
interface LibraryItemInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Creates a library entity from a paragraph entity.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @throws \Exception
   *   If a conversion is attempted for bundles that don't support it.
   *
   * @return \Drupal\paragraphs_library\LibraryItemInterface
   *   The library item entity.
   */
  public static function createFromParagraph(ParagraphInterface $paragraph);

  /**
   * Gets the library item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the library item.
   */
  public function getCreatedTime();

  /**
   * Sets the library item creation timestamp.
   *
   * @param int $timestamp
   *   The library item creation timestamp.
   *
   * @return \Drupal\paragraphs_library\LibraryItemInterface
   *   The called library item entity.
   */
  public function setCreatedTime($timestamp);

}
