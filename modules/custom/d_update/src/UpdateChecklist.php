<?php

declare(strict_types = 1);

namespace Drupal\d_update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\checklistapi\ChecklistapiChecklist;
use Drupal\checklistapi\Storage\StateStorage;
use Drupal\d_update\Entity\Update;

/**
 * Update checklist service.
 */
class UpdateChecklist {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The Checklist API object.
   *
   * @var \Drupal\checklistapi\ChecklistapiChecklist|false
   */
  protected $updateChecklist;

  /**
   * Site configFactory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ChecklistApi storage object.
   *
   * @var \Drupal\checklistapi\Storage\StorageBase
   */
  protected $checkListStateStorage;

  /**
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Module installer service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Update checklist constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\checklistapi\Storage\StateStorage $state_storage
   *   Storage for checklist config.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              ModuleHandlerInterface $module_handler,
                              AccountInterface $account,
                              StateStorage $state_storage) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->account = $account;
    $this->checkListStateStorage = $state_storage->setChecklistId('d_update');

    if ($this->moduleHandler->moduleExists('checklistapi')) {
      $this->updateChecklist = checklistapi_checklist_load('d_update');
    }
    else {
      $this->updateChecklist = FALSE;
    }
  }

  /**
   * Update checklist is available.
   *
   * @return bool
   *   Returns if update checklist is available.
   */
  public function isAvailable() {
    return boolval($this->updateChecklist);
  }

  /**
   * Marks a list of updates as successful.
   *
   * @param array $names
   *   Array of update ids.
   * @param bool $check_list_points
   *   Indicates the corresponding checkbox should be checked.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markUpdatesSuccessful(array $names, $check_list_points = TRUE) {
    if ($this->updateChecklist === FALSE) {
      return;
    }
    $this->setSuccessfulByHook($names, TRUE);
    if ($check_list_points) {
      $this->checkListPoints($names);
    }
  }

  /**
   * Marks a list of updates as failed.
   *
   * @param array $names
   *   Array of update ids.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markUpdatesFailed(array $names) {
    if ($this->updateChecklist === FALSE) {
      return;
    }
    $this->setSuccessfulByHook($names, FALSE);
  }

  /**
   * Marks a list of updates.
   *
   * @param bool $status
   *   Checkboxes enabled or disabled.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markAllUpdates($status = TRUE) {
    if ($this->updateChecklist === FALSE) {
      return;
    }

    $keys = [];
    foreach ($this->updateChecklist->items as $version_items) {
      foreach ($version_items as $key => $item) {
        if (is_array($item)) {
          $keys[] = $key;
        }
      }
    }

    $this->setSuccessfulByHook($keys, $status);
    $this->checkAllListPoints($status);
  }

  /**
   * Set status for update keys.
   *
   * @param array $keys
   *   Keys for update entries.
   * @param bool $status
   *   Status that should be set.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setSuccessfulByHook(array $keys, $status = TRUE) {
    foreach ($keys as $key) {
      if ($update = Update::load($key)) {
        $update->setSuccessfulByHook($status)->save();
      }
      else {
        Update::create(
          [
            'id' => $key,
            'successful_by_hook' => $status,
          ]
        )->save();
      }
    }
  }

  /**
   * Checks an array of bulletpoints on a checklist.
   *
   * @param array $names
   *   Array of the bulletpoints.
   */
  protected function checkListPoints(array $names) {
    $currentProgress = $this->getChecklistSavedProgress();
    $user = $this->account->id();
    $time = time();
    foreach ($names as $name) {
      if (!isset($currentProgress['#items'][$name])) {
        $currentProgress['#items'][$name] = [
          '#completed' => $time,
          '#uid' => $user,
        ];
      }
    }

    $currentProgress['#completed_items'] = count($currentProgress['#items']);
    $currentProgress['#changed'] = $time;
    $currentProgress['#changed_by'] = $user;
    $this->setChecklistSavedProgress($currentProgress);
  }

