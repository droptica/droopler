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
    $currentThemeSettings = $this->getCurrentThemeConfig();
    if (!$currentThemeSettings->get('default_logo')) {
      $previousLogoPath = $currentThemeSettings->get('logo_path');

      $this->setOriginalLogoPath($previousLogoPath);
    }

    $demoModulePath = $this->moduleHandler->getModule('d_demo')->getPath();

    $this->setCurrentThemeDemoLogo($demoModulePath . '/assets/demo-logo.svg');
  }

  /**
   * Function helps restore original settings for current theme logo.
   */
  public function unsetThemeDemoLogo() {
    $originalThemeLogoPath = $this->getOriginalLogoPath();
    $this->setCurrentThemeDemoLogo($originalThemeLogoPath);
    $this->clearStoredOriginalLogoPath();
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
   * Getter for stored path from d_demo settings.
   *
   * @return array|mixed|null
   */
  protected function getOriginalLogoPath() {
    $demoSettings = $this->getConfig('d_demo.settings');
    return $demoSettings->get('original_logo_path');
  }

  /**
   * Setter for stored path in d_demo settings.
   *
   * @param $originalLogoPath
   */
  protected function setOriginalLogoPath($originalLogoPath) {
    /** @var Drupal\Core\Config\Config $demoSettings */
    $demoSettings = $this->getConfig('d_demo.settings');
    $demoSettings->set('original_logo_path', $originalLogoPath);
    $demoSettings->save();
  }

  /**
   * Clears setting for logo path in d_demo settings.
   */
  protected function clearStoredOriginalLogoPath() {
    $demoSettings = $this->getConfig('d_demo.settings');
    $demoSettings->clear('original_logo_path');
    $demoSettings->save();
  }

  /**
   * Helper for updating current theme settings for logo field.
   *
   * @param $logoPath
   */
  protected function setCurrentThemeDemoLogo($logoPath) {
    $currentThemeSettings = $this->getCurrentThemeConfig();
    if ($logoPath) {
      $currentThemeSettings->set('logo.use_default', FALSE);
      $currentThemeSettings->set('logo.path', $logoPath );
    } else {
      $currentThemeSettings->set('logo.use_default', TRUE);
      $currentThemeSettings->clear('logo.path' );
    }
    $currentThemeSettings->save();
  }
}
