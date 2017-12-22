<?php

namespace Drupal\d_p_subscribe_file\Forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Link;
use Drupal\d_p_subscribe_file\Entity\SubscribeFileEntity;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SubscribeFileForm
 * @package Drupal\d_p_subscribe_file\Forms
 */
class SubscribeFileForm extends FormBase {

  /**
   * @var \Drupal\Core\Session\AccountInterface|\Drupal\Core\Session\AnonymousUserSession
   */
  protected $accountProxy;

  public function __construct(AccountProxy $accountProxy) {
    $this->accountProxy = $accountProxy->getAccount();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'd_p_subscribe_file_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $paragraph = NULL) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title_display' => 'invisible',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter your name')],
    ];

    $form['mail'] = [
      '#type' => 'email',
      '#title_display' => 'invisible',
      '#title' => $this->t('E-mail'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter you email to get download link')],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Download link'),
    ];
    $file = $paragraph->get('field_file_download');
    $file_id = $file->getValue();
    $form_state->setTemporaryValue('paragraph_id', $paragraph->id());
    $form_state->setTemporaryValue('file_id', $file_id[0]['target_id']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Save entity
    $temporary = $form_state->getTemporary();
    $file_id = $temporary['file_id'];
    $paragraph_id = $temporary['paragraph_id'];
    $paragraph = Paragraph::load($paragraph_id);
    $link_hash = md5(rand() . time());
    $file_hash = md5(rand() . time());
    $contact = SubscribeFileEntity::create([
      'name' => $form_state->getValue('name'),
      'mail' => $form_state->getValue('mail'),
      'link_hash' => $link_hash,
      'file_hash' => $file_hash,
      'fid' => $file_id,
    ]);
    $contact->save();

    // Send mail with link
    $button_text = $paragraph->get('field_d_p_sf_download_button')->getValue();
    $link_options = [
      'absolute' => TRUE,
      'attributes' => ['class' => 'btn-primary btn-orange']
    ];
    $download_link = Link::createFromRoute($button_text[0]['value'], 'd_p_subscribe_file.downloadfile.checkLink', ['paragraph_id' => $paragraph_id, 'link_hash' => $link_hash], $link_options);
    $rendered_download_link = $download_link->toString()->getGeneratedLink();
    if ($this->accountProxy->hasPermission('Administer site configuration')) {
      drupal_set_message($download_link->getUrl()->toString());
    }

    $display_settings = ['label' => 'hidden',];
    $body = $paragraph->get('field_d_p_sf_mail_body')->view($display_settings);
    $body[0]['#text'] = str_replace("[download-button]", $rendered_download_link, $body[0]['#text']);

    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'd_p_subscribe_file';
    $key = 'subscribe_form';
    $to = $form_state->getValue('mail');
    $params['name'] = $form_state->getValue('name');
    $params['mail'] = $form_state->getValue('mail');
    $params['body'] = [
      '#theme' => 'd_p_subscribe_file_mail',
      '#body' => $body,
    ];

    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
    if ($result) {
      drupal_set_message($this->t('We send download link, check Your e-mail.'));
    }

  }
}
