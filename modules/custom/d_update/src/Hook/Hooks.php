<?php

declare(strict_types=1);

namespace Drupal\d_update\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\d_update\UpdateChecklist;
use Drupal\system\SystemManager;

/**
 * OOP hook implementations for the `d_update` module.
 */
class Hooks {

  use StringTranslationTrait;

  /**
   * Toolbar menu link id we manipulate in {@see self::preprocessMenuToolbar()}.
   */
  protected const string TOOLBAR_LINK_ID = 'd_update.admin_update';

  public function __construct(
    protected readonly UpdateChecklist $updateChecklist,
    protected readonly SystemManager $systemManager,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_checklistapi_checklist_info().
   *
   * @return array<string, array<string, mixed>>
   *   Checklist definitions keyed by checklist id.
   */
  #[Hook('checklistapi_checklist_info')]
  public function checklistapiChecklistInfo(): array {
    return [
      'd_update' => [
        '#title' => $this->t('Droopler update instructions'),
        '#path' => '/admin/config/development/droopler-update',
        '#description' => $this->t('Provides steps to keep your Droopler site up to date.'),
        // Callback resolved via function_exists(); see d_update.module.
        '#callback' => 'd_update_checklistapi_checklist_items',
        '#storage' => 'state',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_menu__toolbar().
   *
   * Rewrites the "Droopler Update" toolbar link's target depending on whether
   * a pending DB update or checklist item is outstanding; hides the link
   * entirely when nothing is pending.
   */
  #[Hook('preprocess_menu__toolbar')]
  public function preprocessMenuToolbar(array &$variables): void {
    if (empty($variables['items'][self::TOOLBAR_LINK_ID])) {
      return;
    }

    if (!$this->updateLinkIsActive()) {
      unset($variables['items'][self::TOOLBAR_LINK_ID]);
      return;
    }

    if ($this->systemHasPendingUpdates()) {
      $variables['items'][self::TOOLBAR_LINK_ID]['title'] = (string) $this->t('Droopler Update');
      $options = $variables['items'][self::TOOLBAR_LINK_ID]['url']->getOptions();
      $variables['items'][self::TOOLBAR_LINK_ID]['url'] = Url::fromRoute('system.db_update')->setOptions($options);
    }

    $variables['#attached']['library'][] = 'd_update/droopler';
  }

  /**
   * Whether the toolbar update link should be shown at all.
   */
  public function updateLinkIsActive(): bool {
    return $this->systemHasPendingUpdates() || $this->checklistHasPendingUpdates();
  }

  /**
   * Whether the Droopler update checklist has any unchecked items.
   */
  public function checklistHasPendingUpdates(): bool {
    if (!$this->updateChecklist->isAvailable()) {
      return FALSE;
    }
    $checklist = checklistapi_checklist_load('d_update');
    return $checklist !== NULL && $checklist->getPercentComplete() !== 100;
  }

  /**
   * Whether Drupal core has pending DB updates.
   */
  public function systemHasPendingUpdates(): bool {
    $requirements = $this->systemManager->listRequirements();
    $update = $requirements['update'] ?? [];
    if (empty($update['severity'])) {
      return FALSE;
    }
    return $update['severity'] === REQUIREMENT_ERROR;
  }

}
