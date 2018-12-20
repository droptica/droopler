<?php

namespace Drupal\search_api\Form;

use Drupal\search_api\UnsavedConfigurationInterface;

/**
 * Provides a helper methods for forms to correctly treat unsaved configuration.
 */
trait UnsavedConfigurationFormTrait {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Retrieves the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function getRenderer() {
    return $this->renderer;
  }

  /**
   * Retrieves the date formatter.
   *
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   *   The date formatter.
   */
  public function getDateFormatter() {
    return $this->dateFormatter;
  }

  /**
   * Checks whether the given entity contains unsaved changes.
   *
   * If this is the case and the changes were made by a different user, the form
   * is disabled and a message displayed.
   *
   * Optionally, if there are unsaved changes by the current user, a different
   * message can be displayed.
   *
   * @param array $form
   *   The form structure, passed by reference.
   * @param object $entity
   *   The entity in question.
   * @param bool $reportChanged
   *   (optional) If TRUE, also show a message for unsaved changes by the
   *   current user.
   */
  protected function checkEntityEditable(array &$form, $entity, $reportChanged = FALSE) {
    if ($entity instanceof UnsavedConfigurationInterface && $entity->hasChanges()) {
      if ($entity->isLocked()) {
        $form['#disabled'] = TRUE;
        $username = [
          '#theme' => 'username',
          '#account' => $entity->getLockOwner(),
        ];
        $lockMessageSubstitutions = [
          '@user' => $this->renderer->render($username),
          '@age' => $this->dateFormatter->formatTimeDiffSince($entity->getLastUpdated()),
          ':url' => $entity->toUrl('break-lock-form')->toString(),
        ];
        $form['locked'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'index-locked',
              'messages',
              'messages--warning',
            ],
          ],
          '#children' => $this->t('This index is being edited by user @user, and is therefore locked from editing by others. This lock is @age old. Click here to <a href=":url">break this lock</a>.', $lockMessageSubstitutions),
          '#weight' => -10,
        ];
      }
      elseif ($reportChanged) {
        $form['changed'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'index-changed',
              'messages',
              'messages--warning',
            ],
          ],
          '#children' => $this->t('You have unsaved changes.'),
          '#weight' => -10,
        ];
      }
    }
  }

}
