<?php

namespace Drupal\paragraphs\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides a form for Paragraphs type deletion.
 */
class ParagraphsTypeDeleteConfirm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_paragraphs = $this->entityTypeManager->getStorage('paragraph')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_paragraphs) {
      $caption = '<p>' . $this->formatPlural($num_paragraphs, '%type Paragraphs type is used by 1 piece of content on your site. You can not remove this %type Paragraphs type until you have removed all from the content.', '%type Paragraphs type is used by @count pieces of content on your site. You may not remove %type Paragraphs type until you have removed all from the content.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];

      // Optional to delete existing entities.
      $form['delete_entities'] = [
        '#type' => 'submit',
        '#submit' => [[$this, 'deleteExistingEntities']],
        '#value' => $this->formatPlural($num_paragraphs, 'Delete existing Paragraph', 'Delete all @count existing Paragraphs'),
      ];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submit callback to delete paragraphs.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deleteExistingEntities(array $form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $ids = $storage->getQuery()
      ->condition('type', $this->entity->id())
      ->execute();

    if (!empty($ids)) {
      $paragraphs = Paragraph::loadMultiple($ids);

      // Delete existing entities.
      $storage->delete($paragraphs);
      drupal_set_message($this->formatPlural(count($paragraphs), 'Entity is successfully deleted.', 'All @count entities are successfully deleted.'));
    }

    // Set form to rebuild.
    $form_state->setRebuild();
  }

}
