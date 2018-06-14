<?php

namespace Drupal\smtp\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\smtp\Plugin\Mail\SMTPMailSystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the SMTP admin settings form.
 */
class SMTPConfigForm extends ConfigFormBase {

  /**
   * The D8 messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs $messenger and $config_factory objects.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The D8 messenger object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Messenger $messenger) {
    $this->messenger = $messenger;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'smtp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('smtp.settings');

    if ($config->get('smtp_on')) {
      $this->messenger->addMessage($this->t('SMTP module is active.'));
    }
    else {
      $this->messenger->addMessage($this->t('SMTP module is INACTIVE.'));
    }

    $this->messenger->addMessage($this->t('Disabled fields are overridden in site-specific configuration file.'), 'warning');

    $form['onoff'] = [
      '#type'  => 'details',
      '#title' => $this->t('Install options'),
      '#open' => TRUE,
    ];
    $form['onoff']['smtp_on'] = [
      '#type' => 'radios',
      '#title' => $this->t('Turn this module on or off'),
      '#default_value' => $config->get('smtp_on') ? 'on' : 'off',
      '#options' => ['on' => $this->t('On'), 'off' => $this->t('Off')],
      '#description' => $this->t('To uninstall this module you must turn it off here first.'),
      '#disabled' => $this->isOverridden('smtp_on'),
    ];
    $form['server'] = [
      '#type'  => 'details',
      '#title' => $this->t('SMTP server settings'),
      '#open' => TRUE,
    ];
    $form['server']['smtp_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMTP server'),
      '#default_value' => $config->get('smtp_host'),
      '#description' => $this->t('The address of your outgoing SMTP server.'),
      '#disabled' => $this->isOverridden('smtp_host'),
    ];
    $form['server']['smtp_hostbackup'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMTP backup server'),
      '#default_value' => $config->get('smtp_hostbackup'),
      '#description' => $this->t("The address of your outgoing SMTP backup server. If the primary server can\'t be found this one will be tried. This is optional."),
      '#disabled' => $this->isOverridden('smtp_hostbackup'),
    ];
    $form['server']['smtp_port'] = [
      '#type' => 'number',
      '#title' => $this->t('SMTP port'),
      '#size' => 6,
      '#maxlength' => 6,
      '#default_value' => $config->get('smtp_port'),
      '#description' => $this->t('The default SMTP port is 25, if that is being blocked try 80. Gmail uses 465. See :url for more information on configuring for use with Gmail.',
        [':url' => 'http://gmail.google.com/support/bin/answer.py?answer=13287']),
      '#disabled' => $this->isOverridden('smtp_port'),
    ];

    // Only display the option if openssl is installed.
    if (function_exists('openssl_open')) {
      $encryption_options = [
        'standard' => $this->t('No'),
        'ssl' => $this->t('Use SSL'),
        'tls' => $this->t('Use TLS'),
      ];
      $encryption_description = $this->t('This allows connection to an SMTP server that requires SSL encryption such as Gmail.');
    }
    // If openssl is not installed, use normal protocol.
    else {
      $config->set('smtp_protocol', 'standard');
      $encryption_options = ['standard' => $this->t('No')];
      $encryption_description = $this->t('Your PHP installation does not have SSL enabled. See the :url page on php.net for more information. Gmail requires SSL.',
        [':url' => 'http://php.net/openssl']);
    }

    $form['server']['smtp_protocol'] = [
      '#type' => 'select',
      '#title' => $this->t('Use encrypted protocol'),
      '#default_value' => $config->get('smtp_protocol'),
      '#options' => $encryption_options,
      '#description' => $encryption_description,
      '#disabled' => $this->isOverridden('smtp_protocol'),
    ];

    $form['auth'] = [
      '#type' => 'details',
      '#title' => $this->t('SMTP Authentication'),
      '#description' => $this->t('Leave blank if your SMTP server does not require authentication.'),
      '#open' => TRUE,
    ];
    $form['auth']['smtp_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('smtp_username'),
      '#description' => $this->t('SMTP Username.'),
      '#disabled' => $this->isOverridden('smtp_username'),
    ];
    $form['auth']['smtp_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('smtp_password'),
      '#description' => $this->t("SMTP password. If you have already entered your password before, you should leave this field blank, unless you want to change the stored password. Please note that this password will be stored as plain-text inside Drupal\'s core configuration variables."),
      '#disabled' => $this->isOverridden('smtp_password'),
    ];

    $form['email_options'] = [
      '#type'  => 'details',
      '#title' =>$this->t('E-mail options'),
      '#open' => TRUE,
    ];
    $form['email_options']['smtp_from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail from address'),
      '#default_value' => $config->get('smtp_from'),
      '#description' => $this->t('The e-mail address that all e-mails will be from.'),
      '#disabled' => $this->isOverridden('smtp_from'),
    ];
    $form['email_options']['smtp_fromname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail from name'),
      '#default_value' => $config->get('smtp_fromname'),
      '#description' => $this->t('The name that all e-mails will be from. If left blank will use a default of: @name',
          ['@name' => $this->configFactory->get('system.site')->get('name')]),
      '#disabled' => $this->isOverridden('smtp_fromname'),
    ];
    $form['email_options']['smtp_allowhtml'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow to send e-mails formatted as HTML'),
      '#default_value' => $config->get('smtp_allowhtml'),
      '#description' => $this->t('Checking this box will allow HTML formatted e-mails to be sent with the SMTP protocol.'),
      '#disabled' => $this->isOverridden('smtp_allowhtml'),
    ];

    $form['client'] = [
      '#type'  => 'details',
      '#title' => $this->t('SMTP client settings'),
      '#open' => TRUE,
    ];
    $form['client']['smtp_client_hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#default_value' => $config->get('smtp_client_hostname'),
      '#description' => $this->t('The hostname to use in the Message-Id and Received headers, and as the default HELO string. Leave blank for using %server_name.',
        ['%server_name' => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain']),
      '#disabled' => $this->isOverridden('smtp_client_hostname'),
    ];
    $form['client']['smtp_client_helo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HELO'),
      '#default_value' => $config->get('smtp_client_helo'),
      '#description' => $this->t('The SMTP HELO/EHLO of the message. Defaults to hostname (see above).'),
      '#disabled' => $this->isOverridden('smtp_client_helo'),
    ];

    $form['email_test'] = [
      '#type' => 'details',
      '#title' => $this->t('Send test e-mail'),
      '#open' => TRUE,
    ];
    $form['email_test']['smtp_test_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail address to send a test e-mail to'),
      '#default_value' => '',
      '#description' => $this->t('Type in an address to have a test e-mail sent there.'),
    ];

    $form['smtp_debugging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#default_value' => $config->get('smtp_debugging'),
      '#description' => $this->t('Checking this box will print SMTP messages from the server for every e-mail that is sent.'),
      '#disabled' => $this->isOverridden('smtp_debugging'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * @param string $name
   *   STMP settings key.
   *
   * @return bool
   *   Boolean.
   */
  protected function isOverridden($name) {
    $original = $this->configFactory->getEditable('smtp.settings')->get($name);
    $current = $this->configFactory->get('smtp.settings')->get($name);
    return $original != $current;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['smtp_on'] == 'on' && $values['smtp_host'] == '') {
      $form_state->setErrorByName('smtp_host', $this->t('You must enter an SMTP server address.'));
    }

