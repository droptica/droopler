<?php

declare(strict_types=1);

namespace Drupal\d_p_subscribe_file\Forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\d_p_subscribe_file\Entity\SubscribeFileEntity;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File subscribe form.
 */
class SubscribeFileForm extends FormBase {

  /**
   * Number of seconds a generated download link remains valid (24h).
   */
  protected const int LINK_LIFETIME_SECONDS = 86400;

  /**
   * The paragraph rendering this form (set via setParagraph()).
   */
  protected ?ParagraphInterface $paragraph = NULL;

  public function __construct(
    protected readonly AccountInterface $currentUser,
    protected readonly MailManagerInterface $mailManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    // @phpstan-ignore-next-line Drupal uses late static binding for plugin factory pattern.
    return new static(
      $container->get('current_user'),
      $container->get('plugin.manager.mail'),
    );
  }

  /**
   * Setter for the paragraph providing form context.
   */
  public function setParagraph(ParagraphInterface $paragraph): void {
    $this->paragraph = $paragraph;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'd_p_subscribe_file_form_' . ($this->paragraph?->id() ?? 'default');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if ($this->paragraph === NULL) {
      return $form;
    }

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
      '#attributes' => ['placeholder' => $this->t('Enter your email to get download link')],
    ];

    $file = $this->paragraph->get('field_file_download')->getValue();
    $form['file_id'] = [
      '#type' => 'value',
      '#value' => $file[0]['target_id'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->paragraph->get('field_d_p_sf_download_button')->value,
      '#attributes' => ['class' => ['btn-secondary']],
    ];

    // Keep compatibility with older Droopler — only render consents if the
    // field exists on the bundle.
    if ($this->paragraph->hasField('field_d_p_sf_consent')) {
      $consents = $this->paragraph->get('field_d_p_sf_consent')->getValue();
      foreach ($consents as $key => $consent) {
        $form["consent_$key"] = [
          '#type' => 'checkbox',
          '#title' => $consent['value'],
          '#required' => TRUE,
        ];
      }
    }

    $form['#attributes'] = [
      'class' => ['d-p-subscribe-file-form'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($this->paragraph === NULL) {
      return;
    }

    $file_id = $form_state->getValue('file_id');
    $link_hash = $this->generateToken();
    $file_hash = $this->generateToken();
    $contact = SubscribeFileEntity::create([
      'name' => $form_state->getValue('name'),
      'mail' => $form_state->getValue('mail'),
      'link_hash' => $link_hash,
      'file_hash' => $file_hash,
      'fid' => $file_id,
    ]);
    $contact->save();

    $button_text = $this->paragraph->get('field_d_p_sf_download_button')->getValue();
    $link_options = [
      'absolute' => TRUE,
      'attributes' => ['class' => 'btn-primary btn-orange'],
    ];
    $download_link = Link::createFromRoute(
      $button_text[0]['value'],
      'd_p_subscribe_file.downloadfile.checkLink',
      [
        'paragraph_id' => $this->paragraph->id(),
        'link_hash' => $link_hash,
      ],
      $link_options,
    );
    $rendered_download_link = $download_link->toString()->getGeneratedLink();
    if ($this->currentUser->hasPermission('administer site configuration')) {
      $this->messenger()->addStatus($download_link->getUrl()->toString());
    }

    $display_settings = ['label' => 'hidden'];
    $body = $this->paragraph->get('field_d_p_sf_mail_body')->view($display_settings);
    $body[0]['#text'] = str_replace('[download-button]', $rendered_download_link, $body[0]['#text']);

    $params = [
      'name' => $form_state->getValue('name'),
      'mail' => $form_state->getValue('mail'),
      'body' => [
        '#theme' => 'd_p_subscribe_file_mail',
        '#body' => $body,
      ],
    ];

    $result = $this->mailManager->mail(
      'd_p_subscribe_file',
      'subscribe_form',
      $form_state->getValue('mail'),
      $this->currentUser->getPreferredLangcode(),
      $params,
      NULL,
      TRUE,
    );
    if ($result['result']) {
      $this->messenger()->addStatus($this->t('We send download link, check Your e-mail.'));
    }
  }

  /**
   * Generate a cryptographically strong 32-char token.
   */
  protected function generateToken(): string {
    return bin2hex(random_bytes(16));
  }

}
