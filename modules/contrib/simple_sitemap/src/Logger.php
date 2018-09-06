<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Logger
 * @package Drupal\simple_sitemap
 */
class Logger {

  use StringTranslationTrait;

  /*
   * Can be debug/info/notice/warning/error.
   */
  const LOG_SEVERITY_LEVEL_DEFAULT = 'notice';

  /*
   * Can be status/warning/error.
   */
  const DISPLAY_MESSAGE_TYPE_DEFAULT = 'status';

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var string
   */
  protected $message = '';

  /**
   * @var array
   */
  protected $substitutions = [];

  /**
   * Logger constructor.
   *
   * @param $logger
   * @param $current_user
   */
  public function __construct(
    LoggerInterface $logger,
    AccountProxyInterface $current_user
  ) {
    $this->logger = $logger;
    $this->currentUser = $current_user;
  }

  /**
   * @param $message
   * @param array $substitutions
   * @return $this
   */
  public function m($message, $substitutions = []) {
    $this->message = $message;
    $this->substitutions = $substitutions;
    return $this;
  }

  /**
   * @param string $logSeverityLevel
   * @return $this
   */
  public function log($logSeverityLevel = self::LOG_SEVERITY_LEVEL_DEFAULT) {
    $this->logger->$logSeverityLevel(strtr($this->message, $this->substitutions));
    return $this;
  }

  /**
   * @param string $displayMessageType
   * @param string $permission
   * @return $this
   */
  public function display($displayMessageType = self::DISPLAY_MESSAGE_TYPE_DEFAULT, $permission = '') {
    if (empty($permission) || $this->currentUser->hasPermission($permission)) {
      drupal_set_message($this->t($this->message, $this->substitutions), $displayMessageType);
    }
    return $this;
  }
}
