<?php

declare(strict_types=1);

namespace Drupal\d_demo\Hook;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Hook implementations for the d_demo module.
 */
class Hooks {

  protected const string MODULE_NAME = 'd_demo';

  public function __construct(
    protected readonly ExtensionPathResolver $extensionPathResolver,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_content_structure_alter().
   */
  #[Hook('content_structure_alter')]
  public function contentStructureAlter(array &$structure, string $context): void {
    if ($context !== 'all') {
      return;
    }

    $path = $this->extensionPathResolver->getPath('module', self::MODULE_NAME) . '/pages';
    $structure['homepage'] = [
      'file' => "$path/homepage.yml",
      'link' => 'Home',
      'weight' => -50,
    ];
    $structure['invest'] = ['file' => "$path/invest.yml"];
    $structure['services'] = [
      'file' => "$path/services.yml",
      'link' => 'Services',
      'weight' => 20,
    ];
    $structure['services_engine_installation'] = ['file' => "$path/services_engine_installation.yml"];
    $structure['services_engine_overhaul'] = ['file' => "$path/services_engine_overhaul.yml"];
    $structure['services_monitoring_and_assessment'] = ['file' => "$path/services_monitoring_and_assessment.yml"];
    $structure['services_engine_repair_and_overhaul'] = ['file' => "$path/services_engine_repair_and_overhaul.yml"];
    $structure['services_engine_repair'] = ['file' => "$path/services_engine_repair.yml"];
    $structure['services_engine_oil_change'] = ['file' => "$path/services_engine_oil_change.yml"];
    $structure['services_engine_testing_services'] = ['file' => "$path/services_engine_testing_services.yml"];
    $structure['services_for_aircrafts'] = ['file' => "$path/services_for_aircrafts.yml"];
    $structure['services_for_boats'] = ['file' => "$path/services_for_boats.yml"];
    $structure['services_for_cars'] = ['file' => "$path/services_for_cars.yml"];
    $structure['services_ad_hoc_engine_repairs'] = ['file' => "$path/services_ad_hoc_engine_repairs.yml"];
    $structure['services_regular_servicing'] = ['file' => "$path/services_regular_servicing.yml"];
    $structure['services_for_spacecrafts'] = ['file' => "$path/services_for_spacecrafts.yml"];
    $structure['contact'] = ['file' => "$path/contact.yml"];
    $structure['about_us'] = ['file' => "$path/about_us.yml"];
    $structure['careers'] = ['file' => "$path/careers.yml"];
    $structure['engines_car'] = [
      'file' => "$path/engines_car.yml",
      'link' => 'Car Engines',
      'parent' => 'd_demo.engines',
      'children' => [
        'engines_car_diesel' => [
          'file' => "$path/engines_car_diesel.yml",
          'link' => 'Diesel Engines',
          'weight' => 0,
        ],
        'engines_car_gasoline' => [
          'file' => "$path/engines_car_gasoline.yml",
          'link' => 'Gasoline Engines',
          'weight' => 1,
        ],
      ],
    ];
    $structure['engines_boat'] = [
      'file' => "$path/engines_boat.yml",
      'link' => 'Boat Engines',
      'parent' => 'd_demo.engines',
    ];
    $structure['engines_aircraft'] = [
      'file' => "$path/engines_aircraft.yml",
      'link' => 'Aircraft Engines',
      'parent' => 'd_demo.engines',
    ];
    $structure['gdpr'] = ['file' => "$path/gdpr.yml"];
    $structure['terms_of_service'] = ['file' => "$path/terms_of_service.yml"];
    $structure['privacy_policy'] = ['file' => "$path/privacy_policy.yml"];
  }

  /**
   * Implements hook_block_structure_alter().
   */
  #[Hook('block_structure_alter')]
  public function blockStructureAlter(array &$structure, string $context): void {
    $path = $this->extensionPathResolver->getPath('module', self::MODULE_NAME) . '/blocks';

    if ($context === 'block_second_run') {
      $structure['cars'] = ['file' => "$path/mega_submenu_services_for_cars.yml"];
      $structure['boats'] = ['file' => "$path/mega_submenu_services_for_boats.yml"];
      $structure['aircrafts'] = ['file' => "$path/mega_submenu_services_for_aircrafts.yml"];
      $structure['spacecrafts'] = ['file' => "$path/mega_submenu_services_for_spacecrafts.yml"];
      return;
    }

    if ($context !== 'all') {
      return;
    }

    $structure['footer_office_1'] = ['file' => "$path/footer_office_1.yml"];
    $structure['footer_office_2'] = ['file' => "$path/footer_office_2.yml"];
    $structure['footer_engines'] = ['file' => "$path/footer_engines.yml"];
    $structure['footer_services'] = ['file' => "$path/footer_services.yml"];
    $structure['footer_others'] = ['file' => "$path/footer_others.yml"];
    $structure['footer_secondary_menu'] = ['file' => "$path/footer_secondary_menu.yml"];
    $structure['d_social_media_footer'] = ['file' => "$path/d_social_media_footer.yml"];
    $structure['secondary_menu'] = ['file' => "$path/secondary_menu.yml"];
    $structure['bottom_footer_menu'] = ['file' => "$path/bottom_footer_menu.yml"];

    if ($this->moduleHandler->moduleExists('d_search')) {
      $structure['header_search_link'] = ['file' => "$path/header_search_link.yml"];
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($form_id !== 'contact_message_feedback_form') {
      return;
    }

    unset($form['actions']['preview'], $form['copy']);

    foreach (Element::children($form) as $key) {
      $this->applyPlaceholderTitles($form[$key]);
    }

    // Add a wrapper for the name + mail fields.
    $wrapper = [
      '#type' => 'container',
      '#weight' => -666,
      '#attributes' => [
        'class' => ['name-and-mail'],
      ],
    ];

    $wrapper['name'] = $form['name'];
    $wrapper['mail'] = $form['mail'];
    $this->normalizeWrapperItem($wrapper['name']);
    $this->normalizeWrapperItem($wrapper['mail']);

    $form['name_and_mail'] = $wrapper;
    unset($form['name'], $form['mail']);
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    $page['#attached']['library'][] = 'd_demo/d_demo_random_price';
  }

  /**
   * Move titles into placeholders for the supported element types.
   */
  protected function applyPlaceholderTitles(array &$formElement): void {
    if (!isset($formElement['#type'])) {
      return;
    }

    match ($formElement['#type']) {
      'text', 'textfield', 'email' => $this->moveTitleToPlaceholder($formElement),
      'item', 'container' => $this->moveWidgetTitleToPlaceholder($formElement),
      default => NULL,
    };
  }

  /**
   * Move the element title into the placeholder attribute.
   */
  protected function moveTitleToPlaceholder(array &$formElement): void {
    $formElement['#attributes']['placeholder'] = $formElement['#title'];
    $formElement['#title_display'] = 'invisible';
  }

  /**
   * Move the widget title into the placeholder attribute.
   */
  protected function moveWidgetTitleToPlaceholder(array &$formElement): void {
    if (empty($formElement['widget']['#title'])) {
      return;
    }
    $title = $formElement['widget']['#title'];
    $formElement['widget'][0]['value']['#attributes']['placeholder'] = $title;
    $formElement['widget'][0]['value']['#title_display'] = 'invisible';
  }

  /**
   * Coerce an `item` field into a disabled textfield with placeholder.
   */
  protected function normalizeWrapperItem(array &$item): void {
    if ($item['#type'] !== 'item') {
      return;
    }
    $item['#type'] = 'textfield';
    $item['#disabled'] = TRUE;
    $item['#required'] = TRUE;
    $item['#attributes']['placeholder'] = $item['#title'];
    $item['#title_display'] = 'invisible';
  }

}
