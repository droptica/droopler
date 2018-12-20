<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirm form for reindexing an index.
 */
class IndexReindexConfirmForm extends EntityConfirmFormBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an IndexReindexConfirmForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $messenger = $container->get('messenger');

    return new static($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reindex the search index %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Indexed data will remain on the search server until all items have been reindexed. Searches on this index will continue to yield results. This action cannot be undone.');
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

    try {
      $index->reindex();
      $this->messenger->addStatus($this->t('The search index %name was successfully reindexed.', ['%name' => $index->label()]));
    }
    catch (SearchApiException $e) {
      $this->messenger->addError($this->t('Failed to reindex items for the search index %name.', ['%name' => $index->label()]));
      $message = '%type while trying to reindex items on index %name: @message in %function (line %line of %file)';
      $variables = [
        '%name' => $index->label(),
      ];
      $variables += Error::decodeException($e);
      $this->getLogger('search_api')->error($message, $variables);
    }

    $form_state->setRedirect('entity.search_api_index.canonical', ['search_api_index' => $index->id()]);
  }

}
