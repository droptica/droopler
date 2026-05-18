<?php

declare(strict_types=1);

namespace Drupal\d_update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\checklistapi\ChecklistapiChecklist;
use Drupal\checklistapi\Storage\StateStorage;
use Drupal\d_update\Entity\Update;

/**
 * Update checklist service.
 *
 * Wraps the `checklistapi` module's checklist storage with Droopler-specific
 * behaviour: persists "update was run" markers on the `d_update_update`
 * content entity in addition to the checklistapi state storage.
 */
class UpdateChecklist {

  use MessengerTrait;
  use StringTranslationTrait;

  protected const string CHECKLIST_ID = 'd_update';

  /**
   * Resolved checklistapi checklist, or NULL when checklistapi is missing.
   *
   * Resolved lazily on first access to avoid a circular reference: invoking
   * `checklistapi_checklist_load()` during this service's construction fires
   * `hook_checklistapi_checklist_info`, which would request our own
   * `\Drupal\d_update\Hook\Hooks` service while it is still being built.
   */
  protected ?ChecklistapiChecklist $updateChecklist = NULL;

  /**
   * Whether the lazy resolution above has already been attempted.
   */
  protected bool $checklistResolved = FALSE;

  /**
   * Storage scoped to this module's checklist id.
   */
  protected readonly StateStorage $checkListStateStorage;

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly AccountInterface $account,
    StateStorage $state_storage,
  ) {
    $state_storage->setChecklistId(self::CHECKLIST_ID);
    $this->checkListStateStorage = $state_storage;
  }

  /**
   * Lazily resolve the checklistapi checklist for this module.
   */
  protected function getChecklist(): ?ChecklistapiChecklist {
    if (!$this->checklistResolved) {
      $this->updateChecklist = function_exists('checklistapi_checklist_load')
        ? checklistapi_checklist_load(self::CHECKLIST_ID)
        : NULL;
      $this->checklistResolved = TRUE;
    }
    return $this->updateChecklist;
  }

  /**
   * Whether the underlying checklistapi checklist is loaded.
   */
  public function isAvailable(): bool {
    return $this->getChecklist() !== NULL;
  }

  /**
   * Mark a list of updates as successful.
   *
   * @param string[] $names
   *   Update ids.
   * @param bool $check_list_points
   *   Also tick the corresponding checkbox in checklistapi.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markUpdatesSuccessful(array $names, bool $check_list_points = TRUE): void {
    if ($this->getChecklist() === NULL) {
      return;
    }
    $this->setSuccessfulByHook($names, TRUE);
    if ($check_list_points) {
      $this->checkListPoints($names);
    }
  }

  /**
   * Mark a list of updates as failed.
   *
   * @param string[] $names
   *   Update ids.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markUpdatesFailed(array $names): void {
    if ($this->getChecklist() === NULL) {
      return;
    }
    $this->setSuccessfulByHook($names, FALSE);
  }

  /**
   * Mark every update on the checklist with the given status.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markAllUpdates(bool $status = TRUE): void {
    $checklist = $this->getChecklist();
    if ($checklist === NULL) {
      return;
    }

    $keys = [];
    foreach ($checklist->items as $version_items) {
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
   * Updates and saves progress of the update checklist.
   *
   * @param array<string, array<string, bool>> $values
   *   Two-dimensional structure `[version_key => [checkbox_id => bool]]`.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveProgress(array $values): void {
    $checklist = $this->getChecklist();
    if ($checklist === NULL) {
      return;
    }

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
          $status['positive'][] = $item_key;
          $num_changed_items++;
          $progress['#completed_items']++;
          $progress['#items'][$item_key] = [
            '#completed' => $time,
            '#uid' => $this->account->id(),
          ];
          continue;
        }
        $status['negative'][] = $item_key;
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
      ['%title' => $checklist->title],
    );
    $this->messenger()->addStatus($message);
  }

  /**
   * Copy checklist values from legacy config-based storage to state-based.
   */
  public function migrateConfigProgressToStateProgress(): void {
    $droopler_update_config = $this->configFactory->getEditable('checklistapi.progress.d_update');
    $config_key = defined(ChecklistapiChecklist::class . '::PROGRESS_CONFIG_KEY')
      ? ChecklistapiChecklist::PROGRESS_CONFIG_KEY
      : 'progress';

    $oldSavedProgress = $droopler_update_config->get($config_key);
    if (empty($oldSavedProgress)) {
      return;
    }

    $newSavedProgress = $this->getChecklistSavedProgress();
    if (!empty($newSavedProgress)) {
      $newSavedProgress['#items'] = array_merge(
        $newSavedProgress['#items'] ?? [],
        $oldSavedProgress['#items'] ?? [],
      );
    }
    else {
      $newSavedProgress = $oldSavedProgress;
    }

    $this->setChecklistSavedProgress($newSavedProgress);
    $droopler_update_config->clear($config_key)->save();
  }

  /**
   * Persist `successful_by_hook` status on the Update entity for each key.
   *
   * @param string[] $keys
   *   Update ids.
   * @param bool $status
   *   Status value to persist on each Update entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setSuccessfulByHook(array $keys, bool $status = TRUE): void {
    foreach ($keys as $key) {
      $update = Update::load($key);
      if ($update instanceof Update) {
        $update->setSuccessfulByHook($status)->save();
        continue;
      }
      Update::create([
        'id' => $key,
        'successful_by_hook' => $status,
      ])->save();
    }
  }

  /**
   * Tick a list of checklistapi bulletpoints.
   *
   * @param string[] $names
   *   Bulletpoint ids.
   */
  protected function checkListPoints(array $names): void {
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
   * Tick / untick every bulletpoint on the checklist.
   */
  protected function checkAllListPoints(bool $status = TRUE): void {
    $checklist = $this->getChecklist();
    if ($checklist === NULL) {
      return;
    }

    $user = $this->account->id();
    $time = time();
    $currentProgress = $this->getChecklistSavedProgress();
    $currentProgress['#changed'] = $time;
    $currentProgress['#changed_by'] = $user;

    $exclude = ['#title', '#description', '#weight'];
    foreach ($checklist->items as $version_items) {
      foreach ($version_items as $item_name => $item) {
        if (in_array($item_name, $exclude, TRUE)) {
          continue;
        }
        if ($status) {
          $currentProgress['#items'][$item_name] = [
            '#completed' => $time,
            '#uid' => $user,
          ];
          continue;
        }
        unset($currentProgress['#items'][$item_name]);
      }
    }
    $currentProgress['#completed_items'] = count($currentProgress['#items']);
    $this->setChecklistSavedProgress($currentProgress);
  }

  /**
   * Saved progress from checklist storage (NULL when nothing is stored).
   */
  protected function getChecklistSavedProgress(): mixed {
    return $this->checkListStateStorage->getSavedProgress();
  }

  /**
   * Persist progress to checklist storage.
   */
  protected function setChecklistSavedProgress(array $progress): void {
    $this->checkListStateStorage->setSavedProgress($progress);
  }

}
