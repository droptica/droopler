<?php

namespace Drupal\paragraphs_library\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\paragraphs_library\LibraryItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LibraryItemRevisionRevertForm extends ConfirmFormBase {

  /**
   * The library_item revision.
   *
   * @var \Drupal\paragraphs_library\LibraryItemInterface
   */
  protected $revision;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * LibraryItemRevisionRevertForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, TimeInterface $time, Messenger $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'library_item_revision_revert';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getChangedTime())
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $original_revision_timestamp = $this->revision->getChangedTime();
    $this->revision = $this->prepareRevertedRevision($this->revision);
    $this->revision->revision_log = t('Copy of the revision from %date.', [
      '%date' => $this->dateFormatter->format($original_revision_timestamp)
    ]);
    $this->revision->setChangedTime($this->time->getRequestTime());
    $this->revision->save();

    $this->messenger->addMessage($this->t('%title has been reverted to the revision from %revision-date.', [
      '%title' => $this->revision->label(),
      '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
    ]));

    $form_state->setRedirect('entity.paragraphs_library_item.version_history', [
      'paragraphs_library_item' => $this->revision->id()
    ]);
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\paragraphs_library\LibraryItemInterface $library_item_revision
   *   The revision to be reverted.
   *
   * @return \Drupal\paragraphs_library\LibraryItemInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(LibraryItemInterface $library_item_revision) {
    $library_item_revision->setNewRevision();
    $library_item_revision->isDefaultRevision(TRUE);

    return $library_item_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $paragraphs_library_item_revision = NULL) {
    $this->revision = $this->entityTypeManager->getStorage('paragraphs_library_item')
      ->loadRevision($paragraphs_library_item_revision);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.paragraphs_library_item.version_history', [
      'paragraphs_library_item' => $this->revision->id()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
  }
}
