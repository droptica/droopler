<?php

/**
 * @file
 * Contains \Drupal\d_update\UpdateChecklist.
 */

namespace Drupal\d_update;

use Drupal\checklistapi\ChecklistapiChecklist;
use Drupal\checklistapi\Storage\StateStorage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\d_update\Entity\Update;

/**
 * Update checklist service.
 *
 * @package Drupal\d_update
 */
class UpdateChecklist {

  use MessengerTrait;

  /**
   * The Checklist API object.
   *
   * @var \Drupal\checklistapi\ChecklistapiChecklist
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
   * @var \Drupal\checklistapi\Storage\StateStorage
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
   */
//  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, AccountInterface $account) {
  public function __construct(StateStorage $stateStorage, ModuleHandlerInterface $module_handler, AccountInterface $account, ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;

    $this->checkListStateStorage = $stateStorage->setChecklistId('d_update');

//    $this->checkListStateStorage = $stateStorage;
    $this->moduleHandler = $module_handler;
    $this->account = $account;

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

    /* @var \Drupal\Core\Config\Config $droopler_update_config */
//    $droopler_update_config = $this->configFactory->getEditable('checklistapi.progress.d_update');
    $currentProgress = $this->getChecklistSavedProgress();
    $user = $this->account->id();
    $time = time();
    foreach ($names as $name) {
//      if ($droopler_update_config && !$droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items.$name")) {
      if (isset($currentProgress['#items'][$name])) {
//        $droopler_update_config
//          ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items.$name", [
//            '#completed' => time(),
//            '#uid' => $user,
//          ]);
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
//    $droopler_update_config
//      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#completed_items', count($droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items")))
//      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#changed', $time)
//      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#changed_by', $user)
//      ->save();
  }

  /**
   * Checks all the bulletpoints on a checklist.
   *
   * @param bool $status
   *   Checkboxes enabled or disabled.
   */
  protected function checkAllListPoints($status = TRUE) {
//    $droopler_update_config = $this->configFactory
//      ->getEditable('checklistapi.progress.d_update');
    $user = $this->account->id();
    $time = time();
    $currentProgress = $this->getChecklistSavedProgress();
//    $droopler_update_config
//      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#changed', $time)
//      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#changed_by', $user);
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
//            $droopler_update_config
//              ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items.$item_name", [
//                '#completed' => $time,
//                '#uid' => $user,
//              ]);
            $currentProgress['#items'][$item_name] = [
              '#completed' => $time,
              '#uid' => $user,
            ];
          }
          else {
            unset($currentProgress['#items'][$item_name]);
//            $droopler_update_config
//              ->clear(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items.$item_name");
          }
        }
      }
    }
    $currentProgress['#completed_items'] = count($currentProgress['#items']);
    $this->setChecklistSavedProgress($currentProgress);
//    $droopler_update_config
//      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#completed_items', count($droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items")))
//      ->save();
  }

  /**
   * Updates and saves progress of the update checklist.
   *
   * @param array $values
   *   Two dimensional array with structure "version_key" => ["checkbox_id" =>
   *   TRUE|FALSE]
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveProgress(array $values) {
    $user = \Drupal::currentUser();

    $time = time();
    $num_changed_items = 0;
    $progress = $this->getChecklistSavedProgress();

    if (empty($progress) ) {
      $progress = [
        '#completed_items' => 0,
        '#items' => [],
      ];
    }
    $progress['#changed'] = $time;
    $progress['#changed_by'] = $user->id();

    $status = [
      'positive' => [],
      'negative' => [],
    ];

//    $droopler_update_config = $this->configFactory->getEditable('checklistapi.progress.d_update');
//    $saved_progress1 = $droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY);


    foreach ($values as $group_key => $group) {
      foreach ($group as $item_key => $item) {
//        $old_item = (!empty($progress['#items'][$item_key])) ? $progress['#items'][$item_key] : 0;
        if (isset($progress['#items'][$item_key])) {
          $num_changed_items++;
          continue;
        }
        if ($item) {
          // Item is checked.
          $status['positive'][] = $item_key;
          $progress['#completed_items']++;
//          if ($old_item) {
//            // Item was previously checked. Use saved value.
//            $new_item = $old_item;
//          }
//          else {
            // Item is newly checked. Set new value.
            $new_item = [
              '#completed' => $time,
              '#uid' => $user->id(),
            ];
            $num_changed_items++;
//          }
          $progress['#items'][$item_key] = $new_item;
        }
        else {
          // Item is unchecked.
          $status['negative'][] = $item_key;
//          if ($old_item) {
//            // Item was previously checked.
//            $num_changed_items++;
//          }
        }
      }
    }

    $this->setSuccessfulByHook($status['positive'], TRUE);
    $this->setSuccessfulByHook($status['negative'], FALSE);

    ksort($progress);

//    $droopler_update_config->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY, $progress)
//      ->save();
    $this->setChecklistSavedProgress($progress);

    $message = \Drupal::translation()->formatPlural(
      $num_changed_items,
      '%title progress has been saved. 1 item changed.',
      '%title progress has been saved. @count items changed.',
      ['%title' => $this->updateChecklist->title],
    );
    $this->messenger()->addStatus($message);
  }

  protected function getChecklistSavedProgress() {
//    return $this->checkListStateStorage->setChecklistId('d_update')->getSavedProgress();
    return $this->checkListStateStorage->getSavedProgress();
  }

  protected function setChecklistSavedProgress($progress) {
//    return $this->checkListStateStorage->setChecklistId('d_update')->getSavedProgress();
////    $this->checkListStateStorage->setChecklistId('d_update')->setSavedProgress($progress);
    $this->checkListStateStorage->setSavedProgress($progress);

  }

  public function migrateConfigProgressToStateProgress() {
    $droopler_update_config = $this->configFactory->getEditable('checklistapi.progress.d_update');
    if ($droopler_update_config && defined(ChecklistapiChecklist::class . '::PROGRESS_CONFIG_KEY')) {
      $oldSavedProgress = $droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY);
      if ($oldSavedProgress) {
        $newSavedProgress = $this->getChecklistSavedProgress();
        if (!empty($newSavedProgress)) {
          $newSavedProgress['#items'] = array_merge($newSavedProgress['#items'] ?? [], $oldSavedProgress['#items'] ?? []);
        } else {
          $newSavedProgress = $oldSavedProgress;
        }
        $this->setChecklistSavedProgress($newSavedProgress);
        $droopler_update_config->clear(ChecklistapiChecklist::PROGRESS_CONFIG_KEY)->save();
      }
    }
  }

}
