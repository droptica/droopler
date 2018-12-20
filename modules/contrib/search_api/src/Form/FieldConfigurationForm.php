<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\search_api\Processor\ConfigurablePropertyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a form for changing a field's configuration.
 */
class FieldConfigurationForm extends EntityForm {

  use UnsavedConfigurationFormTrait;

  /**
   * The index for which the fields are configured.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The field whose configuration is edited.
   *
   * @var \Drupal\search_api\Item\FieldInterface
   */
  protected $field;

  /**
   * The "id" attribute of the generated form.
   *
   * @var string
   */
  protected $formIdAttribute;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a FieldConfigurationForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer to use.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, DateFormatterInterface $date_formatter, RequestStack $request_stack, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
    $this->requestStack = $request_stack;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    $renderer = $container->get('renderer');
    $date_formatter = $container->get('date.formatter');
    $request_stack = $container->get('request_stack');
    $messenger = $container->get('messenger');

    return new static($entity_type_manager, $renderer, $date_formatter, $request_stack, $messenger);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_field_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $field = $this->getField();

    if (!$field) {
      $args['@id'] = $this->getRequest()->attributes->get('field_id');
      $form['message'] = [
        '#markup' => $this->t('Unknown field with ID "@id".', $args),
      ];
      return $form;
    }

    $args['%field'] = $field->getLabel();
    $form['#title'] = $this->t('Edit field %field', $args);

    if ($this->getRequest()->query->get('modal_redirect')) {
      $form['title']['#markup'] = new FormattableMarkup('<h2>@title</h2>', ['@title' => $form['#title']]);
      Html::setIsAjax(TRUE);
    }

    $this->formIdAttribute = Html::getUniqueId($this->getFormId());
    $form['#id'] = $this->formIdAttribute;

    $form['messages'] = [
      '#type' => 'status_messages',
    ];

    $property = $field->getDataDefinition();
    if (!($property instanceof ConfigurablePropertyInterface)) {
      $args['%field'] = $field->getLabel();
      $form['message'] = [
        '#markup' => $this->t('Field %field is not configurable.', $args),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $field = $this->getField();
    /** @var \Drupal\search_api\Processor\ConfigurablePropertyInterface $property */
    $property = $field->getDataDefinition();

    $form = $property->buildConfigurationForm($field, $form, $form_state);

    $this->checkEntityEditable($form, $this->entity);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);

    if ($this->getRequest()->query->get('modal_redirect')) {
      $actions['submit']['#ajax']['wrapper'] = $this->formIdAttribute;
    }
    else {
      $actions['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => $this->entity->toUrl('fields'),
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $field = $this->getField();
    /** @var \Drupal\search_api\Processor\ConfigurablePropertyInterface $property */
    $property = $field->getDataDefinition();
    $property->validateConfigurationForm($field, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field = $this->getField();
    /** @var \Drupal\search_api\Processor\ConfigurablePropertyInterface $property */
    $property = $field->getDataDefinition();
    $property->submitConfigurationForm($field, $form, $form_state);

    $this->messenger->addStatus($this->t('The field configuration was successfully saved.'));
    if ($this->getRequest()->query->get('modal_redirect')) {
      $url = $this->entity->toUrl('add-fields-ajax')
        ->setOption('query', [
          MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax',
        ]);
      $form_state->setRedirectUrl($url);
    }
    else {
      $form_state->setRedirectUrl($this->entity->toUrl('fields'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Our form structure doesn't emulate the entity structure, so copying those
    // values wouldn't make any sense.
  }

  /**
   * Retrieves the field that is being edited.
   *
   * @return \Drupal\search_api\Item\FieldInterface|null
   *   The field, if it exists.
   */
  protected function getField() {
    if (!isset($this->field)) {
      $field_id = $this->getRequest()->attributes->get('field_id');
      $this->field = $this->entity->getField($field_id);
    }

    return $this->field;
  }

}
