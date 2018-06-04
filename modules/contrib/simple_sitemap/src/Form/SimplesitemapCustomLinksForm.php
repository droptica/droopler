<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class SimplesitemapCustomLinksForm
 * @package Drupal\simple_sitemap\Form
 */
class SimplesitemapCustomLinksForm extends SimplesitemapFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_custom_links_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['simple_sitemap_custom'] = [
      '#title' => $this->t('Custom links'),
      '#type' => 'fieldset',
      '#markup' => '<p>' . $this->t('Add custom internal drupal paths to the XML sitemap.') . '</p>',
      '#prefix' => $this->getDonationText(),
    ];

    $form['simple_sitemap_custom']['custom_links'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Relative Drupal paths'),
      '#default_value' => $this->customLinksToString($this->generator->getCustomLinks(FALSE)),
      '#description' => $this->t("Please specify drupal internal (relative) paths, one per line. Do not forget to prepend the paths with a '/'.<br/>Optionally link priority <em>(0.0 - 1.0)</em> can be added by appending it after a space.<br/> Optionally link change frequency <em>(always / hourly / daily / weekly / monthly / yearly / never)</em> can be added by appending it after a space.<br/><br/><strong>Examples:</strong><br/><em>/ 1.0 daily</em> -> home page with the highest priority and daily change frequency<br/><em>/contact</em> -> contact page with the default priority and no change frequency information"),
    ];

    $form['simple_sitemap_custom']['include_images'] = [
      '#type' => 'select',
      '#title' => $this->t('Include images'),
      '#description' => $this->t('If a custom link points to an entity, include its referenced images in the sitemap.'),
      '#default_value' => $this->generator->getSetting('custom_links_include_images', 0),
      '#options' => [0 => $this->t('No'), 1 => $this->t('Yes')],
    ];

    $this->formHelper->displayRegenerateNow($form['simple_sitemap_custom']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->stringToCustomLinks($form_state->getValue('custom_links')) as $i => $link_config) {
      $placeholders = [
        '@line' => ++$i,
        '@path' => $link_config['path'],
        '@priority' => isset($link_config['priority']) ? $link_config['priority'] : '',
        '@changefreq' => isset($link_config['changefreq']) ? $link_config['changefreq'] : '',
        '@changefreq_options' => implode(', ', FormHelper::getChangefreqOptions()),
      ];

      // Checking if internal path exists.
      if (!$this->pathValidator->isValid($link_config['path'])
//      if (!$this->pathValidator->getUrlIfValidWithoutAccessCheck($link_config['path']) //todo
      // Path validator does not see a double slash as an error. Catching this to prevent breaking path generation.
       || strpos($link_config['path'], '//') !== FALSE) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The path <em>@path</em> does not exist.', $placeholders));
      }

      // Making sure the paths start with a slash.
      if ($link_config['path'][0] !== '/') {
        $form_state->setErrorByName('', $this->t("<strong>Line @line</strong>: The path <em>@path</em> needs to start with a '/'.", $placeholders));
      }

      // Making sure the priority is formatted correctly.
      if (isset($link_config['priority']) && !FormHelper::isValidPriority($link_config['priority'])) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The priority setting <em>@priority</em> for path <em>@path</em> is incorrect. Set the priority from 0.0 to 1.0.', $placeholders));
      }

      // Making sure changefreq is formatted correctly.
      if (isset($link_config['changefreq']) && !FormHelper::isValidChangefreq($link_config['changefreq'])) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The changefreq setting <em>@changefreq</em> for path <em>@path</em> is incorrect. The following are the correct values: <em>@changefreq_options</em>.', $placeholders));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $custom_links = $this->stringToCustomLinks($form_state->getValue('custom_links'));
    $this->generator->removeCustomLinks();
    foreach ($custom_links as $link_config) {
      $this->generator->addCustomLink($link_config['path'], $link_config);
    }
    $this->generator->saveSetting('custom_links_include_images', $form_state->getValue('include_images'));
    parent::submitForm($form, $form_state);

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $this->generator->generateSitemap();
    }
  }

  /**
   * @param $custom_links_string
   * @return array
   */
  protected function stringToCustomLinks($custom_links_string) {

    // Unify newline characters and explode into array.
    $custom_links_string_lines = explode("\n", str_replace("\r\n", "\n", $custom_links_string));

    // Remove empty values and whitespaces from array.
    $custom_links_string_lines = array_filter(array_map('trim', $custom_links_string_lines));

    $custom_links = [];
    foreach ($custom_links_string_lines as $i => &$line) {
      $link_settings = explode(' ', $line);
      $custom_links[$i]['path'] = $link_settings[0];

      // If two arguments are provided for a link, assume the first to be
      // priority, the second to be changefreq.
      if (!empty($link_settings[1]) && !empty($link_settings[2])) {
        $custom_links[$i]['priority'] = $link_settings[1];
        $custom_links[$i]['changefreq'] = $link_settings[2];
      }
      else {
        // If one argument is provided for a link, guess if it is priority or
        // changefreq.
        if (!empty($link_settings[1])) {
          if (is_numeric($link_settings[1])) {
            $custom_links[$i]['priority'] = $link_settings[1];
          }
          else {
            $custom_links[$i]['changefreq'] = $link_settings[1];
          }
        }
      }
    }
    return $custom_links;
  }

  /**
   * @param array $links
   * @return string
   */
  protected function customLinksToString(array $links) {
    $setting_string = '';
    foreach ($links as $custom_link) {
      $setting_string .= $custom_link['path'];
      $setting_string .= isset($custom_link['priority'])
        ? ' ' . $this->formHelper->formatPriority($custom_link['priority'])
        : '';
      $setting_string .= isset($custom_link['changefreq'])
        ? ' ' . $custom_link['changefreq']
        : '';
      $setting_string .= "\r\n";
    }
    return $setting_string;
  }
}
