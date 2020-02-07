<?php

namespace Drupal\d_mautic\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\d_mail\Controller\Mailer;
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

/**
 * Provides integration with Mautic API for handling contacts.
 *
 * @package Drupal\d_mautic\Service
 */
class Contact {

  /**
   * Api path.
   *
   * @const string
   */
  const API_PATH = '/api/';

  /**
   * Mautic credentials.
   *
   * @var array
   */
  protected $settings;

  /**
   * Mautic token.
   *
   * @var array
   */
  protected $accessTokenData;

  /**
   * Mautic credentials stored in the database.
   *
   * @var array
   */
  protected $config;

  /**
   * Mautic auth object.
   *
   * @var \Mautic\Auth\AuthInterface
   */
  protected $auth;

  /**
   * Mautic API Auth object.
   *
   * @var \Mautic\Auth
   */
  protected $init;

  /**
   * Mautic mailer object.
   *
   * @var \Drupal\d_mail\Controller\Mailer
   */
  private $mailer;

  /**
   * Constructs Contact service provider.
   *
   * @param ConfigFactory $config_factory
   * @param Mailer $mailer
   */
  public function __construct(ConfigFactory $config_factory, Mailer $mailer) {
    $this->mailer = $mailer;
    $this->config = $config_factory->getEditable('d_mautic.settings');
    $config = $this->config->getRawData();
    $this->init = new ApiAuth();
    $this->accessTokenData($config);
    $this->settings($config);
  }

  /**
   * Sets accessTokenData.
   *
   * @param array $config
   */
  private function accessTokenData(array $config) {
    $this->accessTokenData = [
      'access_token' => $config['d_mautic_access_token'],
      'expires' => $config['d_mautic_expires'],
      'token_type' => $config['d_mautic_token_type'],
      'refresh_token' => $config['d_mautic_refresh_token'],
    ];
  }

  /**
   * Sets Mautic OAuth2 settings.
   *
   * @param array $config
   */
  private function settings(array $config) {
    $this->settings = [
      'baseUrl' => $config['d_mautic_url'],
      'version' => 'OAuth2',
      'clientKey' => $config['d_mautic_client_id'],
      'clientSecret' => $config['d_mautic_client_secret'],
      'callback' => $config['d_mautic_redirect_uri'],
    ];
  }

  /**
   * Initiates Mautic API authorization.
   */
  public function authorize() {
    $this->auth = $this->init->newAuth($this->settings);

    if ($this->auth->validateAccessToken()) {
      if ($this->auth->accessTokenUpdated()) {
        $this->accessTokenData = $this->auth->getAccessTokenData();
        $this->config->set('d_mautic_access_token', $this->accessTokenData['access_token'])
          ->set('d_mautic_expires', $this->accessTokenData['expires'])
          ->set('d_mautic_token_type', $this->accessTokenData['token_type'])
          ->set('d_mautic_refresh_token', $this->accessTokenData['refresh_token'])
          ->save();
      }
    }
  }

  /**
   * Initiates Mautic API connection in selected context.
   *
   * @param string $api_context
   *   MauticApi context type.
   *
   * @return \Mautic\Api\Api
   *   MauticApi instance in $api_context.
   *
   * @throws \Mautic\Exception\ContextNotFoundException
   */
  private function initMauticApiByContext(string $api_context) {
    $auth = $this->init->newAuth($this->settings)
      ->setAccessTokenDetails($this->accessTokenData);
    $api = new MauticApi();

    return $api->newApi($api_context, $auth, $this->settings['baseUrl'] . self::API_PATH);
  }

  /**
   * Creates a new contact and save it in supplied Mautic service.
   *
   * @param array $data
   *   Contact data to save.
   *
   * @throws \Exception
   *   Exception represents request errors, returned to logger object.
   */
  public function createContact(array $data) {
    $api = $this->initMauticApiByContext('contacts');
    $data = [
      'firstname' => $data['name'],
      'lastname' => '',
      'email' => $data['email'],
      'ipAddress' => \Drupal::request()->getClientIp(),
    ];

    $response = $api->create($data);

    if (isset($response['errors']) && !empty($response['errors'])) {
      $this->sendErrorsToMail($response['errors']);
      $this->sendErrorsToLog($response['errors']);
    }

    if (isset($response['contact']['id'])) {
      $this->addToSegment($response['contact']['id']);
    }
  }

  /**
   * Add a contact to a specific segment.
   *
   * @param int $contact_id
   *   Contact data to save.
   *
   * @throws \Mautic\Exception\ContextNotFoundException
   */
  private function addToSegment(int $contact_id) {
    $api = $this->initMauticApiByContext('segments');
    $uri = \Drupal::request()->getHttpHost();
    $segment_id = stristr($uri, 'com');

    $api->addContact($segment_id, $contact_id);
  }

  /**
   * Sends Mautic authorization errors to mail.
   *
   * @param array $errors
   *   Request errors.
   */
  private function sendErrorsToMail($errors) {
    $title = 'Mautic errors reporting';
    $token = isset($this->accessTokenData['expires']) && !empty($this->accessTokenData['expires']);
    $tokenExpires = $token ? $this->accessTokenData['expires'] : FALSE;
    $extra = $tokenExpires ?
      "Current access_token expires: " . date("F j, Y", $tokenExpires) :
      "There is no access_token in the system.";

    $mailer_values = [
      'errors' => $errors,
      'extra' => $extra,
    ];

    $this->mailer->sendErrorReport($mailer_values, $title);
  }

  /**
   * Sends Mautic authorization errors to Drupal logs.
   *
   * @param array $errors_arr
   *   Request errors.
   *
   * @throws \Exception
   *   Exception represents request errors, returned to logger object.
   */
  private function sendErrorsToLog($errors_arr) {
    $errors = '';
    foreach ($errors_arr as $key => $error) {
      $errors .= 'nr.:' . $key . "<br>"
        . 'code:' . $error['code'] . "<br>"
        . 'type:' . $error['type'] . "<br>"
        . 'message:' . $error['message'] . "<br>";
    }

    throw new \Exception($errors);
  }

}
