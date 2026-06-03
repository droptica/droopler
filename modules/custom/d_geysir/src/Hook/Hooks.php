<?php

declare(strict_types=1);

namespace Drupal\d_geysir\Hook;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Form\ViewsForm;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations for the d_geysir module.
 */
class Hooks {

  use StringTranslationTrait;

  protected const string D_P_MODULE = 'd_p';
  protected const string GEYSIR_PERMISSION = 'geysir manage paragraphs from front-end';

  public function __construct(
    protected readonly AccountInterface $currentUser,
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly AdminContext $adminContext,
    protected readonly RequestContext $requestContext,
    protected readonly ExtensionPathResolver $extensionPathResolver,
  ) {}

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($form_id === 'geysir_modal_add_select_type_form') {
      $this->alterGeysirModalAddSelectTypeForm($form);
    }

    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ViewsForm
      && str_starts_with($form_object->getBaseFormId() ?? '', 'views_form_media_library')
    ) {
      if (isset($form['header'])) {
        $form['header']['#attributes']['class'][] = 'media-library-views-form__header';
        $form['header']['media_bulk_form']['#attributes']['class'][] = 'media-library-views-form__bulk_form';
      }
      $form['actions']['submit']['#attributes']['class'] = ['media-library-select'];
    }

    if ($form_id === 'views_exposed_form'
      && str_starts_with($form['#id'] ?? '', 'views-exposed-form-media-library-widget')
    ) {
      $form['actions']['#attributes']['class'][] = 'media-library-view--form-actions';
      $form['actions']['submit']['#attributes']['class'][] = 'button-override';
    }
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    if (!$this->currentUser->hasPermission(self::GEYSIR_PERMISSION)) {
      return;
    }
    $page['#attached']['library'][] = 'd_geysir/media';
  }

  /**
   * Implements hook_field_widget_link_attributes_form_alter().
   */
  #[Hook('field_widget_link_attributes_form_alter')]
  public function fieldWidgetLinkAttributesFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $element['options']['attributes']['#open'] = 0;
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   *
   * Disable premature autofocus on modal forms.
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $element['open_button']['#attributes']['data-disabled-focus'] = 'false';
  }

  /**
   * Implements hook_toolbar_alter().
   */
  #[Hook('toolbar_alter')]
  public function toolbarAlter(array &$items): void {
    if (!isset($items['geysir'])) {
      return;
    }

    $route = $this->routeMatch->getRouteObject();
    if ($route !== NULL && $this->adminContext->isAdminRoute($route)) {
      $items['geysir'] = [
        '#cache' => $items['geysir']['#cache'],
      ];
      return;
    }

    if (!empty($items['geysir']['tab'])) {
      $items['geysir']['tab']['#value'] = $this->t('Paragraph overlay');
      $items['geysir']['tab']['#attached']['library'][] = 'd_geysir/d_geysir';
    }
  }

  /**
   * Implements hook_preprocess_media_library_item__widget().
   *
   * Targets each media item selected in an entity reference field.
   */
  #[Hook('preprocess_media_library_item__widget')]
  public function preprocessMediaLibraryItemWidget(array &$variables): void {
    $variables['content']['remove_button']['#attributes']['class'][] = 'media-library-item__remove';
    $variables['content']['remove_button']['#attributes']['class'][] = 'no-bootstrap';
  }

  /**
   * Implements hook_preprocess_views_view_fields__media_library().
   *
   * Targets each rendered media item in the grid display of the media
   * library's modal dialog.
   */
  #[Hook('preprocess_views_view_fields__media_library')]
  public function preprocessViewsViewFieldsMediaLibrary(array &$variables): void {
    if (isset($variables['fields']['rendered_entity']->wrapper_attributes)) {
      $variables['fields']['rendered_entity']->wrapper_attributes
        ->addClass('media-library-item__click-to-select-trigger');
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for media_library_add_form.
   */
  #[Hook('form_media_library_add_form_alter')]
  public function formMediaLibraryAddFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $form['#attributes']['class'][] = 'media-library-add-form';

    if (isset($form['media'])) {
      $form['#attributes']['class'][] = 'media-library-add-form--with-input';
      // Wrap the informational message above the unsaved media items.
      $form['description']['#template'] = '<p class="media-library-add-form__description">{{ text }}</p>';
      return;
    }

    $form['#attributes']['class'][] = 'media-library-add-form--without-input';
  }

  /**
   * Implements hook_form_FORM_ID_alter() for media_library_add_form_upload.
   */
  #[Hook('form_media_library_add_form_upload_alter')]
  public function formMediaLibraryAddFormUploadAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $form['#attributes']['class'][] = 'media-library-add-form--upload';
    if (isset($form['container'])) {
      $form['container']['#attributes']['class'][] = 'media-library-add-form__input-wrapper';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for media_library_add_form_oembed.
   */
  #[Hook('form_media_library_add_form_oembed_alter')]
  public function formMediaLibraryAddFormOembedAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $form['#attributes']['class'][] = 'media-library-add-form--oembed';

    if (!isset($form['container'])) {
      return;
    }
    $form['container']['#attributes']['class'][] = 'media-library-add-form__input-wrapper';
    $form['container']['url']['#attributes']['class'][] = 'media-library-add-form-oembed-url';
    $form['container']['submit']['#attributes']['class'][] = 'media-library-add-form-oembed-submit';
  }

  /**
   * Implements hook_preprocess_item_list__media_library_add_form_media_list().
   *
   * Targets each new, unsaved media item added to the media library, before
   * they are saved.
   */
  #[Hook('preprocess_item_list__media_library_add_form_media_list')]
  public function preprocessItemListMediaLibraryAddFormMediaList(array &$variables): void {
    foreach ($variables['items'] as &$item) {
      $item['value']['preview']['#attributes']['class'][] = 'media-library-add-form__preview';
      $item['value']['fields']['#attributes']['class'][] = 'media-library-add-form__fields';
      $item['value']['remove_button']['#attributes']['class'][] = 'media-library-add-form__remove-button';
      $item['value']['remove_button']['#attributes']['class'][] = 'no-bootstrap';

      $fields = &$item['value']['fields'];
      $source_field_name = $fields['#source_field_name'] ?? NULL;
      if ($source_field_name !== NULL && isset($fields[$source_field_name])) {
        $fields[$source_field_name]['#attributes']['class'][] = 'media-library-add-form__source-field';
      }
    }
  }

  /**
   * Implements hook_preprocess_media_library_item__small().
   *
   * Targets each pre-selected media item selected when adding new media in
   * the modal media library dialog.
   */
  #[Hook('preprocess_media_library_item__small')]
  public function preprocessMediaLibraryItemSmall(array &$variables): void {
    $variables['content']['select']['#attributes']['class'][] = 'media-library-item__click-to-select-checkbox';
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    if ($view->id() !== 'media_library') {
      return;
    }

    if ($view->display_handler->options['defaults']['css_class']) {
      $this->addCssClasses($view->displayHandlers->get('default')->options['css_class'], ['media-library-view']);
    }
    else {
      $this->addCssClasses($view->display_handler->options['css_class'], ['media-library-view']);
    }

    if ($view->current_display === 'page') {
      $this->applyMediaLibraryPageClasses($view);
      return;
    }

    if (str_starts_with((string) $view->current_display, 'widget')) {
      $this->applyMediaLibraryWidgetClasses($view);
    }
  }

  /**
   * Implements hook_preprocess_links__media_library_menu().
   *
   * Targets the menu of available media types in the media library's modal
   * dialog.
   *
   * @todo Do this in the relevant template once
   *   https://www.drupal.org/project/drupal/issues/3088856 is resolved.
   */
  #[Hook('preprocess_links__media_library_menu')]
  public function preprocessLinksMediaLibraryMenu(array &$variables): void {
    foreach ($variables['links'] as &$link) {
      $link['link']['#options']['attributes']['class'][] = 'media-library-menu__link';
    }
  }

  /**
   * Inject paragraph preview images into the Geysir "add" selector.
   */
  protected function alterGeysirModalAddSelectTypeForm(array &$form): void {
    $root_path = DRUPAL_ROOT;
    $base_url = $this->requestContext->getBaseUrl();
    $base_url = $base_url === '' ? '' : '/' . trim($base_url, '/') . '/';
    $module_path = $this->extensionPathResolver->getPath('module', self::D_P_MODULE);

    foreach ($form['description'] as $paragraph_name => $attributes) {
      $file_server_path = $root_path . '/' . $module_path . '/images/' . $paragraph_name . '.png';
      if (file_exists($file_server_path)) {
        $file_url = $base_url . $module_path . '/images/' . $paragraph_name . '.png';
        $form['description'][$paragraph_name]['#src'] = $file_url;
      }
    }
  }

  /**
   * Apply CSS classes to media library "page" display fields.
   */
  protected function applyMediaLibraryPageClasses(ViewExecutable $view): void {
    if (array_key_exists('media_bulk_form', $view->field)) {
      $this->addCssClasses(
        $view->field['media_bulk_form']->options['element_class'],
        ['media-library-item__click-to-select-checkbox'],
      );
    }
    if (array_key_exists('rendered_entity', $view->field)) {
      $this->addCssClasses(
        $view->field['rendered_entity']->options['element_class'],
        ['media-library-item__content'],
      );
    }
    if (array_key_exists('edit_media', $view->field)) {
      $this->addCssClasses(
        $view->field['edit_media']->options['alter']['link_class'],
        ['media-library-item__edit'],
      );
    }
    if (array_key_exists('delete_media', $view->field)) {
      $this->addCssClasses(
        $view->field['delete_media']->options['alter']['link_class'],
        ['media-library-item__remove'],
      );
    }
  }

  /**
   * Apply CSS classes to media library "widget" display fields.
   */
  protected function applyMediaLibraryWidgetClasses(ViewExecutable $view): void {
    if (array_key_exists('rendered_entity', $view->field)) {
      $this->addCssClasses(
        $view->field['rendered_entity']->options['element_class'],
        ['media-library-item__content'],
      );
    }
    if (array_key_exists('media_library_select_form', $view->field)) {
      $this->addCssClasses(
        $view->field['media_library_select_form']->options['element_wrapper_class'],
        ['media-library-item__click-to-select-checkbox'],
      );
    }

    if ($view->display_handler->options['defaults']['css_class']) {
      $this->addCssClasses(
        $view->displayHandlers->get('default')->options['css_class'],
        ['media-library-view--widget'],
      );
    }
    else {
      $this->addCssClasses($view->display_handler->options['css_class'], ['media-library-view--widget']);
    }
  }

  /**
   * Merge CSS classes into a space-separated string while removing duplicates.
   *
   * @param string $option
   *   The CSS class option, by reference.
   * @param string[] $classes_to_add
   *   Class names to merge in.
   */
  protected function addCssClasses(string &$option, array $classes_to_add): void {
    $classes = preg_split('/\s+/', $option) ?: [];
    $classes = array_filter($classes);
    $classes = array_merge($classes, $classes_to_add);
    $option = implode(' ', array_unique($classes));
  }

}
