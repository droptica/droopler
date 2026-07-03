<?php

declare(strict_types=1);

namespace Drupal\d_product\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook implementations for the d_product module.
 */
class Hooks {

  use StringTranslationTrait;

  protected const string PRODUCT_BUNDLE = 'd_product';
  protected const int SUBJECT_LIMIT = 100;

  public function __construct(
    protected readonly RequestStack $requestStack,
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly PagerManagerInterface $pagerManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountInterface $currentUser,
  ) {}

  /**
   * Implements hook_preprocess_node().
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];
    if ($node->getType() !== self::PRODUCT_BUNDLE) {
      return;
    }

    $view_mode = $variables['view_mode'] ?? '';
    if ($view_mode === 'teaser') {
      if (isset($variables['content']['field_d_product_media'][0])) {
        $variables['main_image'] = $variables['content']['field_d_product_media'][0];
        unset($variables['content']['field_d_product_media']);
      }
      if (isset($variables['content']['field_product_categories'][0])) {
        $variables['category'] = $variables['content']['field_product_categories'];
        unset($variables['content']['field_product_categories']);
      }
      if (isset($variables['content']['field_d_product_link'])) {
        $variables['link'] = $variables['content']['field_d_product_link'];
        unset($variables['content']['field_d_product_link']);
      }
    }

    if ($view_mode !== 'full') {
      return;
    }
    if (!$node->hasField('field_d_ask_for_product')) {
      return;
    }
    $ask = $node->get('field_d_ask_for_product')->getValue();
    if (((int) ($ask[0]['value'] ?? 0)) === 1) {
      $variables['link_ask_for_product'] = $this->generateAskProductLink($node);
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($form_id === 'views_exposed_form'
      && ($form['#id'] ?? '') === 'views-exposed-form-products-list-products-list'
    ) {
      $this->alterProductsListExposedForm($form);
      return;
    }

    if ($form_id === 'contact_message_feedback_form') {
      $this->alterContactForm($form);
    }
  }

  /**
   * Implements hook_preprocess_page().
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    $route_name = $this->routeMatch->getRouteName() ?? '';

    if (stripos($route_name, 'view.products_list') !== FALSE) {
      $pager = $this->pagerManager->getPager(0);
      if ($pager !== NULL) {
        $variables['pager_total_items'] = $pager->getTotalItems();
      }
      $variables['#attached']['library'][] = 'd_product/d_product_select';
      $variables['#attached']['library'][] = 'd_product/d_product_searches';
    }

    $no_title_routes = [
      'entity.node.canonical',
      'entity.node.revision',
      'view.products_list.products_list',
    ];
    if (!in_array($route_name, $no_title_routes, TRUE)) {
      return;
    }

    $node = $variables['node'] ?? NULL;
    if ($node !== NULL && !$node instanceof NodeInterface) {
      /** @var \Drupal\node\NodeInterface|null $node */
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }
    if ($node instanceof NodeInterface && $node->getType() !== self::PRODUCT_BUNDLE) {
      return;
    }

    foreach ($variables['page']['content'] as &$element) {
      if (($element['#plugin_id'] ?? NULL) === 'page_title_block') {
        $element['#access'] = FALSE;
      }
    }
  }

  /**
   * Implements hook_preprocess_field().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    $variables['is_product'] = FALSE;

    if (($variables['element']['#bundle'] ?? '') !== self::PRODUCT_BUNDLE) {
      return;
    }
    if (($variables['element']['#view_mode'] ?? '') === 'teaser') {
      return;
    }

    $variables['is_product'] = TRUE;
    $items = $variables['element']['#items'];

    $variables['links'] = match ($variables['field_name'] ?? '') {
      'field_tags' => $this->generateTermLinks($items, 'tags_taxonomy_term_name'),
      'field_product_categories' => $this->generateTermLinks($items, 'product_categories_taxonomy_term_name'),
      default => $variables['links'] ?? NULL,
    };
  }

  /**
   * Implements hook_preprocess_image_style().
   */
  #[Hook('preprocess_image_style')]
  public function preprocessImageStyle(array &$variables): void {
    if ($variables['style_name'] !== 'product_thumbnail_pc') {
      return;
    }
    $variables['image'] = [
      '#theme' => 'd_media_canvas_image',
      '#image' => $variables['image'],
    ];
  }

  /**
   * Implements hook_preprocess_pager().
   */
  #[Hook('preprocess_pager')]
  public function preprocessPager(array &$variables): void {
    if (($variables['theme_hook_original'] ?? '') !== 'pager__products_list') {
      return;
    }

    $pager = $this->pagerManager->getPager(0);
    if ($pager === NULL) {
      return;
    }
    $pages_count = $pager->getTotalPages();
    if (isset($variables['items']['pages'][$pages_count])) {
      unset($variables['items']['last']);
    }
    if (isset($variables['items']['last'])) {
      $variables['items']['last']['text'] = $pages_count;
    }
  }

  /**
   * Implements hook_views_plugins_argument_alter().
   */
  #[Hook('views_plugins_argument_alter')]
  public function viewsPluginsArgumentAlter(array &$plugins): void {
    $plugins['node_vid']['class'] = 'Drupal\d_product\Plugin\views\argument\NodeVid';
  }

  /**
   * Style and prefill the products-list exposed form.
   */
  protected function alterProductsListExposedForm(array &$form): void {
    $form['aggregated_field']['#attributes']['placeholder'] = $this->t('Search products...');
    $form['sort_by']['#title'] = '';
    $form['sort_by']['#attributes']['placeholder'] = $this->t('Placeholder');
    unset($form['sort_order']);

    $query = $this->requestStack->getCurrentRequest()?->query->all() ?? [];
    if (!isset($query['f']) || !is_array($query['f'])) {
      return;
    }
    foreach ($query['f'] as $key => $value) {
      $form['f[' . $key . ']'] = [
        '#type' => 'hidden',
        '#value' => $value,
        '#weight' => -1,
      ];
    }
  }

  /**
   * Disable cache on the contact page and autofill from the product query arg.
   */
  protected function alterContactForm(array &$form): void {
    $form['#cache'] = [
      'contexts' => [],
      'max-age' => 0,
    ];

    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL || !$request->query->has('product')) {
      return;
    }

    $nid = $request->query->get('product');
    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if ($node === NULL) {
      return;
    }
    /** @var \Drupal\user\UserInterface|null $user */
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if ($node->bundle() !== self::PRODUCT_BUNDLE || !$node->access('view', $user)) {
      return;
    }

    $alias = $node->toUrl()->setAbsolute()->toString();
    $subject = (string) $this->t(
      'I would like to ask about product @title',
      ['@title' => $node->getTitle()],
    );
    if (strlen($subject) > self::SUBJECT_LIMIT) {
      $subject = substr($subject, 0, self::SUBJECT_LIMIT - 3) . '...';
    }
    $message = $this->t('I would like to ask about @title, link - @link', [
      '@title' => $node->getTitle(),
      '@link' => $alias,
    ]);
    $form['subject']['widget'][0]['value']['#default_value'] = $subject;
    $form['message']['widget'][0]['value']['#default_value'] = $message;
  }

  /**
   * Build custom term links pointing to a facet path.
   *
   * @return array[]
   *   Render arrays for each term link, keyed by delta.
   */
  protected function generateTermLinks(iterable $items, string $element): array {
    $links = [];
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($items as $term_obj) {
      $tid = $term_obj->getValue();
      /** @var \Drupal\taxonomy\TermInterface|null $term */
      $term = $term_storage->load($tid['target_id']);
      if ($term === NULL) {
        continue;
      }
      $name = $term->getName();
      $options = [
        'query' => ['f[0]' => $element . ':' . strtolower($name)],
      ];
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $links[] = Link::fromTextAndUrl($this->t($name), Url::fromUri('internal:/products', $options))
        ->toRenderable();
    }
    return $links;
  }

  /**
   * Build the "Ask for product" CTA link rendered on full product nodes.
   */
  protected function generateAskProductLink(NodeInterface $node): array {
    $options = ['query' => ['product' => $node->id()]];
    $link = Link::fromTextAndUrl(
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $this->t('Ask for product'),
      Url::fromUri('internal:/contact#contact-message-feedback-form', $options),
    )->toRenderable();
    $link['#attributes'] = ['class' => ['btn btn-outline-primary']];
    return $link;
  }

}
