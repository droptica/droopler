<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\search_api\Backend\BackendPluginManager;
use Drupal\search_api\ServerInterface;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for creating and editing search servers.
 */
class ServerForm extends EntityForm {

  /**
   * The backend plugin manager.
   *
   * @var \Drupal\search_api\Backend\BackendPluginManager
   */
  protected $backendPluginManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a ServerForm object.
   *
   * @param \Drupal\search_api\Backend\BackendPluginManager $backend_plugin_manager
   *   The backend plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(BackendPluginManager $backend_plugin_manager, MessengerInterface $messenger) {
    $this->backendPluginManager = $backend_plugin_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $backend_plugin_manager = $container->get('plugin.manager.search_api.backend');
    $messenger = $container->get('messenger');

    return new static($backend_plugin_manager, $messenger);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // If the form is being rebuilt, rebuild the entity with the current form
    // values.
    if ($form_state->isRebuilding()) {
      $this->entity = $this->buildEntity($form, $form_state);
    }

    $form = parent::form($form, $form_state);

    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->getEntity();

    // Set the page title according to whether we are creating or editing the
    // server.
    if ($server->isNew()) {
      $form['#title'] = $this->t('Add search server');
    }
    else {
      $form['#title'] = $this->t('Edit search server %label', ['%label' => $server->label()]);
    }

    $this->buildEntityForm($form, $form_state, $server);
    // Skip adding the backend config form if we cleared the server form due to
    // an error.
    if ($form) {
      $this->buildBackendConfigForm($form, $form_state, $server);
    }

    return $form;
  }

  /**
   * Builds the form for the basic server properties.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\search_api\ServerInterface $server
   *   The server that is being created or edited.
   */
  public function buildEntityForm(array &$form, FormStateInterface $form_state, ServerInterface $server) {
    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server name'),
      '#description' => $this->t('Enter the displayed name for the server.'),
      '#default_value' => $server->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $server->isNew() ? NULL : $server->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\search_api\Entity\Server::load',
        'source' => ['name'],
      ],
      '#disabled' => !$server->isNew(),
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Only enabled servers can index items or execute searches.'),
      '#default_value' => $server->status(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Enter a description for the server.'),
      '#default_value' => $server->getDescription(),
    ];