    if ($values['smtp_on'] == 'on' && $values['smtp_port'] == '') {
      $form_state->setErrorByName('smtp_port', $this->t('You must enter an SMTP port number.'));
    }

    if ($values['smtp_from'] && !\Drupal::service('email.validator')->isValid($values['smtp_from'])) {
      $form_state->setErrorByName('smtp_from', $this->t('The provided from e-mail address is not valid.'));
    }

    if ($values['smtp_test_address'] && !\Drupal::service('email.validator')->isValid($values['smtp_test_address'])) {
      $form_state->setErrorByName('smtp_test_address', $this->t('The provided test e-mail address is not valid.'));
    }

    // If username is set empty, we must set both
    // username/password empty as well.
    if (empty($values['smtp_username'])) {
      $values['smtp_password'] = '';
    }
    // A little hack. When form is presented,
    // the password is not shown (Drupal way of doing).
    // So, if user submits the form without changing the password,
    // we must prevent it from being reset.
    elseif (empty($values['smtp_password'])) {
      $form_state->unsetValue('smtp_password');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('smtp.settings');
    $mail_config = $this->configFactory->getEditable('system.mail');
    $mail_system = $mail_config->get('interface.default');

    // Updating config vars.
    if (isset($values['smtp_password']) && !$this->isOverridden('smtp_password')) {
      $config->set('smtp_password', $values['smtp_password']);
    }
    if (!$this->isOverridden('smtp_on')) {
      $config->set('smtp_on', $values['smtp_on'] == 'on')->save();
    }
    $config_keys = [
      'smtp_host',
      'smtp_hostbackup',
      'smtp_port',
      'smtp_protocol',
      'smtp_username',
      'smtp_from',
      'smtp_fromname',
      'smtp_client_hostname',
      'smtp_client_helo',
      'smtp_allowhtml',
      'smtp_debugging',
    ];
    foreach ($config_keys as $name) {
      if (!$this->isOverridden($name)) {
        $config->set($name, $values[$name])->save();
      }
    }

    // Set as default mail system if module is enabled.
    if ($config->get('smtp_on')) {
      if ($mail_system != 'SMTPMailSystem') {
        $config->set('prev_mail_system', $mail_system);
      }
      $mail_system = 'SMTPMailSystem';
      $mail_config->set('interface.default', $mail_system)->save();
    }
    else {
      $default_system_mail = 'php_mail';
      $mail_config = $this->configFactory->getEditable('system.mail');
      $default_interface = ($mail_config->get('prev_mail_system')) ? $mail_config->get('prev_mail_system') : $default_system_mail;
      $mail_config->set('interface.default', $default_interface)
        ->save();
    }

    // If an address was given, send a test e-mail message.
    if ($test_address = $values['smtp_test_address']) {
      $params['subject'] = $this->t('Drupal SMTP test e-mail');
      $params['body'] = [$this->t('If you receive this message it means your site is capable of using SMTP to send e-mail.')];
      $account = \Drupal::currentUser();
      // If module is off, send the test message
      // with SMTP by temporarily overriding.
      if (!$config->get('smtp_on')) {
        $original = $mail_config->get('interface');
        $mail_system = 'SMTPMailSystem';
        $mail_config->set('interface.default', $mail_system)->save();
      }
      \Drupal::service('plugin.manager.mail')->mail('smtp', 'smtp-test', $test_address, $account->getPreferredLangcode(), $params);
      if (!$config->get('smtp_on')) {
        $mail_config->set('interface', $original)->save();
      }
      $this->messenger->addMessage($this->t('A test e-mail has been sent to @email via SMTP. You may want to check the log for any error messages.', ['@email' => $test_address]));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo - Flesh this out.
   */
  public function getEditableConfigNames() {}

}
