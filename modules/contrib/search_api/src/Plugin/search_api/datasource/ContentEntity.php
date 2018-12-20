<?php

namespace Drupal\search_api\Plugin\search_api\datasource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\search_api\Datasource\DatasourcePluginBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a datasource which exposes the content entities.
 *
 * @SearchApiDatasource(
 *   id = "entity",
 *   deriver = "Drupal\search_api\Plugin\search_api\datasource\ContentEntityDeriver"
 * )
 */
class ContentEntity extends DatasourcePluginBase implements EntityDatasourceInterface, PluginFormInterface {

  use PluginFormTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|null
   */
  protected $entityFieldManager;

  /**
   * The entity display repository manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|null
   */
  protected $entityDisplayRepository;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|null
   */
  protected $entityTypeBundleInfo;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface|null
   */
  protected $typedDataManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface|null
   */
  protected $fieldsHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (!empty($configuration['#index']) && $configuration['#index'] instanceof IndexInterface) {
      $this->setIndex($configuration['#index']);
      unset($configuration['#index']);
    }

    // Since defaultConfiguration() depends on the plugin definition, we need to
    // override the constructor and set the definition property before calling
    // that method.
    $this->pluginDefinition = $plugin_definition;
    $this->pluginId = $plugin_id;
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $datasource */
    $datasource = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $datasource->setEntityTypeManager($container->get('entity_type.manager'));
    $datasource->setEntityFieldManager($container->get('entity_field.manager'));
    $datasource->setEntityDisplayRepository($container->get('entity_display.repository'));
    $datasource->setEntityTypeBundleInfo($container->get('entity_type.bundle.info'));
    $datasource->setTypedDataManager($container->get('typed_data_manager'));
    $datasource->setConfigFactory($container->get('config.factory'));
    $datasource->setLanguageManager($container->get('language_manager'));
    $datasource->setFieldsHelper($container->get('search_api.fields_helper'));

    return $datasource;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Retrieves the entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function getEntityStorage() {
    return $this->getEntityTypeManager()->getStorage($this->getEntityTypeId());
  }

  /**
   * Returns the definition of this datasource's entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type definition.
   */
  protected function getEntityType() {
    return $this->getEntityTypeManager()
      ->getDefinition($this->getEntityTypeId());
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The new entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * Retrieves the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  public function getEntityFieldManager() {
    return $this->entityFieldManager ?: \Drupal::service('entity_field.manager');
  }

  /**
   * Sets the entity field manager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The new entity field manager.
   *
   * @return $this
   */
  public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
    return $this;
  }

  /**
   * Retrieves the entity display repository.
   *
   * @return \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   *   The entity entity display repository.
   */
  public function getEntityDisplayRepository() {
    return $this->entityDisplayRepository ?: \Drupal::service('entity_display.repository');
  }

  /**
   * Sets the entity display repository.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The new entity display repository.
   *
   * @return $this
   */
  public function setEntityDisplayRepository(EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityDisplayRepository = $entity_display_repository;
    return $this;
  }

  /**
   * Retrieves the entity display repository.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The entity entity display repository.
   */
  public function getEntityTypeBundleInfo() {
    return $this->entityTypeBundleInfo ?: \Drupal::service('entity_type.bundle.info');
  }

  /**
   * Sets the entity type bundle info.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The new entity type bundle info.
   *
   * @return $this
   */
  public function setEntityTypeBundleInfo(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    return $this;
  }

  /**
   * Retrieves the typed data manager.
   *
   * @return \Drupal\Core\TypedData\TypedDataManagerInterface
   *   The typed data manager.
   */
  public function getTypedDataManager() {
    return $this->typedDataManager ?: \Drupal::typedDataManager();
  }

  /**
   * Sets the typed data manager.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The new typed data manager.
   *
   * @return $this
   */
  public function setTypedDataManager(TypedDataManagerInterface $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
    return $this;
  }

