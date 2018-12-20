<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to break the lock of an edited search index.
 */
class IndexBreakLockForm extends EntityConfirmFormBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The shared temporary storage for unsaved search indexes.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an IndexBreakLockForm object.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The factory for shared temporary storages.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer to use.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, MessengerInterface $messenger) {
    $this->tempStore = $temp_store_factory->get('search_api_index');
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $temp_store_factory = $container->get('tempstore.shared');
    $entity_type_manager = $container->get('entity_type.manager');
    $renderer = $container->get('renderer');
    $messenger = $container->get('messenger');

    return new static($temp_store_factory, $entity_type_manager, $renderer, $messenger);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_index_break_lock_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to break the lock on search index %name?', ['%name' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $locked = $this->tempStore->getMetadata($this->entity->id());
    $account = $this->entityTypeManager->getStorage('user')->load($locked->owner);
    $username = [
      '#theme' => 'username',
      '#account' => $account,
    ];
    return $this->t('By breaking this lock, any unsaved changes made by @user will be lost.', ['@user' => $this->renderer->render($username)]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('fields');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Break lock');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->tempStore->getMetadata($this->entity->id())) {
      $form['message']['#markup'] = $this->t('There is no lock on search index %name to break.', ['%name' => $this->entity->id()]);
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->tempStore->delete($this->entity->id());
    $form_state->setRedirectUrl($this->entity->toUrl('fields'));
    $this->messenger->addStatus($this->t('The lock has been broken. You may now edit this search index.'));
  }

}
