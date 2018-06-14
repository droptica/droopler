<?php

namespace Drupal\paragraphs\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

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
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
