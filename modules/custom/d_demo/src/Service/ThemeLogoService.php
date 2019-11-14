<?php

namespace Drupal\d_demo\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\Config;

class ThemeLogoService {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ThemeLogoService constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   * @param ModuleHandlerInterface $moduleHandler
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
   * @return Config
   */
  protected function getConfig($name) {
    return $this->configFactory->getEditable($name);
  }

  /**
   * Returns current theme editable config.
   *
   * @return Config
   */
  protected function getCurrentThemeConfig() {
    /** @var Drupal\Core\Config\ImmutableConfig $systemConfig */
    $systemConfig = $this->getConfig('system.theme');
    $currentTheme =  $systemConfig->get('default');

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
      $currentThemeSettings->set('logo.path', $logoPath );
      $currentThemeSettings->save();
    }
  }
}
