<?php

namespace Drupal\paragraphs_library\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs_library\LibraryItemInterface;
use Drupal\user\UserInterface;

/**
 * Defines the LibraryItem entity.
 *
 * @ContentEntityType(
 *   id = "paragraphs_library_item",
 *   label = @Translation("Paragraphs library item"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\paragraphs_library\LibraryItemAccessControlHandler",
 *     "views_data" = "Drupal\paragraphs_library\LibraryItemViewsData",
 *     "form" = {
 *       "default" = "Drupal\paragraphs_library\Form\LibraryItemForm",
 *       "add" = "Drupal\paragraphs_library\Form\LibraryItemForm",
 *       "edit" = "Drupal\paragraphs_library\Form\LibraryItemForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\paragraphs_library\Routing\LibraryItemRouteProvider",
 *     },
 *   },
 *   base_table = "paragraphs_library_item",
 *   data_table = "paragraphs_library_item_field_data",
 *   revision_table = "paragraphs_library_item_revision",
 *   revision_data_table = "paragraphs_library_item_revision_field_data",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer paragraphs library",
 *   render_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_created" = "revision_created",
 *     "revision_user" = "revision_uid",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/paragraphs/add/default",
 *     "edit-form" = "/admin/content/paragraphs/{paragraphs_library_item}/edit",
 *     "delete-form" = "/admin/content/paragraphs/{paragraphs_library_item}/delete",
 *     "collection" = "/admin/content/paragraphs",
 *     "canonical" = "/admin/content/paragraphs/{paragraphs_library_item}",
 *     "revision" = "/admin/content/paragraphs/{paragraphs_library_item}/revisions/{paragraphs_library_item_revision}/view",
 *     "revision-revert" = "/admin/content/paragraphs/{paragraphs_library_item}/revisions/{paragraphs_library_item_revision}/revert",
 *     "revision-delete" = "/admin/content/paragraphs/{paragraphs_library_item}/revisions/{paragraphs_library_item_revision}/delete"
 *   },
 *   field_ui_base_route = "paragraphs_library_item.settings",
 * )
 */
class LibraryItem extends EditorialContentEntityBase implements LibraryItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['paragraphs'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Paragraphs'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'paragraph')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_revisions_entity_view',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'paragraphs',
        'weight' => 0,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the library item was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'region' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the library item was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the library item author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\paragraphs_library\Entity\LibraryItem::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'region' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // If no revision author has been set explicitly, make the entity owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      $record->revision_log = $this->original->revision_log->value;
    }

    // @todo Remove when https://www.drupal.org/project/drupal/issues/2869056 is
    // fixed.
    $new_revision = $this->isNewRevision();
    if (!$new_revision && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing library item without adding a new
      // revision, we need to make sure $entity->revision_log is reset whenever
      // it is empty. Therefore, this code allows us to avoid clobbering an
      // existing log entry with an empty one.
      $record->revision_log = $this->original->getRevisionLogMessage();
    }

    if ($new_revision && (!isset($record->revision_created) || empty($record->revision_created))) {
      $record->revision_created = \Drupal::time()->getRequestTime();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromParagraph(ParagraphInterface $paragraph) {
    if (!$paragraph->getParagraphType()->getThirdPartySetting('paragraphs_library', 'allow_library_conversion', FALSE)) {
      throw new \Exception(sprintf('%s cannot be converted to library item per configuration', $paragraph->bundle()));
    }

    // Ensure that we work with the default language as the active one.
    $paragraph = $paragraph->getUntranslated();

    // Make a copy of the paragraph. Use the Replicate module, if it is enabled.
    if (\Drupal::hasService('replicate.replicator')) {
      $duplicate_paragraph = \Drupal::getContainer()->get('replicate.replicator')->replicateEntity($paragraph);
    }
    else {
      $duplicate_paragraph = $paragraph->createDuplicate();
    }
    $duplicate_paragraph->save();

    $label = static::buildLabel($paragraph);
    $library_item = static::create([
      'label' => $label,
      'paragraphs' => $duplicate_paragraph,
    ]);

    // Add a translation for each translation the paragraph has, the only
    // translatable element is the label. The referenced paragraph keeps the
    // existing translations.
    foreach ($duplicate_paragraph->getTranslationLanguages(FALSE) as $langcode => $language) {
      $library_item->addTranslation($langcode, [
        'label' => static::buildLabel($duplicate_paragraph->getTranslation($langcode))
      ] + $library_item->toArray());
    }

    return $library_item;
  }

  /**
   * Builds a label for the library item.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph for which the label should be generated.
   *
   * @return string
   */
  protected static function buildLabel(ParagraphInterface $paragraph) {
    $summary = $paragraph->getSummary(['show_behavior_summary' => FALSE]);
    $summary = Unicode::truncate($summary, 50);
    return $paragraph->getParagraphType()->label() . ': ' . $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    if ($rel === 'revision-revert' || $rel === 'revision-delete') {
      $uri_route_parameters['paragraphs_library_item_revision'] = $this->getRevisionId();
    }
    return $uri_route_parameters;
  }
}
