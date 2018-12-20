<?php

namespace Drupal\search_api;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Tracker\TrackerInterface;
use Drupal\Core\TempStore\SharedTempStore;

/**
 * Represents a configuration of an index that was not yet permanently saved.
 */
class UnsavedIndexConfiguration implements IndexInterface, UnsavedConfigurationInterface {

  /**
   * The proxied index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The shared temporary storage to use.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * Either the UID of the currently logged-in user, or the session ID.
   *
   * @var int|string
   */
  protected $currentUserId;

  /**
   * The lock information for this configuration.
   *
   * @var object|null
   */
  protected $lock;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UnsavedIndexConfiguration.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to proxy.
   * @param \Drupal\Core\TempStore\SharedTempStore $temp_store
   *   The shared temporary storage to use.
   * @param int|string $current_user_id
   *   Either the UID of the currently logged-in user, or the session ID (for
   *   anonymous users).
   */
  public function __construct(IndexInterface $index, SharedTempStore $temp_store, $current_user_id) {
    $this->entity = $index;
    $this->tempStore = $temp_store;
    $this->currentUserId = $current_user_id;
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
   * {@inheritdoc}
   */
  public function setCurrentUserId($current_user_id) {
    $this->currentUserId = $current_user_id;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanges() {
    return (bool) $this->lock;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    if ($this->lock) {
      return $this->lock->owner != $this->currentUserId;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLockOwner() {
    if (!$this->lock) {
      return NULL;
    }
    $uid = is_numeric($this->lock->owner) ? $this->lock->owner : 0;
    return $this->getEntityTypeManager()->getStorage('user')->load($uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastUpdated() {
    return $this->lock ? $this->lock->updated : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setLockInformation($lock = NULL) {
    $this->lock = $lock;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function savePermanent() {
    // Make sure to overwrite only the index's fields, not just all properties.
    // Unlike the Views UI, we have several separate pages for editing index
    // entities, and only one of them is locked. Therefore, this extra step is
    // necessary, we can't just call $this->entity->save().
    /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $storage */
    $storage = $this->getEntityTypeManager()->getStorage('search_api_index');
    $storage->resetCache([$this->entity->id()]);
    /** @var \Drupal\search_api\IndexInterface $original */
    $original = $storage->loadOverrideFree($this->entity->id());
    $fields = $this->entity->getFields();
    // Set the correct index object on the field objects.
    foreach ($fields as $field) {
      $field->setIndex($original);
    }
    $original->setFields($fields);
    $original->save();
    // Setting the saved entity as the wrapped one is important if methods like
    // isReindexing() are called on the object afterwards.
    $this->entity = $original;
    $this->discardChanges();
  }

  /**
   * {@inheritdoc}
   */
  public function discardChanges() {
    $this->tempStore->delete($this->entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->entity->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    return $this->entity->isReadOnly();
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name, $default = NULL) {
    return $this->entity->getOption($name, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->entity->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $option) {
    $this->entity->setOption($name, $option);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->entity->setOptions($options);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasources() {
    return $this->entity->getDatasources();
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasourceIds() {
    return $this->entity->getDatasourceIds();
  }

  /**
   * {@inheritdoc}
   */
  public function isValidDatasource($datasource_id) {
    return $this->entity->isValidDatasource($datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasource($datasource_id) {
    return $this->entity->getDatasource($datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function addDatasource(DatasourceInterface $datasource) {
    $this->entity->addDatasource($datasource);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeDatasource($datasource_id) {
    $this->entity->removeDatasource($datasource_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDatasources(array $datasources) {
    $this->entity->setDatasources($datasources);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    return $this->entity->getEntityTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidTracker() {
    return $this->entity->hasValidTracker();
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackerId() {
    return $this->entity->getTrackerId();
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackerInstance() {
    return $this->entity->getTrackerInstance();
  }

  /**
   * {@inheritdoc}
   */
  public function setTracker(TrackerInterface $tracker) {
    $this->entity->setTracker($tracker);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidServer() {
    return $this->entity->hasValidServer();
  }

  /**
   * {@inheritdoc}
   */
  public function isServerEnabled() {
    return $this->entity->isServerEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getServerId() {
    return $this->entity->getServerId();
  }

  /**
   * {@inheritdoc}
   */
  public function getServerInstance() {
    return $this->entity->getServerInstance();
  }

  /**
   * {@inheritdoc}
   */
  public function setServer(ServerInterface $server = NULL) {
    $this->entity->setServer($server);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessors() {
    return $this->entity->getProcessors();
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorsByStage($stage, array $overrides = []) {
    return $this->entity->getProcessorsByStage($stage, $overrides);
  }

  /**
   * {@inheritdoc}
   */
  public function isValidProcessor($processor_id) {
    return $this->entity->isValidProcessor($processor_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessor($processor_id) {
    return $this->entity->getProcessor($processor_id);
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(ProcessorInterface $processor) {
    $this->entity->addProcessor($processor);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeProcessor($processor_id) {
    $this->entity->removeProcessor($processor_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessors(array $processors) {
    $this->entity->setProcessors($processors);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    $this->entity->alterIndexedItems($items);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    $this->entity->preprocessIndexItems($items);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    $this->entity->preprocessSearchQuery($query);
  }

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {
    $this->entity->postprocessSearchResults($results);
  }

  /**
   * {@inheritdoc}
   */
  public function addField(FieldInterface $field) {
    $this->entity->addField($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function renameField($old_field_id, $new_field_id) {
    $this->entity->renameField($old_field_id, $new_field_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeField($field_id) {
    $this->entity->removeField($field_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    $this->entity->setFields($fields);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($include_server_defined = FALSE) {
    return $this->entity->getFields($include_server_defined);
  }

  /**
   * {@inheritdoc}
   */
  public function getField($field_id) {
    return $this->entity->getField($field_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsByDatasource($datasource_id) {
    return $this->entity->getFieldsByDatasource($datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFulltextFields() {
    return $this->entity->getFulltextFields();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldRenames() {
    return $this->entity->getFieldRenames();
  }

  /**
   * {@inheritdoc}
   */
  public function discardFieldChanges() {
    $this->entity->discardFieldChanges();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions($datasource_id) {
    return $this->entity->getPropertyDefinitions($datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadItem($item_id) {
    return $this->entity->loadItem($item_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadItemsMultiple(array $item_ids) {
    return $this->entity->loadItemsMultiple($item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems($limit = -1, $datasource_id = NULL) {
    return $this->entity->indexItems($limit, $datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function indexSpecificItems(array $search_objects) {
    return $this->entity->indexSpecificItems($search_objects);
  }

  /**
   * {@inheritdoc}
   */
  public function isBatchTracking() {
    return $this->entity->isBatchTracking();
  }

  /**
   * {@inheritdoc}
   */
  public function startBatchTracking() {
    $this->entity->startBatchTracking();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function stopBatchTracking() {
    $this->entity->stopBatchTracking();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsInserted($datasource_id, array $ids) {
    $this->entity->trackItemsInserted($datasource_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsUpdated($datasource_id, array $ids) {
    $this->entity->trackItemsUpdated($datasource_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsDeleted($datasource_id, array $ids) {
    $this->entity->trackItemsDeleted($datasource_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function reindex() {
    $this->entity->reindex();
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    $this->entity->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildTracker() {
    $this->entity->rebuildTracker();
  }

  /**
   * {@inheritdoc}
   */
  public function isReindexing() {
    return $this->entity->isReindexing();
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $options = []) {
    return $this->entity->query($options);
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->entity->enable();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->entity->disable();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->entity->setStatus($status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncing($status) {
    $this->entity->setSyncing($status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    return $this->entity->status();
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncing() {
    return $this->entity->isSyncing();
  }

  /**
   * {@inheritdoc}
   */
  public function isUninstalling() {
    return $this->entity->isUninstalling();
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    return $this->entity->get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    $this->entity->set($property_name, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->entity->calculateDependencies();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    return $this->entity->onDependencyRemoval($dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return $this->entity->getDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function isInstallable() {
    return $this->entity->isInstallable();
  }

  /**
   * {@inheritdoc}
   */
  public function trustData() {
    $this->entity->trustData();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTrustedData() {
    return $this->entity->hasTrustedData();
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return $this->entity->uuid();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    return $this->entity->language();
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return $this->entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function enforceIsNew($value = TRUE) {
    $this->entity->enforceIsNew($value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entity->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->entity->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'canonical', array $options = []) {
    return $this->entity->toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    return $this->entity->toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', $options = []) {
    return $this->entity->toUrl($rel, $options)->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function link($text = NULL, $rel = 'canonical', array $options = []) {
    return $this->entity->toLink($text, $rel, $options)->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function toLink($text = NULL, $rel = 'canonical', array $options = []) {
    return $this->entity->toLink($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($key) {
    return $this->entity->hasLinkTemplate($key);
  }

  /**
   * {@inheritdoc}
   */
  public function uriRelationships() {
    return $this->entity->uriRelationships();
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id) {
    return Index::load($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(array $ids = NULL) {
    return Index::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    return Index::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if ($this->tempStore->setIfOwner($this->entity->id(), $this->entity)) {
      return SAVED_UPDATED;
    }
    throw new EntityStorageException('Cannot save temporary index configuration: currently being edited by someone else.');
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->entity->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->entity->preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->entity->postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    EntityInterface::preCreate($storage, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    $this->entity->postCreate($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    EntityInterface::preDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    EntityInterface::postDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    EntityInterface::postLoad($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    return new UnsavedIndexConfiguration($this->entity->createDuplicate(), $this->tempStore, $this->currentUserId);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entity->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    return $this->entity->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalId() {
    return $this->entity->getOriginalId();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return $this->entity->getCacheTagsToInvalidate();
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalId($id) {
    $this->entity->setOriginalId($id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->entity->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedData() {
    return $this->entity->getTypedData();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    return $this->entity->getConfigDependencyKey();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyName() {
    return $this->entity->getConfigDependencyName();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigTarget() {
    return $this->entity->getConfigTarget();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $this->entity->access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->entity->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->entity->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->entity->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheContexts(array $cache_contexts) {
    $this->entity->addCacheContexts($cache_contexts);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheTags(array $cache_tags) {
    $this->entity->addCacheTags($cache_tags);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeCacheMaxAge($max_age) {
    $this->entity->mergeCacheMaxAge($max_age);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheableDependency($other_object) {
    $this->entity->addCacheableDependency($other_object);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($module, $key, $value) {
    $this->entity->setThirdPartySetting($module, $key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    return $this->entity->getThirdPartySetting($module, $key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($module) {
    return $this->entity->getThirdPartySettings($module);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($module, $key) {
    return $this->entity->unsetThirdPartySetting($module, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return $this->entity->getThirdPartyProviders();
  }

}