  /**
   * Checks all the bulletpoints on a checklist.
   *
   * @param bool $status
   *   Checkboxes enabled or disabled.
   */
  protected function checkAllListPoints($status = TRUE) {
    $user = $this->account->id();
    $time = time();
    $currentProgress = $this->getChecklistSavedProgress();
    $currentProgress['#changed'] = $time;
    $currentProgress['#changed_by'] = $user;

    $exclude = [
      '#title',
      '#description',
      '#weight',
    ];
    foreach ($this->updateChecklist->items as $version_items) {
      foreach ($version_items as $item_name => $item) {
        if (!in_array($item_name, $exclude)) {
          if ($status) {
            $currentProgress['#items'][$item_name] = [
              '#completed' => $time,
              '#uid' => $user,
            ];
          }
          else {
            unset($currentProgress['#items'][$item_name]);
          }
        }
      }
    }
    $currentProgress['#completed_items'] = isset($currentProgress['#items']) ? count($currentProgress['#items']) : 0;
    $this->setChecklistSavedProgress($currentProgress);
  }

  /**
   * Updates and saves progress of the update checklist.
   *
   * @param array $values
   *   Two dimensional array with structure "version_key" => ["checkbox_id" =>
   *   TRUE|FALSE].
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveProgress(array $values) {
    $time = time();
    $num_changed_items = 0;
    $progress = $this->getChecklistSavedProgress();

    if (empty($progress)) {
      $progress = [
        '#completed_items' => 0,
        '#items' => [],
      ];
    }
    $progress['#changed'] = $time;
    $progress['#changed_by'] = $this->account->id();

    $status = [
      'positive' => [],
      'negative' => [],
    ];

    foreach ($values as $group) {
      foreach ($group as $item_key => $item) {
        if (isset($progress['#items'][$item_key])) {
          $num_changed_items++;
          continue;
        }
        if ($item) {
          // Item is checked.
          $status['positive'][] = $item_key;
          $num_changed_items++;
          $progress['#completed_items']++;
          $progress['#items'][$item_key] = [
            '#completed' => $time,
            '#uid' => $this->account->id(),
          ];
        }
        else {
          // Item is unchecked.
          $status['negative'][] = $item_key;
        }
      }
    }

    $this->setSuccessfulByHook($status['positive'], TRUE);
    $this->setSuccessfulByHook($status['negative'], FALSE);

    ksort($progress);

    $this->setChecklistSavedProgress($progress);

    $message = $this->formatPlural(
      $num_changed_items,
      '%title progress has been saved. 1 item changed.',
      '%title progress has been saved. @count items changed.',
      ['%title' => $this->updateChecklist->title]
    );
    $this->messenger()->addStatus($message);
  }

  /**
   * Getter for saved progress in checklist storage.
   *
   * @return mixed
   *   The stored value or NULL if no value exists.
   */
  protected function getChecklistSavedProgress() {
    return $this->checkListStateStorage->getSavedProgress();
  }

  /**
   * Setter for saving progress to checklist storage.
   *
   * @param array $progress
   *   An array of checklist progress data.
   */
  protected function setChecklistSavedProgress(array $progress) {
    $this->checkListStateStorage->setSavedProgress($progress);
  }

  /**
   * Function copies checklist.
   *
   * Function copies checklist values stored by old method to a
   * new checklist storage.
   */
  public function migrateConfigProgressToStateProgress() {
    $droopler_update_config = $this->configFactory->getEditable('checklistapi.progress.d_update');
    $config_key = defined(ChecklistapiChecklist::class . '::PROGRESS_CONFIG_KEY') ? ChecklistapiChecklist::PROGRESS_CONFIG_KEY : 'progress';
    $oldSavedProgress = $droopler_update_config->get($config_key);
    if ($oldSavedProgress) {
      $newSavedProgress = $this->getChecklistSavedProgress();
      if (!empty($newSavedProgress)) {
        $newSavedProgress['#items'] = array_merge($newSavedProgress['#items'] ?? [], $oldSavedProgress['#items'] ?? []);
      }
      else {
        $newSavedProgress = $oldSavedProgress;
      }
      $this->setChecklistSavedProgress($newSavedProgress);
      $droopler_update_config->clear($config_key)->save();
    }
  }

}
