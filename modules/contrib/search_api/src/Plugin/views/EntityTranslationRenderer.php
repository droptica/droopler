<?php

namespace Drupal\search_api\Plugin\views;

use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Entity\Render\TranslationLanguageRenderer;
use Drupal\views\ResultRow;

/**
 * Renders entity translations in their row language.
 */
class EntityTranslationRenderer extends TranslationLanguageRenderer {

  /**
   * {@inheritdoc}
   */
  public function getLangcode(ResultRow $row) {
    if (!empty($row->search_api_language)) {
      return $row->search_api_language;
    }
    // If our normal query plugin is used, this shouldn't really ever happen,
    // but if it does we fall back to the current request's content language.
    return $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
  }

}
