<?php

namespace Drupal\paragraphs\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\ChangedFieldItemList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\entity_reference_revisions\EntityNeedsSaveInterface;
use Drupal\entity_reference_revisions\EntityNeedsSaveTrait;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Paragraph entity.
 *
 * @ingroup paragraphs
 *
 * @ContentEntityType(
 *   id = "paragraph",
 *   label = @Translation("Paragraph"),
 *   bundle_label = @Translation("Paragraph type"),
 *   handlers = {
 *     "view_builder" = "Drupal\paragraphs\ParagraphViewBuilder",
 *     "access" = "Drupal\paragraphs\ParagraphAccessControlHandler",
 *     "storage_schema" = "Drupal\paragraphs\ParagraphStorageSchema",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "paragraphs_item",
 *   data_table = "paragraphs_item_field_data",
 *   revision_table = "paragraphs_item_revision",
 *   revision_data_table = "paragraphs_item_revision_field_data",
 *   translatable = TRUE,
 *   entity_revision_parent_type_field = "parent_type",
 *   entity_revision_parent_id_field = "parent_id",
 *   entity_revision_parent_field_name_field = "parent_field_name",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "revision" = "revision_id",
 *     "published" = "status"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *   },
 *   bundle_entity_type = "paragraphs_type",
 *   field_ui_base_route = "entity.paragraphs_type.edit_form",
 *   common_reference_revisions_target = TRUE,
 *   content_translation_ui_skip = TRUE,
 *   render_cache = FALSE,
 *   default_reference_revision_settings = {
 *     "field_storage_config" = {
 *       "cardinality" = -1,
 *       "settings" = {
 *         "target_type" = "paragraph"
 *       }
 *     },
 *     "field_config" = {
 *       "settings" = {
 *         "handler" = "default:paragraph"
 *       }
 *     },
 *     "entity_form_display" = {
 *       "type" = "entity_reference_paragraphs"
 *     },
 *     "entity_view_display" = {
 *       "type" = "entity_reference_revisions_entity_view"
 *     }
 *   }
 * )
 */
class Paragraph extends ContentEntityBase implements ParagraphInterface {

  use EntityNeedsSaveTrait;
  use EntityPublishedTrait;

  /**
   * The behavior plugin data for the paragraph entity.
   *
   * @var array
   */
  protected $unserializedBehaviorSettings;

  /**
   * Number of summaries.
   *
   * @var int
   */
  protected $summaryCount;

