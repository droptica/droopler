<?php

namespace Drupal\search_api\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\search_api\ServerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block routines for search server-specific routes.
 */
class ServerController extends ControllerBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|null
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $controller */
    $controller = parent::create($container);

    $controller->setMessenger($container->get('messenger'));

    return $controller;
  }

  /**
   * Retrieves the messenger.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger.
   */
  public function getMessenger() {
    return $this->messenger ?: \Drupal::service('messenger');
  }

  /**
   * Sets the messenger.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The new messenger.
   *
   * @return $this
   */
  public function setMessenger(MessengerInterface $messenger) {
    $this->messenger = $messenger;
    return $this;
  }

  /**
   * Displays information about a search server.
   *
   * @param \Drupal\search_api\ServerInterface $search_api_server
   *   The server to display.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(ServerInterface $search_api_server) {
    // Build the search server information.
    $render = [
      'view' => [
        '#theme' => 'search_api_server',
        '#server' => $search_api_server,
      ],
      '#attached' => [
        'library' => ['search_api/drupal.search_api.admin_css'],
      ],
    ];
    // Check if the server is enabled.
    if ($search_api_server->status()) {
      // Attach the server status form.
      $render['form'] = $this->formBuilder()->getForm('Drupal\search_api\Form\ServerStatusForm', $search_api_server);
    }
    return $render;
  }

  /**
   * Returns the page title for a server's "View" tab.
   *
   * @param \Drupal\search_api\ServerInterface $search_api_server
   *   The server that is displayed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(ServerInterface $search_api_server) {
    return new FormattableMarkup('@title', ['@title' => $search_api_server->label()]);
  }

  /**
   * Enables a search server without a confirmation form.
   *
   * @param \Drupal\search_api\ServerInterface $search_api_server
   *   The server to be enabled.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to send to the browser.
   */
  public function serverBypassEnable(ServerInterface $search_api_server) {
    $search_api_server->setStatus(TRUE)->save();

    // Notify the user about the status change.
    $this->getMessenger()->addStatus($this->t('The search server %name has been enabled.', ['%name' => $search_api_server->label()]));

    // Redirect to the server's "View" page.
    $url = $search_api_server->toUrl('canonical');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

}
