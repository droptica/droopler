<?php

/**
 * @file
 * Contains \Drupal\d_update\UpdateChecklist.
 */

namespace Drupal\d_update;

use Drupal\checklistapi\ChecklistapiChecklist;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\d_update\Entity\Update;

/**
 * Update checklist service.
 *
 * @package Drupal\d_update
 */
class UpdateChecklist {

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
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, AccountInterface $account) {
    $this->configFactory = $config_factory;
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
    $droopler_update_config = $this->configFactory->getEditable('checklistapi.progress.d_update');
    $user = $this->account->id();
    $time = time();
    foreach ($names as $name) {
      if ($droopler_update_config && !$droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items.$name")) {
        $droopler_update_config
          ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items.$name", [
            '#completed' => time(),
            '#uid' => $user,
          ]);
      }
    }
    $droopler_update_config
      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#completed_items', count($droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items")))
      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#changed', $time)
      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#changed_by', $user)
      ->save();
  }

  /**
   * Checks all the bulletpoints on a checklist.
   *
   * @param bool $status
   *   Checkboxes enabled or disabled.
   */
  protected function checkAllListPoints($status = TRUE) {
    $droopler_update_config = $this->configFactory
      ->getEditable('checklistapi.progress.d_update');
    $user = $this->account->id();
    $time = time();
    $droopler_update_config
      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#changed', $time)
      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#changed_by', $user);
    $exclude = [
      '#title',
      '#description',
      '#weight',
    ];
    foreach ($this->updateChecklist->items as $version_items) {
      foreach ($version_items as $item_name => $item) {
        if (!in_array($item_name, $exclude)) {
          if ($status) {
            $droopler_update_config
              ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items.$item_name", [
                '#completed' => $time,
                '#uid' => $user,
              ]);
          }
          else {
            $droopler_update_config
              ->clear(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items.$item_name");
          }
        }
      }
    }
    $droopler_update_config
      ->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . '.#completed_items', count($droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY . ".#items")))
      ->save();
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
    $progress = [
      '#changed' => $time,
      '#changed_by' => $user->id(),
      '#completed_items' => 0,
      '#items' => [],
    ];
    $status = [
      'positive' => [],
      'negative' => [],
    ];

    $droopler_update_config = $this->configFactory->getEditable('checklistapi.progress.d_update');
    $saved_progress = $droopler_update_config->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY);

    foreach ($values as $group_key => $group) {
      foreach ($group as $item_key => $item) {
        $old_item = (!empty($saved_progress['#items'][$item_key])) ? $saved_progress['#items'][$item_key] : 0;
        if ($item) {
          // Item is checked.
          $status['positive'][] = $item_key;
          $progress['#completed_items']++;
          if ($old_item) {
            // Item was previously checked. Use saved value.
            $new_item = $old_item;
          }
          else {
            // Item is newly checked. Set new value.
            $new_item = [
              '#completed' => $time,
              '#uid' => $user->id(),
            ];
            $num_changed_items++;
          }
          $progress['#items'][$item_key] = $new_item;
        }
        else {
          // Item is unchecked.
          $status['negative'][] = $item_key;
          if ($old_item) {
            // Item was previously checked.
            $num_changed_items++;
          }
        }
      }
    }

    $this->setSuccessfulByHook($status['positive'], TRUE);
    $this->setSuccessfulByHook($status['negative'], FALSE);

    ksort($progress);

    $droopler_update_config->set(ChecklistapiChecklist::PROGRESS_CONFIG_KEY, $progress)
      ->save();
    drupal_set_message(\Drupal::translation()->formatPlural(
      $num_changed_items,
      '%title progress has been saved. 1 item changed.',
      '%title progress has been saved. @count items changed.',
      ['%title' => $this->updateChecklist->title]
    ));
  }

}
