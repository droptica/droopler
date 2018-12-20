<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirm form for clearing an index.
 */
class IndexRebuildTrackerConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);

    $form->setMessenger($container->get('messenger'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to rebuild the tracking data for the search index %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("<p>The complete information about existing and indexed items for this index will be deleted and will have to be rebuilt.</p><p>This should usually not be necessary, but can help if some existing items aren't contained in the index's tracking data for whatever reason (in other words, when the total number of items to be indexed is less than it should be).</p><p>This action cannot be undone.</p>");
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.search_api_index.canonical', ['search_api_index' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getEntity();
    $index->rebuildTracker();
    $this->messenger()
      ->addStatus($this->t('The tracking information for search index %name will be rebuilt.', ['%name' => $index->label()]));
    $form_state->setRedirect('entity.search_api_index.canonical', ['search_api_index' => $index->id()]);
  }

}
