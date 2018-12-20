<?php

namespace Drupal\admin_toolbar_tools\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\CronInterface;
use Drupal\Core\Menu\ContextualLinkManagerInterface;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\PhpStorage\PhpStorageFactory;

/**
 * Class ToolbarController.
 *
 * @package Drupal\admin_toolbar_tools\Controller
 */
class ToolbarController extends ControllerBase {

  /**
   * A cron instance.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * A menu link manager instance.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * A context link manager instance.
   *
   * @var \Drupal\Core\Menu\ContextualLinkManagerInterface
   */
  protected $contextualLinkManager;

  /**
   * A local task manager instance.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskLinkManager;

  /**
   * A local action manager instance.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionLinkManager;

  /**
   * A cache backend interface instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * A date time instance.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * A request stack symfony instance.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * A plugin cache clear instance.
   *
   * @var \Drupal\Core\Plugin\CachedDiscoveryClearerInterface
   */
  protected $pluginCacheClearer;

  /**
   * Constructs a ToolbarController object.
   *
   * @param \Drupal\Core\CronInterface $cron
   *   A cron instance.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   A menu link manager instance.
   * @param \Drupal\Core\Menu\ContextualLinkManagerInterface $contextualLinkManager
   *   A context link manager instance.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $localTaskLinkManager
   *   A local task manager instance.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $localActionLinkManager
   *   A local action manager instance.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheRender
   *   A cache backend interface instance.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   A date time instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack symfony instance.
   * @param \Drupal\Core\Plugin\CachedDiscoveryClearerInterface $plugin_cache_clearer
   *   A plugin cache clear instance.
   */
  public function __construct(CronInterface $cron,
                              MenuLinkManagerInterface $menuLinkManager,
                              ContextualLinkManagerInterface $contextualLinkManager,
                              LocalTaskManagerInterface $localTaskLinkManager,
                              LocalActionManagerInterface $localActionLinkManager,
                              CacheBackendInterface $cacheRender,
                              TimeInterface $time,
                              RequestStack $request_stack,
                              CachedDiscoveryClearerInterface $plugin_cache_clearer) {
    $this->cron = $cron;
    $this->menuLinkManager = $menuLinkManager;
    $this->contextualLinkManager = $contextualLinkManager;
    $this->localTaskLinkManager = $localTaskLinkManager;
    $this->localActionLinkManager = $localActionLinkManager;
    $this->cacheRender = $cacheRender;
    $this->time = $time;
    $this->requestStack = $request_stack;
    $this->pluginCacheClearer = $plugin_cache_clearer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cron'),
      $container->get('plugin.manager.menu.link'),
      $container->get('plugin.manager.menu.contextual_link'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('cache.render'),
      $container->get('datetime.time'),
      $container->get('request_stack'),
      $container->get('plugin.cache_clearer')
    );
  }

  /**
   * Reload the previous page.
   */
  public function reloadPage() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->server->get('HTTP_REFERER')) {
      return $request->server->get('HTTP_REFERER');
    }
    else {
      return '/';
    }
  }

  /**
   * Flushes all caches.
   */
  public function flushAll() {
    $this->messenger()->addMessage($this->t('All caches cleared.'));
    drupal_flush_all_caches();
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Flushes css and javascript caches.
   */
  public function flushJsCss() {
    $this->state()
      ->set('system.css_js_query_string', base_convert($this->time->getCurrentTime(), 10, 36));
    $this->messenger()->addMessage($this->t('CSS and JavaScript cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Flushes plugins caches.
   */
  public function flushPlugins() {
    $this->pluginCacheClearer->clearCachedDefinitions();
    $this->messenger()->addMessage($this->t('Plugins cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Resets all static caches.
   */
  public function flushStatic() {
    drupal_static_reset();
    $this->messenger()->addMessage($this->t('Static cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Clears all cached menu data.
   */
  public function flushMenu() {
    menu_cache_clear_all();
    $this->menuLinkManager->rebuild();
    $this->contextualLinkManager->clearCachedDefinitions();
    $this->localTaskLinkManager->clearCachedDefinitions();
    $this->localActionLinkManager->clearCachedDefinitions();
    $this->messenger()->addMessage($this->t('Routing and links cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Clears all cached views data.
   */
  public function flushViews() {
    views_invalidate_cache();
    $this->messenger()->addMessage($this->t('Views cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Clears the twig cache.
   */
  public function flushTwig() {
    // @todo Update once Drupal 8.6 will be released.
    // @see https://www.drupal.org/node/2908461
    PhpStorageFactory::get('twig')->deleteAll();
    $this->messenger()->addMessage($this->t('Twig cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Run the cron.
   */
  public function runCron() {
    $this->cron->run();
    $this->messenger()->addMessage($this->t('Cron ran successfully.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Clear the rendered cache.
   */
  public function cacheRender() {
    $this->cacheRender->invalidateAll();
    $this->messenger()->addMessage($this->t('Render cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

}