    $backends = $this->backendPluginManager->getDefinitions();
    $backend_options = [];
    $descriptions = [];
    foreach ($backends as $backend_id => $definition) {
      $config = $backend_id === $server->getBackendId() ? $server->getBackendConfig() : [];
      $config['#server'] = $server;
      try {
        /** @var \Drupal\search_api\Backend\BackendInterface $backend */
        $backend = $this->backendPluginManager
          ->createInstance($backend_id, $config);
      }
      catch (PluginException $e) {
        continue;
      }
      if ($backend->isHidden()) {
        continue;
      }
      $backend_options[$backend_id] = Utility::escapeHtml($backend->label());
      $descriptions[$backend_id]['#description'] = Utility::escapeHtml($backend->getDescription());
    }
    asort($backend_options, SORT_NATURAL | SORT_FLAG_CASE);
    if ($backend_options) {
      if (count($backend_options) == 1) {
        $server->set('backend', key($backend_options));
      }
      $form['backend'] = [
        '#type' => 'radios',
        '#title' => $this->t('Backend'),
        '#description' => $this->t('Choose a backend to use for this server.'),
        '#options' => $backend_options,
        '#default_value' => $server->getBackendId(),
        '#required' => TRUE,
        '#disabled' => !$server->isNew(),
        '#ajax' => [
          'callback' => [get_class($this), 'buildAjaxBackendConfigForm'],
          'wrapper' => 'search-api-backend-config-form',
          'method' => 'replace',
          'effect' => 'fade',
        ],
      ];
      $form['backend'] += $descriptions;
    }
    else {
      $url = 'https://www.drupal.org/node/1254698';
      $args[':url'] = Url::fromUri($url)->toString();
      $error = $this->t('There are no backend plugins available for the Search API. Please install a <a href=":url">module that provides a backend plugin</a> to proceed.', $args);
      $this->messenger->addError($error);
      $form = [];
    }
  }

  /**
   * Builds the backend-specific configuration form.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\search_api\ServerInterface $server
   *   The server that is being created or edited.
   */
  public function buildBackendConfigForm(array &$form, FormStateInterface $form_state, ServerInterface $server) {
    $form['backend_config'] = [];
    if ($server->hasValidBackend()) {
      $backend = $server->getBackend();
      $form_state->set('backend', $backend->getPluginId());
      if ($backend instanceof PluginFormInterface) {
        if ($form_state->isRebuilding()) {
          $this->messenger->addWarning($this->t('Please configure the selected backend.'));
        }
        // Attach the backend plugin configuration form.
        $backend_form_state = SubformState::createForSubform($form['backend_config'], $form, $form_state);
        $form['backend_config'] = $backend->buildConfigurationForm($form['backend_config'], $backend_form_state);

        // Modify the backend plugin configuration container element.
        $form['backend_config']['#type'] = 'details';
        $form['backend_config']['#title'] = $this->t('Configure %plugin backend', ['%plugin' => $backend->label()]);
        $form['backend_config']['#open'] = TRUE;
      }
    }
    // Only notify the user of a missing backend plugin if we're editing an
    // existing server.
    elseif (!$server->isNew()) {
      $this->messenger->addError($this->t('The backend plugin is missing or invalid.'));
      return;
    }
    $form['backend_config'] += [
      '#type' => 'container',
    ];
    $form['backend_config']['#attributes']['id'] = 'search-api-backend-config-form';
    $form['backend_config']['#tree'] = TRUE;
  }

  /**
   * Handles switching the selected backend plugin.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The part of the form to return as AJAX.
   */
  public static function buildAjaxBackendConfigForm(array $form, FormStateInterface $form_state) {
    // The work is already done in form(), where we rebuild the entity according
    // to the current form values and then create the backend configuration form
    // based on that. So we just need to return the relevant part of the form
    // here.
    return $form['backend_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->getEntity();

    // Check if the backend plugin changed.
    $backend_id = $server->getBackendId();
    if ($backend_id != $form_state->get('backend')) {
      // This can only happen during initial server creation, since we don't
      // allow switching the backend afterwards. The user has selected a
      // different backend, so any values entered for the other backend should
      // be discarded.
      $input = &$form_state->getUserInput();
      $input['backend_config'] = [];
      $new_backend = $this->backendPluginManager->createInstance($form_state->getValues()['backend']);
      if ($new_backend instanceof PluginFormInterface) {
        $form_state->setRebuild();
      }
    }
    // Check before loading the backend plugin so we don't throw an exception.
    elseif ($server->hasValidBackend()) {
      $backend = $server->getBackend();
      if ($backend instanceof PluginFormInterface) {
        $backend_form_state = SubformState::createForSubform($form['backend_config'], $form, $form_state);
        $backend->validateConfigurationForm($form['backend_config'], $backend_form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->getEntity();
    // Check before loading the backend plugin so we don't throw an exception.
    if ($server->hasValidBackend()) {
      $backend = $server->getBackend();
      if ($backend instanceof PluginFormInterface) {
        $backend_form_state = SubformState::createForSubform($form['backend_config'], $form, $form_state);
        $backend->submitConfigurationForm($form['backend_config'], $backend_form_state);
      }
    }

    return $server;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Only save the server if the form doesn't need to be rebuilt.
    if (!$form_state->isRebuilding()) {
      try {
        $server = $this->getEntity();
        $server->save();
        $this->messenger->addStatus($this->t('The server was successfully saved.'));
        $form_state->setRedirect('entity.search_api_server.canonical', ['search_api_server' => $server->id()]);
      }
      catch (EntityStorageException $e) {
        $form_state->setRebuild();

        $message = '%type: @message in %function (line %line of %file).';
        $variables = Error::decodeException($e);
        $this->getLogger('search_api')->error($message, $variables);

        $this->messenger->addError($this->t('The server could not be saved.'));
      }
    }
  }

}
