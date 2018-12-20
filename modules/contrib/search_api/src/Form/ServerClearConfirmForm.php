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
 * Defines a confirm form for clearing a server.
 */
class ServerClearConfirmForm extends EntityConfirmFormBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a ServerClearConfirmForm object.
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
    return $this->t('Are you sure you want to clear all indexed data from the search server %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("This will permanently remove all data currently indexed on this server for indexes that aren't read-only. Items are queued for reindexing. Until reindexing occurs, searches for the affected indexes will not return any results. This action cannot be undone.");
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.search_api_server.canonical', ['search_api_server' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->getEntity();

    try {
      $server->deleteAllItems();
      $this->messenger->addStatus($this->t('All indexed data was successfully deleted from the server.'));
    }
    catch (SearchApiException $e) {
      $this->messenger->addError($this->t('Indexed data could not be cleared for some indexes. Check the logs for details.'));
    }

    $failed_reindexing = [];
    $properties = [
      'status' => TRUE,
      'read_only' => FALSE,
    ];
    foreach ($server->getIndexes($properties) as $index) {
      try {
        $index->reindex();
      }
      catch (SearchApiException $e) {
        $message = '%type while clearing index %index: @message in %function (line %line of %file).';
        $variables = [
          '%index' => $index->label(),
        ];
        $variables += Error::decodeException($e);
        $this->getLogger('search_api')->error($message, $variables);

        $failed_reindexing[] = $index->label();
      }
    }

    if ($failed_reindexing) {
      $args = [
        '@indexes' => implode(', ', $failed_reindexing),
      ];
      $this->messenger->addWarning($this->t('Failed to mark the following indexes for reindexing: @indexes. Check the logs for details.', $args));
    }

    $form_state->setRedirect('entity.search_api_server.canonical', ['search_api_server' => $server->id()]);
  }

}