  /**
   * {@inheritdoc}
   */
  public function getParentEntity() {
    if (!isset($this->get('parent_type')->value) || !isset($this->get('parent_id')->value)) {
      return NULL;
    }

    $parent = \Drupal::entityTypeManager()->getStorage($this->get('parent_type')->value)->load($this->get('parent_id')->value);

    // Return current translation of parent entity, if it exists.
    if ($parent != NULL && ($parent instanceof TranslatableInterface) && $parent->hasTranslation($this->language()->getId())) {
      return $parent->getTranslation($this->language()->getId());
    }

    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntity(ContentEntityInterface $parent, $parent_field_name) {
    $this->set('parent_type', $parent->getEntityTypeId());
    $this->set('parent_id', $parent->id());
    $this->set('parent_field_name', $parent_field_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = '';
    if ($parent = $this->getParentEntity()) {
      $parent_field = $this->get('parent_field_name')->value;
      $values = $parent->{$parent_field};
      foreach ($values as $key => $value) {
        if ($value->entity->id() == $this->id()) {
          $label = $parent->label() . ' > ' . $value->getFieldDefinition()->getLabel();
        } else {
          // A previous or draft revision or a deleted stale Paragraph.
          $label = $parent->label() . ' > ' . $value->getFieldDefinition()->getLabel() . ' (previous revision)';
        }
      }
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }
    // If no revision author has been set explicitly, make the node owner the
    // revision author.
    if (!$this->getRevisionAuthor()) {
      $this->setRevisionAuthorId($this->getOwnerId());
    }

    // If behavior settings are not set then get them from the entity.
    if ($this->unserializedBehaviorSettings !== NULL) {
      $this->set('behavior_settings', serialize($this->unserializedBehaviorSettings));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAllBehaviorSettings() {
    if ($this->unserializedBehaviorSettings === NULL) {
      $this->unserializedBehaviorSettings = unserialize($this->get('behavior_settings')->value);
    }
    if (!is_array($this->unserializedBehaviorSettings)) {
      $this->unserializedBehaviorSettings = [];
    }
    return $this->unserializedBehaviorSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function &getBehaviorSetting($plugin_id, $key, $default = NULL) {
    $settings = $this->getAllBehaviorSettings();
    $exists = NULL;
    $value = &NestedArray::getValue($settings, array_merge((array) $plugin_id, (array) $key), $exists);
    if (!$exists) {
      $value = $default;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllBehaviorSettings(array $settings) {
    // Set behavior settings fields.
    $this->unserializedBehaviorSettings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setBehaviorSettings($plugin_id, array $settings) {
    // Set behavior settings fields.
    $this->unserializedBehaviorSettings[$plugin_id] = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->setNeedsSave(FALSE);
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);
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
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getParagraphType() {
    return $this->type->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($revision_log) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The paragraphs entity language code.'))
      ->setRevisionable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the paragraphs author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\paragraphs\Entity\Paragraph::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'region' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the Paragraph was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'region' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    $fields['parent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent ID'))
      ->setDescription(t('The ID of the parent entity of which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE);

    $fields['parent_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent type'))
      ->setDescription(t('The entity parent type to which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['parent_field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent field name'))
      ->setDescription(t('The entity parent field name to which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['behavior_settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Behavior settings'))
      ->setDescription(t('The behavior plugin settings'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(serialize([]));

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
 public function createDuplicate() {
   $duplicate = parent::createDuplicate();
   // Loop over entity fields and duplicate nested paragraphs.
   foreach ($duplicate->getFields() as $field) {
     if ($field->getFieldDefinition()->getType() == 'entity_reference_revisions') {
       if ($field->getFieldDefinition()->getTargetEntityTypeId() == "paragraph") {
         foreach ($field as $item) {
           $item->entity = $item->entity->createDuplicate();
         }
       }
     }
   }
   return $duplicate;
 }

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $options = []) {
    $show_behavior_summary = isset($options['show_behavior_summary']) ? $options['show_behavior_summary'] : TRUE;
    $depth_limit = isset($options['depth_limit']) ? $options['depth_limit'] : 1;
    $this->summaryCount = 0;
    $summary = [];
    $components = entity_get_form_display('paragraph', $this->getType(), 'default')->getComponents();
    uasort($components, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    foreach (array_keys($components) as $field_name) {
      // Components can be extra fields, check if the field really exists.
      if (!$this->hasField($field_name)) {
        continue;
      }
      $field_definition = $this->getFieldDefinition($field_name);
      // We do not add content to the summary from base fields, skip them
      // keeps performance while building the paragraph summary.
      if (!($field_definition instanceof FieldConfigInterface) || !$this->get($field_name)->access('view')) {
        continue;
      }

      if ($field_definition->getType() == 'image' || $field_definition->getType() == 'file') {
        $file_summary = $this->getFileSummary($field_name);
        if ($file_summary != '') {
          $summary[] = $file_summary;
        }
      }

      $text_summary = $this->getTextSummary($field_name, $field_definition);
      if ($text_summary != '') {
        $summary[] = $text_summary;
      }

      if ($field_definition->getType() == 'entity_reference_revisions') {
        // Decrease the depth, since we are entering a nested paragraph.
        $nested_summary = $this->getNestedSummary($field_name, [
          'show_behavior_summary' => $show_behavior_summary,
          'depth_limit' => $depth_limit - 1
        ]);
        if ($nested_summary != '') {
          $summary[] = $nested_summary;
        }
      }

      if ($field_type = $field_definition->getType() == 'entity_reference') {
        if ($this->get($field_name)->entity && $this->get($field_name)->entity->access('view label')) {
          $summary[] = $this->get($field_name)->entity->label();
        }
      }

      // Add the Block admin label referenced by block_field.
      if ($field_definition->getType() == 'block_field') {
        if (!empty($this->get($field_name)->first())) {
          $block_admin_label = $this->get($field_name)->first()->getBlock()->getPluginDefinition()['admin_label'];
          $summary[] = $block_admin_label;
        }
      }

      if ($field_definition->getType() == 'link') {
        if (!empty($this->get($field_name)->first())) {
          // If title is not set, fallback to the uri.
          if ($title = $this->get($field_name)->title) {
            $summary[] = $title;
          }
          else {
            $summary[] = $this->get($field_name)->uri;
          }
        }
      }
    }

    if ($show_behavior_summary) {
      $paragraphs_type = $this->getParagraphType();
      foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin) {
        if ($plugin_summary = $plugin->settingsSummary($this)) {
          $summary = array_merge($summary, $plugin_summary);
        }
      }
    }

    $collapsed_summary_text = implode(', ', $summary);
    return strip_tags($collapsed_summary_text);
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons(array $options = []) {
    $show_behavior_info = isset($options['show_behavior_icon']) ? $options['show_behavior_icon'] : TRUE;
    $icons = [];

    // For now we depend here on the fact that summaryCount is already
    // initialized. That means that getSummary() should be called before
    // getIcons().
    // @todo - should we fix this dependency somehow?
    if ($this->summaryCount) {
      $icons['count'] = [
        '#markup' => $this->summaryCount,
        '#prefix' => '<span class="paragraphs-badge" title="' . (string) \Drupal::translation()->formatPlural($this->summaryCount, '1 child', '@count children') . '">',
        '#suffix' => '</span>',
      ];
    }

    if ($show_behavior_info) {
      $paragraphs_type = $this->getParagraphType();
      foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin) {
        if ($plugin_info = $plugin->settingsIcon($this)) {
          $icons = array_merge($icons, $plugin_info);
        }
      }
    }

    return $icons;
  }

  /**
   * Returns an array of field names to skip in ::isChanged.
   *
   * @return array
   *   An array of field names.
   */
  protected function getFieldsToSkipFromChangedCheck() {
    // A list of revision fields which should be skipped from the comparision.
    $fields = [
      $this->getEntityType()->getKey('revision'),
      'revision_uid'
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isChanged() {
    if ($this->isNew()) {
      return TRUE;
    }

    // $this->original only exists during save. If it exists we re-use it here
    // for performance reasons.
    /** @var \Drupal\paragraphs\ParagraphInterface $original */
    $original = $this->original ?: NULL;
    if (!$original) {
      $original = $this->entityTypeManager()->getStorage($this->getEntityTypeId())->loadRevision($this->getLoadedRevisionId());
    }

    // If the current revision has just been added, we have a change.
    if ($original->isNewRevision()) {
      return TRUE;
    }

    // The list of fields to skip from the comparision.
    $skip_fields = $this->getFieldsToSkipFromChangedCheck();

    // Compare field item current values with the original ones to determine
    // whether we have changes. We skip also computed fields as comparing them
    // with their original values might not be possible or be meaningless.
    foreach ($this->getFieldDefinitions() as $field_name => $definition) {
      if (in_array($field_name, $skip_fields, TRUE)) {
        continue;
      }
      $field = $this->get($field_name);
      // When saving entities in the user interface, the changed timestamp is
      // automatically incremented by ContentEntityForm::submitForm() even if
      // nothing was actually changed. Thus, the changed time needs to be
      // ignored when determining whether there are any actual changes in the
      // entity.
      if (!($field instanceof ChangedFieldItemList) && !$definition->isComputed()) {
        $items = $field->filterEmptyItems();
        $original_items = $original->get($field_name)->filterEmptyItems();
        if (!$items->equals($original_items)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Returns summary for file paragraph.
   *
   * @param string $field_name
   *   Field name from field definition.
   *
   * @return string
   *   Summary for image.
   */
  protected function getFileSummary($field_name) {
    $summary = '';
    if ($this->get($field_name)->entity) {
      foreach ($this->get($field_name) as $file_key => $file_value) {

        $text = '';
        if ($file_value->description != '') {
          $text = $file_value->description;
        }
        elseif ($file_value->title != '') {
          $text = $file_value->title;
        }
        elseif ($file_value->alt != '') {
          $text = $file_value->alt;
        }
        elseif ($file_value->entity->getFileName()) {
          $text = $file_value->entity->getFileName();
        }

        if (strlen($text) > 150) {
          $text = Unicode::truncate($text, 150);
        }

        $summary = $text;
      }
    }

    return trim($summary);
  }

  /**
   * Returns summary for nested paragraphs.
   *
   * @param string $field_name
   *   Field definition id for paragraph.
   * @param array $options
   *   (optional) An associative array of additional options.
   *   See \Drupal\paragraphs\ParagraphInterface::getSummary() for all of the
   *   available options.
   *
   * @return string
   *   Short summary for nested Paragraphs type
   *   or NULL if the summary is empty.
   */
  protected function getNestedSummary($field_name, array $options) {
    $summary = [];
    if ($options['depth_limit'] >= 0) {
      foreach ($this->get($field_name) as $item) {
        $entity = $item->entity;
        if ($entity instanceof ParagraphInterface) {
          $summary[] = $entity->getSummary($options);
          $this->summaryCount++;
        }
      }
    }

    $summary = array_filter($summary);

    if (empty($summary)) {
      return NULL;
    }

    $paragraph_summary = implode(', ', $summary);
    return $paragraph_summary;
  }

  /**
   * Returns summary for all text type paragraph.
   *
   * @param string $field_name
   *   Field definition id for paragraph.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition for paragraph.
   *
   * @return string
   *   Short summary for text paragraph.
   */
  public function getTextSummary($field_name, FieldDefinitionInterface $field_definition) {
    $text_types = [
      'text_with_summary',
      'text',
      'text_long',
      'list_string',
      'string',
    ];

    $excluded_text_types = [
      'parent_id',
      'parent_type',
      'parent_field_name',
    ];

    $summary = '';
    if (in_array($field_definition->getType(), $text_types)) {
      if (in_array($field_name, $excluded_text_types)) {
        return $summary;
      }

      $text = $this->get($field_name)->value;
      if (strlen($text) > 150) {
        $text = Unicode::truncate($text, 150);
      }

      $summary = $text;
    }

    return trim($summary);
  }

}
