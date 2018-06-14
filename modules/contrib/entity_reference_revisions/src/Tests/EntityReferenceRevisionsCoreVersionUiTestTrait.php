<?php

namespace Drupal\entity_reference_revisions\Tests;

/**
 * Provides helper methods for Drupal 8.3.x and 8.4.x versions.
 */
trait EntityReferenceRevisionsCoreVersionUiTestTrait {

  /**
   * An adapter for 8.3 > 8.4 Save (and (un)publish) node button change.
   *
   * @see \Drupal\simpletest\WebTestBase::drupalPostForm
   * @see https://www.drupal.org/node/2847274
   */
  protected function drupalPostNodeForm($path, $edit, $submit, array $options = [], array $headers = [], $form_html_id = NULL, $extra_post = NULL) {
    $drupal_version = (float) substr(\Drupal::VERSION, 0, 3);
    if ($drupal_version > 8.3) {

      switch ($submit) {
        case  t('Save and unpublish'):
          $edit['status[value]'] = FALSE;
          break;

        case t('Save and publish'):
          $edit['status[value]'] = TRUE;
          break;
      }

      $submit = t('Save');
    }
    parent::drupalPostForm($path, $edit, $submit, $options, $headers, $form_html_id, $extra_post);
  }

}
