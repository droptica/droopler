<?php

declare(strict_types=1);

namespace Drupal\droopler\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * OOP hook implementations for the Droopler profile.
 */
class Hooks {

  use StringTranslationTrait;

  /**
   * Placeholder version literal replaced by `extension.list.profile`.
   *
   * Modules ship with `version: DROOPLER_VERSION` in their `.info.yml`; on
   * a real install we rewrite that token to the actual profile version
   * (from {@see ProfileExtensionList::getExtensionInfo()}). The replacement
   * is skipped during `install.php` because the profile isn't installed
   * yet and the lookup would fail.
   */
  protected const string VERSION_PLACEHOLDER = 'DROOPLER_VERSION';

  protected const string INSTALL_PATH_NEEDLE = 'install.php';

  public function __construct(
    protected readonly RequestStack $requestStack,
    protected readonly ProfileExtensionList $profileExtensionList,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_system_info_alter().
   *
   * Rewrites the `DROOPLER_VERSION` placeholder in submodule `.info.yml`
   * to the actual installed profile version.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, string $type): void {
    if (($info['version'] ?? NULL) !== self::VERSION_PLACEHOLDER) {
      return;
    }

    $request = $this->requestStack->getCurrentRequest();
    $current_uri = $request !== NULL ? $request->getRequestUri() : '';
    if (str_contains($current_uri, self::INSTALL_PATH_NEEDLE)) {
      return;
    }

    $drooplerInfo = $this->profileExtensionList->getExtensionInfo('droopler');
    $info['version'] = $drooplerInfo['version'] ?? '';
  }

  /**
   * Implements hook_form_FORM_ID_alter() for install_configure_form.
   *
   * Replaces the core "send anonymous statistics" copy with Droopler-flavoured
   * wording that explains the upstream impact.
   */
  #[Hook('form_install_configure_form_alter')]
  public function formInstallConfigureFormAlter(array &$form, FormStateInterface $form_state): void {
    if (!isset($form['update_notifications']['enable_update_status_module']['#description'])) {
      return;
    }
    $form['update_notifications']['enable_update_status_module']['#description'] = $this->t(
      'By enabling the update notifications you are encouraging Droopler authors to further development of the distribution.',
    );
  }

}
