<?php

namespace Drupal\paragraphs;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_reference_revisions\EntityNeedsSaveInterface;

/**
 * Provides an interface defining a paragraphs entity.
 * @ingroup paragraphs
 */
interface ParagraphInterface extends ContentEntityInterface, EntityOwnerInterface, EntityNeedsSaveInterface, EntityPublishedInterface {

  /**
   * Gets the parent entity of the paragraph.
   *
   * Preserves language context with translated entities.
   *
   * @return ContentEntityInterface
   *   The parent entity.
   */
  public function getParentEntity();

  /**
   * Set the parent entity of the paragraph.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $parent
   *   The parent entity.
   * @param string $parent_field_name
   *   The parent field name.
   *
   * @return $this
   */
  public function setParentEntity(ContentEntityInterface $parent, $parent_field_name);

  /**
   * Returns short summary for paragraph.
   *
   * @param array $options
   *   (optional) Array of additional options, with the following elements:
   *   - 'show_behavior_summary': Whether the summary should contain the
   *     behavior settings. Defaults to TRUE to show behavior settings in the
   *     summary.
   *   - 'depth_limit': Depth limit of how many nested paragraph summaries are
   *     allowed. Defaults to 1 to show nested paragraphs only on top level.
   *
   * @return string
   *   The text without tags.
   */
  public function getSummary(array $options = []);

  /**
   * Returns info icons render array for a paragraph.
   *
   * @param array $options
   *   (optional) Array of additional options, with the following elements:
   *   - 'show_behavior_icon': Whether the icons should contain the
   *     behavior settings. Defaults to TRUE to show behavior icons in the
   *     summary.
   *
   * @return array
   *   A list of render arrays that will be rendered as icons.
   */
  public function getIcons(array $options = []);

  /**
   * Returns a flag whether a current revision has been changed.
   *
   * The current instance is being compared with the latest saved revision.
   *
   * @return bool
   *   TRUE in case the current revision changed. Otherwise, FALSE.
   *
   * @see \Drupal\Core\Entity\ContentEntityBase::hasTranslationChanges()
   */
  public function isChanged();

  /**
   * Returns the paragraph type / bundle name as string.
   *
   * @return string
   *   The Paragraph bundle name.
   */
  public function getType();

  /**
   * Returns the paragraph type.
   *
   * @return ParagraphsTypeInterface
   *   The Paragraph Type.
   */
  public function getParagraphType();

  /**
   * Gets all the behavior settings.
   *
   * @return array
   *   The array of behavior settings.
   */
  public function getAllBehaviorSettings();

  /**
   * Gets the behavior setting of an specific plugin.
   *
   * @param string $plugin_id
   *   The plugin ID for which to get the settings.
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will return $values[$key]. If $key is an array, each element
   *   of the array will be used as a nested key. If $key = array('foo', 'bar')
   *   it will return $values['foo']['bar'].
   * @param mixed $default
   *   (optional) The default value if the specified key does not exist.
   *
   * @return mixed
   *   The value for the given key.
   */
  public function &getBehaviorSetting($plugin_id, $key, $default = NULL);

  /**
   * Sets all the behavior settings of a plugin.
   *
   * @param array $settings
   *   The behavior settings from the form.
   */
  public function setAllBehaviorSettings(array $settings);

  /**
   * Sets the behavior settings of a plugin.
   *
   * @param string $plugin_id
   *   The plugin ID for which to set the settings.
   * @param array $settings
   *   The behavior settings from the form.
   */
  public function setBehaviorSettings($plugin_id, array $settings);

}
