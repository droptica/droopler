<?php

declare(strict_types=1);

namespace Drupal\d_commerce\Hook;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Hook implementations for the d_commerce module.
 */
class Hooks {

  use StringTranslationTrait;

  protected const string MODULE_NAME = 'd_commerce';
  protected const string THEME_NAME = 'droopler_theme';

  public function __construct(
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly LibraryDiscoveryInterface $libraryDiscovery,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'commerce_checkout_order_summary' => [
        'template' => 'd_commerce-checkout-order-summary',
      ],
      'commerce_order_receipt__default' => [
        'template' => 'commerce-order-receipt--default',
        'base hook' => 'commerce_order_receipt__default',
      ],
      'commerce_order_total_summary__default' => [
        'template' => 'commerce-order-total-summary--default',
        'base hook' => 'commerce_order_total_summary',
      ],
      'commerce_coupon_redemption_form' => [
        'template' => 'd_commerce-coupon-redemption-form',
      ],
      'commerce_checkout_completion_message' => [
        'template' => 'commerce-checkout-completion-message',
        'base hook' => 'commerce_checkout_completion_message',
      ],
      'commerce_checkout_completion_register' => [
        'template' => 'commerce-checkout-completion-register',
        'base hook' => 'commerce_checkout_completion_register',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_page().
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    $this->attachLibraries($variables);
  }

  /**
   * Implements hook_preprocess_maintenance_page().
   */
  #[Hook('preprocess_maintenance_page')]
  public function preprocessMaintenancePage(array &$variables): void {
    $this->attachLibraries($variables);
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for commerce_order_total_summary.
   */
  #[Hook('theme_suggestions_commerce_order_total_summary_alter')]
  public function themeSuggestionsCommerceOrderTotalSummaryAlter(array &$suggestions, array $variables): void {
    $suggestions[] = $variables['theme_hook_original'] . '__' . $variables['order_entity']->bundle();
  }

  /**
   * Implements hook_preprocess_HOOK() for commerce_cart_block.
   */
  #[Hook('preprocess_commerce_cart_block')]
  public function preprocessCommerceCartBlock(array &$variables): void {
    $variables['icon'] = NULL;

    $options = [
      'attributes' => ['class' => ['btn btn-primary']],
    ];
    $variables['links'] = [
      [
        '#type' => 'link',
        '#title' => $this->t('Cart'),
        '#url' => Url::fromRoute('commerce_cart.page', [], $options),
      ],
    ];
  }

  /**
   * Implements hook_form_FORM_ID_alter() for commerce_checkout_flow_multistep_default.
   */
  #[Hook('form_commerce_checkout_flow_multistep_default_alter')]
  public function formCommerceCheckoutFlowMultistepDefaultAlter(array &$form, mixed &$form_state, string $form_id): void {
    if (
      isset($form['actions']['next']['#suffix'])
      && $form['actions']['next']['#suffix'] instanceof GeneratedLink
    ) {
      $form['actions']['next']['#suffix']->setGeneratedLink(str_replace(
        'link--previous',
        'link--previous btn btn-light',
        $form['actions']['next']['#suffix']->getGeneratedLink()
      ));
    }
    if (isset($form['shipping_information']['recalculate_shipping'])) {
      $form['shipping_information']['recalculate_shipping']['#attributes']['class'][] = 'btn btn-light mb-4';
    }
    if (isset($form['shipping_information']['shipping_profile']['edit_button'])) {
      $form['shipping_information']['shipping_profile']['edit_button']['#attributes']['class'][] = 'btn btn-light';
    }
    if (isset($form['payment_information']['billing_information']['edit_button'])) {
      $form['payment_information']['billing_information']['edit_button']['#attributes']['class'][] = 'btn btn-light';
    }

    $step = $form['#step_id'] ?? '';

    match ($step) {
      'login' => $this->styleLoginStep($form),
      'order_information' => $this->styleOrderInformationStep($form),
      default => NULL,
    };
  }

  /**
   * Attach related libraries from other modules and themes.
   */
  protected function attachLibraries(array &$variables): void {
    if (!$this->themeHandler->themeExists(self::THEME_NAME)) {
      return;
    }
    $library = $this->libraryDiscovery->getLibraryByName(self::THEME_NAME, self::MODULE_NAME);
    if ($library) {
      $variables['#attached']['library'][] = implode('/', [
        self::THEME_NAME,
        self::MODULE_NAME,
      ]);
    }
  }

  /**
   * Applies button classes used on the checkout "login" step.
   */
  protected function styleLoginStep(array &$form): void {
    if (isset($form['login']['returning_customer']['submit'])) {
      $form['login']['returning_customer']['submit']['#attributes']['class'][] = 'mr-3';
    }
    if (isset($form['login']['returning_customer']['forgot_password'])) {
      $form['login']['returning_customer']['forgot_password']['#attributes'] = [
        'class' => [
          'btn',
          'btn-outline-primary',
        ],
      ];
    }
  }

  /**
   * Adjusts weights used on the checkout "order_information" step.
   */
  protected function styleOrderInformationStep(array &$form): void {
    if (isset($form['payment_information']['billing_information'])) {
      $form['payment_information']['billing_information']['#weight'] = -1;
    }
  }

}
