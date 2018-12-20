<?php

namespace Drupal\search_api_test\Plugin\search_api\backend;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_test\TestPluginTrait;

/**
 * Provides a dummy backend for testing purposes.
 *
 * @SearchApiBackend(
 *   id = "search_api_test",
 *   label = @Translation("Test backend"),
 *   description = @Translation("Dummy backend implementation")
 * )
 */
class TestBackend extends BackendPluginBase implements PluginFormInterface {

  use PluginFormTrait;
  use TestPluginTrait {
    checkError as traitCheckError;
  }

  /**
   * {@inheritdoc}
   */
  public function postInsert() {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this);
      return;
    }
    $this->checkError(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preUpdate() {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this);
      return;
    }
    $this->checkError(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postUpdate() {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this);
    }
    $this->checkError(__FUNCTION__);
    return $this->getReturnValue(__FUNCTION__, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    return [
      [
        'label' => 'Dummy Info',
        'info' => 'Dummy Value',
        'status' => 'error',
      ],
      [
        'label' => 'Dummy Info 2',
        'info' => 'Dummy Value 2',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures() {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this);
    }
    return ['search_api_mlt'];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDataType($type) {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this, $type);
    }
    return in_array($type, ['search_api_test', 'search_api_test_altering']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['test' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => 'Test',
      '#default_value' => $this->configuration['test'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this, $index, $items);
    }
    $this->checkError(__FUNCTION__);

    $state = \Drupal::state();
    $key = 'search_api_test.backend.indexed.' . $index->id();
    $indexed_values = $state->get($key, []);
    $skip = $state->get('search_api_test.backend.indexItems.skip', []);
    $skip = array_flip($skip);
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $id => $item) {
      if (isset($skip[$id])) {
        unset($items[$id]);
        continue;
      }
      $indexed_values[$id] = [];
      foreach ($item->getFields() as $field_id => $field) {
        $indexed_values[$id][$field_id] = $field->getValues();
      }
    }
    $state->set($key, $indexed_values);

    return array_keys($items);
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex(IndexInterface $index) {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $index);
      return;
    }
    $this->checkError(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index) {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $index);
      return;
    }
    $this->checkError(__FUNCTION__);
    $index->reindex();
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $index);
      return;
    }
    $this->checkError(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids) {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $index, $item_ids);
      return;
    }
    $this->checkError(__FUNCTION__);

    $state = \Drupal::state();
    $key = 'search_api_test.backend.indexed.' . $index->id();
    $indexed_values = $state->get($key, []);
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($item_ids as $item_id) {
      unset($indexed_values[$item_id]);
    }
    $state->set($key, $indexed_values);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $index, $datasource_id);
      return;
    }
    $this->checkError(__FUNCTION__);

    $key = 'search_api_test.backend.indexed.' . $index->id();
    if (!$datasource_id) {
      \Drupal::state()->delete($key);
      return;
    }

    $indexed = \Drupal::state()->get($key, []);
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach (array_keys($indexed) as $item_id) {
      list($item_datasource_id) = Utility::splitCombinedId($item_id);
      if ($item_datasource_id == $datasource_id) {
        unset($indexed[$item_id]);
      }
    }
    \Drupal::state()->set($key, $indexed);
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $query);
      return;
    }
    $this->checkError(__FUNCTION__);

    $results = $query->getResults();
    $result_items = [];
    $datasources = $query->getIndex()->getDatasources();
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    $datasource = reset($datasources);
    $datasource_id = $datasource->getPluginId();
    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    if ($query->getKeys() && $query->getKeys()[0] == 'test') {
      $item_id = Utility::createCombinedId($datasource_id, '1');
      $item = $fields_helper->createItem($query->getIndex(), $item_id, $datasource);
      $item->setScore(2);
      $item->setExcerpt('test');
      $result_items[$item_id] = $item;
    }
    elseif ($query->getOption('search_api_mlt')) {
      $item_id = Utility::createCombinedId($datasource_id, '2');
      $item = $fields_helper->createItem($query->getIndex(), $item_id, $datasource);
      $item->setScore(2);
      $item->setExcerpt('test test');
      $result_items[$item_id] = $item;
    }
    else {
      $item_id = Utility::createCombinedId($datasource_id, '1');
      $item = $fields_helper->createItem($query->getIndex(), $item_id, $datasource);
      $item->setScore(1);
      $result_items[$item_id] = $item;
      $item_id = Utility::createCombinedId($datasource_id, '2');
      $item = $fields_helper->createItem($query->getIndex(), $item_id, $datasource);
      $item->setScore(1);
      $result_items[$item_id] = $item;
    }
    $results->setResultItems($result_items);
    $results->setResultCount(count($result_items));
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this);
    }
    return $this->getReturnValue(__FUNCTION__, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getDiscouragedProcessors() {
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return (array) call_user_func($override, $this);
    }
    return $this->getReturnValue(__FUNCTION__, []);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return !empty($this->configuration['dependencies']) ? $this->configuration['dependencies'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $remove = $this->getReturnValue(__FUNCTION__, FALSE);
    if ($remove) {
      unset($this->configuration['dependencies']);
    }
    return $remove;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkError($method) {
    $this->traitCheckError($method);
    $this->logMethodCall($method);
  }

}
