<?php

namespace Drupal\d_demo\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class ThemeLogoService {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ThemeLogoService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Function helps set current theme new demo logo.
   */
  public function setThemeDemoLogo() {
    $demoModulePath = $this->moduleHandler->getModule('d_demo')->getPath();

    $this->setCurrentThemeDemoLogo($demoModulePath . '/assets/demo-logo.svg');
  }

  /**
   * Returns editable config.
   *
   * @param $name
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function getConfig($name) {
    return $this->configFactory->getEditable($name);
  }

  /**
   * Returns current theme editable config.
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function getCurrentThemeConfig() {
    /** @var Drupal\Core\Config\ImmutableConfig $systemConfig */
    $systemConfig = $this->getConfig('system.theme');
    $currentTheme = $systemConfig->get('default');

    return $this->getConfig($currentTheme . '.settings');
  }

  /**
   * Helper for updating current theme settings for logo field.
   *
   * @param $logoPath
   */
  protected function setCurrentThemeDemoLogo($logoPath) {
    if ($logoPath) {
      $currentThemeSettings = $this->getCurrentThemeConfig();
      $currentThemeSettings->set('logo.use_default', FALSE);
      $currentThemeSettings->set('logo.path', $logoPath);
      $currentThemeSettings->save();
    }
  }

}