  /**
   * Retrieves the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function getConfigFactory() {
    return $this->configFactory ?: \Drupal::configFactory();
  }

  /**
   * Retrieves the config value for a certain key in the Search API settings.
   *
   * @param string $key
   *   The key whose value should be retrieved.
   *
   * @return mixed
   *   The config value for the given key.
   */
  protected function getConfigValue($key) {
    return $this->getConfigFactory()->get('search_api.settings')->get($key);
  }

  /**
   * Sets the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The new config factory.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * Retrieves the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public function getLanguageManager() {
    return $this->languageManager ?: \Drupal::languageManager();
  }

  /**
   * Sets the language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The new language manager.
   */
  public function setLanguageManager(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Retrieves the fields helper.
   *
   * @return \Drupal\search_api\Utility\FieldsHelperInterface
   *   The fields helper.
   */
  public function getFieldsHelper() {
    return $this->fieldsHelper ?: \Drupal::service('search_api.fields_helper');
  }

  /**
   * Sets the fields helper.
   *
   * @param \Drupal\search_api\Utility\FieldsHelperInterface $fields_helper
   *   The new fields helper.
   *
   * @return $this
   */
  public function setFieldsHelper(FieldsHelperInterface $fields_helper) {
    $this->fieldsHelper = $fields_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $type = $this->getEntityTypeId();
    $properties = $this->getEntityFieldManager()->getBaseFieldDefinitions($type);
    if ($bundles = array_keys($this->getBundles())) {
      foreach ($bundles as $bundle_id) {
        $properties += $this->getEntityFieldManager()->getFieldDefinitions($type, $bundle_id);
      }
    }
    // Exclude properties with custom storage, since we can't extract them
    // currently, due to a shortcoming of Core's Typed Data API. See #2695527.
    // Computed properties should mostly be OK, though, even though they still
    // count as having "custom storage". The "Path" field from the Core module
    // does not work, though, so we explicitly exclude it here to avoid
    // confusion.
    foreach ($properties as $key => $property) {
      if (!$property->isComputed() || $key === 'path') {
        if ($property->getFieldStorageDefinition()->hasCustomStorage()) {
          unset($properties[$key]);
        }
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    $allowed_languages = $this->getLanguages();
    // Always allow items with undefined language. (Can be the case when
    // entities are created programmatically.)
    $allowed_languages[LanguageInterface::LANGCODE_NOT_SPECIFIED] = TRUE;
    $allowed_languages[LanguageInterface::LANGCODE_NOT_APPLICABLE] = TRUE;

    $entity_ids = [];
    foreach ($ids as $item_id) {
      $pos = strrpos($item_id, ':');
      // This can only happen if someone passes an invalid ID, since we always
      // include a language code. Still, no harm in guarding against bad input.
      if ($pos === FALSE) {
        continue;
      }
      $entity_id = substr($item_id, 0, $pos);
      $langcode = substr($item_id, $pos + 1);
      if (isset($allowed_languages[$langcode])) {
        $entity_ids[$entity_id][$item_id] = $langcode;
      }
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $this->getEntityStorage()->loadMultiple(array_keys($entity_ids));
    $items = [];
    $allowed_bundles = $this->getBundles();
    foreach ($entity_ids as $entity_id => $langcodes) {
      if (empty($entities[$entity_id]) || !isset($allowed_bundles[$entities[$entity_id]->bundle()])) {
        continue;
      }
      foreach ($langcodes as $item_id => $langcode) {
        if ($entities[$entity_id]->hasTranslation($langcode)) {
          $items[$item_id] = $entities[$entity_id]->getTranslation($langcode)->getTypedData();
        }
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = [];

    if ($this->hasBundles()) {
      $default_configuration['bundles'] = [
        'default' => TRUE,
        'selected' => [],
      ];
    }

    if ($this->isTranslatable()) {
      $default_configuration['languages'] = [
        'default' => TRUE,
        'selected' => [],
      ];
    }

    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($this->hasBundles() && ($bundles = $this->getEntityBundleOptions())) {
      $form['bundles'] = [
        '#type' => 'details',
        '#title' => $this->t('Bundles'),
        '#open' => TRUE,
      ];
      $form['bundles']['default'] = [
        '#type' => 'radios',
        '#title' => $this->t('Which bundles should be indexed?'),
        '#options' => [
          0 => $this->t('Only those selected'),
          1 => $this->t('All except those selected'),
        ],
        '#default_value' => (int) $this->configuration['bundles']['default'],
      ];
      $form['bundles']['selected'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles'),
        '#options' => $bundles,
        '#default_value' => $this->configuration['bundles']['selected'],
        '#size' => min(4, count($bundles)),
        '#multiple' => TRUE,
      ];
    }

    if ($this->isTranslatable()) {
      $form['languages'] = [
        '#type' => 'details',
        '#title' => $this->t('Languages'),
        '#open' => TRUE,
      ];
      $form['languages']['default'] = [
        '#type' => 'radios',
        '#title' => $this->t('Which languages should be indexed?'),
        '#options' => [
          0 => $this->t('Only those selected'),
          1 => $this->t('All except those selected'),
        ],
        '#default_value' => (int) $this->configuration['languages']['default'],
      ];
      $form['languages']['selected'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Languages'),
        '#options' => $this->getTranslationOptions(),
        '#default_value' => $this->configuration['languages']['selected'],
        '#multiple' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * Retrieves the available bundles of this entity type as an options list.
   *
   * @return array
   *   An associative array of bundle labels, keyed by the bundle name.
   */
  protected function getEntityBundleOptions() {
    $options = [];
    if (($bundles = $this->getEntityBundles())) {
      foreach ($bundles as $bundle => $bundle_info) {
        $options[$bundle] = Utility::escapeHtml($bundle_info['label']);
      }
    }
    return $options;
  }

  /**
   * Retrieves the available languages of this entity type as an options list.
   *
   * @return array
   *   An associative array of language labels, keyed by the language name.
   */
  protected function getTranslationOptions() {
    $options = [];
    foreach ($this->getLanguageManager()->getLanguages() as $language) {
      $options[$language->getId()] = $language->getName();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Filter out empty checkboxes.
    foreach (['bundles', 'languages'] as $key) {
      if ($form_state->hasValue($key)) {
        $parents = [$key, 'selected'];
        $value = $form_state->getValue($parents, []);
        $value = array_keys(array_filter($value));
        $form_state->setValue($parents, $value);
      }
    }

    $this->setConfiguration($form_state->getValues());
  }

  /**
   * Retrieves the entity from a search item.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   An item of this datasource's type.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object represented by that item, or NULL if none could be
   *   found.
   */
  protected function getEntity(ComplexDataInterface $item) {
    $value = $item->getValue();
    return $value instanceof EntityInterface ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    if ($entity = $this->getEntity($item)) {
      $enabled_bundles = $this->getBundles();
      if (isset($enabled_bundles[$entity->bundle()])) {
        return $entity->id() . ':' . $entity->language()->getId();
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemLabel(ComplexDataInterface $item) {
    if ($entity = $this->getEntity($item)) {
      return $entity->label();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemBundle(ComplexDataInterface $item) {
    if ($entity = $this->getEntity($item)) {
      return $entity->bundle();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemUrl(ComplexDataInterface $item) {
    if ($entity = $this->getEntity($item)) {
      if ($entity->hasLinkTemplate('canonical')) {
        return $entity->toUrl('canonical');
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function checkItemAccess(ComplexDataInterface $item, AccountInterface $account = NULL) {
    if ($entity = $this->getEntity($item)) {
      return $this->getEntityTypeManager()
        ->getAccessControlHandler($this->getEntityTypeId())
        ->access($entity, 'view', $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIds($page = NULL) {
    return $this->getPartialItemIds($page);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['entity_type'];
  }

  /**
   * Determines whether the entity type supports bundles.
   *
   * @return bool
   *   TRUE if the entity type supports bundles, FALSE otherwise.
   */
  protected function hasBundles() {
    return $this->getEntityType()->hasKey('bundle');
  }

  /**
   * Determines whether the entity type supports translations.
   *
   * @return bool
   *   TRUE if the entity is translatable, FALSE otherwise.
   */
  protected function isTranslatable() {
    return $this->getEntityType()->isTranslatable();
  }

  /**
   * Retrieves all bundles of this datasource's entity type.
   *
   * @return array
   *   An associative array of bundle infos, keyed by the bundle names.
   */
  protected function getEntityBundles() {
    return $this->hasBundles() ? $this->getEntityTypeBundleInfo()->getBundleInfo($this->getEntityTypeId()) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPartialItemIds($page = NULL, array $bundles = NULL, array $languages = NULL) {
    // These would be pretty pointless calls, but for the sake of completeness
    // we should check for them and return early. (Otherwise makes the rest of
    // the code more complicated.)
    if (($bundles === [] && !$languages) || ($languages === [] && !$bundles)) {
      return NULL;
    }

    $select = $this->getEntityTypeManager()
      ->getStorage($this->getEntityTypeId())
      ->getQuery();

    // When tracking items, we never want access checks.
    $select->accessCheck(FALSE);

    // We want to determine all entities of either one of the given bundles OR
    // one of the given languages. That means we can't just filter for $bundles
    // if $languages is given. Instead, we have to filter for all bundles we
    // might want to include and later sort out those for which we want only the
    // translations in $languages and those (matching $bundles) where we want
    // all (enabled) translations.
    if ($this->hasBundles()) {
      $bundle_property = $this->getEntityType()->getKey('bundle');
      if ($bundles && !$languages) {
        $select->condition($bundle_property, $bundles, 'IN');
      }
      else {
        $enabled_bundles = array_keys($this->getBundles());
        // Since this is also called for removed bundles/languages,
        // $enabled_bundles might not include $bundles.
        if ($bundles) {
          $enabled_bundles = array_unique(array_merge($bundles, $enabled_bundles));
        }
        if (count($enabled_bundles) < count($this->getEntityBundles())) {
          $select->condition($bundle_property, $enabled_bundles, 'IN');
        }
      }
    }

    if (isset($page)) {
      $page_size = $this->getConfigValue('tracking_page_size');
      assert($page_size, 'Tracking page size is not set.');
      $select->range($page * $page_size, $page_size);
      // For paging to reliably work, a sort should be present.
      $entity_id = $this->getEntityType()->getKey('id');
      $select->sort($entity_id);
    }

    $entity_ids = $select->execute();

    if (!$entity_ids) {
      return NULL;
    }

    // For all loaded entities, compute all their item IDs (one for each
    // translation we want to include). For those matching the given bundles (if
    // any), we want to include translations for all enabled languages. For all
    // other entities, we just want to include the translations for the
    // languages passed to the method (if any).
    $item_ids = [];
    $enabled_languages = array_keys($this->getLanguages());
    // As above for bundles, $enabled_languages might not include $languages.
    if ($languages) {
      $enabled_languages = array_unique(array_merge($languages, $enabled_languages));
    }
    // Also, we want to always include entities with unknown language.
    $enabled_languages[] = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $enabled_languages[] = LanguageInterface::LANGCODE_NOT_APPLICABLE;

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($this->getEntityStorage()->loadMultiple($entity_ids) as $entity_id => $entity) {
      $translations = array_keys($entity->getTranslationLanguages());
      $translations = array_intersect($translations, $enabled_languages);
      // If only languages were specified, keep only those translations matching
      // them. If bundles were also specified, keep all (enabled) translations
      // for those entities that match those bundles.
      if ($languages !== NULL
          && (!$bundles || !in_array($entity->bundle(), $bundles))) {
        $translations = array_intersect($translations, $languages);
      }
      foreach ($translations as $langcode) {
        $item_ids[] = "$entity_id:$langcode";
      }
    }

    if (Utility::isRunningInCli()) {
      // When running in the CLI, this might be executed for all entities from
      // within a single process. To avoid running out of memory, reset the
      // static cache after each batch.
      $this->getEntityStorage()->resetCache($entity_ids);
    }

    return $item_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    if (!$this->hasBundles()) {
      // For entity types that have no bundle, return a default pseudo-bundle.
      return [$this->getEntityTypeId() => $this->label()];
    }

    $configuration = $this->getConfiguration();

    // If "default" is TRUE (that is, "All except those selected"),remove all
    // the selected bundles from the available ones to compute the indexed
    // bundles. Otherwise, return all the selected bundles.
    $bundles = [];
    $entity_bundles = $this->getEntityBundles();
    $selected_bundles = array_flip($configuration['bundles']['selected']);
    $function = $configuration['bundles']['default'] ? 'array_diff_key' : 'array_intersect_key';
    $entity_bundles = $function($entity_bundles, $selected_bundles);
    foreach ($entity_bundles as $bundle_id => $bundle_info) {
      $bundles[$bundle_id] = isset($bundle_info['label']) ? $bundle_info['label'] : $bundle_id;
    }
    return $bundles ?: [$this->getEntityTypeId() => $this->label()];
  }

  /**
   * Retrieves the enabled languages.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   All languages that are enabled for this datasource, keyed by language
   *   code.
   */
  protected function getLanguages() {
    $all_languages = $this->getLanguageManager()->getLanguages();

    if ($this->isTranslatable()) {
      $selected_languages = array_flip($this->configuration['languages']['selected']);
      if ($this->configuration['languages']['default']) {
        return array_diff_key($all_languages, $selected_languages);
      }
      else {
        return array_intersect_key($all_languages, $selected_languages);
      }
    }

    return $all_languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes($bundle = NULL) {
    return $this->getEntityDisplayRepository()
      ->getViewModeOptions($this->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function viewItem(ComplexDataInterface $item, $view_mode, $langcode = NULL) {
    try {
      if ($entity = $this->getEntity($item)) {
        $langcode = $langcode ?: $entity->language()->getId();
        return $this->getEntityTypeManager()->getViewBuilder($this->getEntityTypeId())->view($entity, $view_mode, $langcode);
      }
    }
    catch (\Exception $e) {
      // The most common reason for this would be a
      // \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException in
      // getViewBuilder(), because the entity type definition doesn't specify a
      // view_builder class.
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultipleItems(array $items, $view_mode, $langcode = NULL) {
    try {
      $view_builder = $this->getEntityTypeManager()
        ->getViewBuilder($this->getEntityTypeId());
      // Langcode passed, use that for viewing.
      if (isset($langcode)) {
        $entities = [];
        foreach ($items as $i => $item) {
          if ($entity = $this->getEntity($item)) {
            $entities[$i] = $entity;
          }
        }
        if ($entities) {
          return $view_builder->viewMultiple($entities, $view_mode, $langcode);
        }
        return [];
      }
      // Otherwise, separate the items by language, keeping the keys.
      $items_by_language = [];
      foreach ($items as $i => $item) {
        if ($entity = $this->getEntity($item)) {
          $items_by_language[$entity->language()->getId()][$i] = $entity;
        }
      }
      // Then build the items for each language. We initialize $build beforehand
      // and use array_replace() to add to it so the order stays the same.
      $build = array_fill_keys(array_keys($items), []);
      foreach ($items_by_language as $langcode => $language_items) {
        $build = array_replace($build, $view_builder->viewMultiple($language_items, $view_mode, $langcode));
      }
      return $build;
    }
    catch (\Exception $e) {
      // The most common reason for this would be a
      // \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException in
      // getViewBuilder(), because the entity type definition doesn't specify a
      // view_builder class.
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();

    $this->addDependency('module', $this->getEntityType()->getProvider());

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDependencies(array $fields) {
    $dependencies = [];
    $properties = $this->getPropertyDefinitions();

    foreach ($fields as $field_id => $property_path) {
      $dependencies[$field_id] = $this->getPropertyPathDependencies($property_path, $properties);
    }

    return $dependencies;
  }

  /**
   * Computes all dependencies of the given property path.
   *
   * @param string $property_path
   *   The property path of the property.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   The properties which form the basis for the property path.
   *
   * @return string[][]
   *   An associative array with the dependencies for the given property path,
   *   mapping dependency types to arrays of dependency names.
   */
  protected function getPropertyPathDependencies($property_path, array $properties) {
    $dependencies = [];

    list($key, $nested_path) = Utility::splitPropertyPath($property_path, FALSE);
    if (!isset($properties[$key])) {
      return $dependencies;
    }

    $property = $properties[$key];
    if ($property instanceof FieldConfigInterface) {
      $storage = $property->getFieldStorageDefinition();
      if ($storage instanceof FieldStorageConfigInterface) {
        $name = $storage->getConfigDependencyName();
        $dependencies[$storage->getConfigDependencyKey()][$name] = $name;
      }
    }

    // The field might be provided by a module which is not the provider of the
    // entity type, therefore we need to add a dependency on that module.
    if ($property instanceof FieldStorageDefinitionInterface) {
      $provider = $property->getProvider();
      $dependencies['module'][$provider] = $provider;
    }

    $property = $this->getFieldsHelper()->getInnerProperty($property);

    if ($property instanceof EntityDataDefinitionInterface) {
      $entity_type_definition = $this->getEntityTypeManager()
        ->getDefinition($property->getEntityTypeId());
      if ($entity_type_definition) {
        $module = $entity_type_definition->getProvider();
        $dependencies['module'][$module] = $module;
      }
    }

    if (isset($nested_path) && $property instanceof ComplexDataDefinitionInterface) {
      $nested = $this->getFieldsHelper()->getNestedProperties($property);
      $nested_dependencies = $this->getPropertyPathDependencies($nested_path, $nested);
      foreach ($nested_dependencies as $type => $names) {
        $dependencies += [$type => []];
        $dependencies[$type] += $names;
      }
    }

    return array_map('array_values', $dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public static function getIndexesForEntity(ContentEntityInterface $entity) {
    $datasource_id = 'entity:' . $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $has_bundles = $entity->getEntityType()->hasKey('bundle');

    // Needed for PhpStorm. See https://youtrack.jetbrains.com/issue/WI-23395.
    /** @var \Drupal\search_api\IndexInterface[] $indexes */
    $indexes = Index::loadMultiple();

    foreach ($indexes as $index_id => $index) {
      // Filter out indexes that don't contain the datasource in question.
      if (!$index->isValidDatasource($datasource_id)) {
        unset($indexes[$index_id]);
      }
      elseif ($has_bundles) {
        // If the entity type supports bundles, we also have to filter out
        // indexes that exclude the entity's bundle.
        $config = $index->getDatasource($datasource_id)->getConfiguration();
        if (!Utility::matches($entity_bundle, $config['bundles'])) {
          unset($indexes[$index_id]);
        }
      }
    }

    return $indexes;
  }

  /**
   * Filters a set of datasource-specific item IDs.
   *
   * Returns only those item IDs that are valid for the given datasource and
   * index. This method only checks the item language, though – whether an
   * entity with that ID actually exists, or whether it has a bundle included
   * for that datasource, is not verified.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to validate.
   * @param string $datasource_id
   *   The ID of the datasource on the index for which to validate.
   * @param string[] $item_ids
   *   The item IDs to be validated.
   *
   * @return string[]
   *   All given item IDs that are valid for that index and datasource.
   */
  public static function filterValidItemIds(IndexInterface $index, $datasource_id, array $item_ids) {
    if (!$index->isValidDatasource($datasource_id)) {
      return $item_ids;
    }
    $config = $index->getDatasource($datasource_id)->getConfiguration();
    // If the entity type doesn't allow translations, we just accept all IDs.
    // (If the entity type were translatable, the config key would have been set
    // with the default configuration.)
    if (!isset($config['languages']['selected'])) {
      return $item_ids;
    }
    $always_valid = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
    ];
    $valid_ids = [];
    foreach ($item_ids as $item_id) {
      $pos = strrpos($item_id, ':');
      // Item IDs without colons are always invalid.
      if ($pos === FALSE) {
        continue;
      }
      $langcode = substr($item_id, $pos + 1);
      if (Utility::matches($langcode, $config['languages'])
          || in_array($langcode, $always_valid)) {
        $valid_ids[] = $item_id;
      }
    }
    return $valid_ids;
  }

}
