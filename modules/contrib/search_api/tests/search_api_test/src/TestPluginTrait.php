<?php

namespace Drupal\search_api_test;

use Drupal\search_api\SearchApiException;

/**
 * Provides common functionality for test plugins.
 */
trait TestPluginTrait {

  /**
   * This object's plugin type.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * Throws an exception if set in the Drupal state for the given method.
   *
   * @param string $method
   *   The method on this object from which this method was called.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if state "search_api_test.TYPE.exception.$method" is TRUE.
   */
  protected function checkError($method) {
    $type = $this->getPluginType();
    $state = \Drupal::state();
    if ($state->get("search_api_test.$type.exception.$method")) {
      throw new SearchApiException($method);
    }
  }

  /**
   * Logs a method call to the site state.
   *
   * @param string $method
   *   The name of the called method.
   * @param array $args
   *   (optional) The method arguments.
   */
  protected function logMethodCall($method, array $args = []) {
    $type = $this->getPluginType();
    $state = \Drupal::state();

    // Log call.
    $key = "search_api_test.$type.methods_called";
    $methods_called = $state->get($key, []);
    $methods_called[] = $method;
    $state->set($key, $methods_called);

    // Log arguments.
    $key = "search_api_test.$type.method_arguments.$method";
    $state->set($key, $args);
  }

  /**
   * Retrieves the value to return for a certain method.
   *
   * @param string $method
   *   The name of the called method.
   * @param mixed $default
   *   (optional) The default return value.
   *
   * @return mixed
   *   The value to return from the method.
   */
  protected function getReturnValue($method, $default = NULL) {
    $type = $this->getPluginType();
    $key = "search_api_test.$type.return.$method";
    return \Drupal::state()->get($key, $default);
  }

  /**
   * Retrieves a possible override set for the given method.
   *
   * @param string $method
   *   The name of the method.
   *
   * @return callable|null
   *   The method override to use, or NULL if none was set.
   */
  protected function getMethodOverride($method) {
    $type = $this->getPluginType();
    $key = "search_api_test.$type.method.$method";
    return \Drupal::state()->get($key);
  }

  /**
   * Returns the plugin type of this object.
   *
   * Equivalent to the last component of the namespace.
   *
   * @return string
   *   The "short" plugin type.
   */
  protected function getPluginType() {
    if (!isset($this->pluginType)) {
      $class = explode("\\", get_class($this));
      array_pop($class);
      $this->pluginType = array_pop($class);
    }

    return $this->pluginType;
  }

  /**
   * Implements the magic __call() method.
   *
   * Allows the easy definition of additional methods via method overrides.
   *
   * @param string $name
   *   The method name.
   * @param array $arguments
   *   The arguments of the method call.
   *
   * @return mixed
   *   The method's return value, if any.
   *
   * @see \Drupal\search_api_test\TestPluginTrait::getMethodOverride()
   */
  public function __call($name, array $arguments) {
    if ($override = $this->getMethodOverride($name)) {
      array_unshift($arguments, $this);
      return call_user_func_array($override, $arguments);
    }
    $class = static::class;
    throw new \BadMethodCallException("Method $class::$name() doesn't exist.");
  }

}
