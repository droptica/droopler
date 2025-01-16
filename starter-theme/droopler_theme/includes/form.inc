<?php

/**
 * @file
 * Theme and preprocess functions for forms.
 */

declare(strict_types = 1);

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Implements hook_preprocess_HOOK().
 */
function droopler_theme_preprocess_input__submit(&$variables) {
  if (isset($variables['element']['#button_type'])) {
    $variables['button_type'] = $variables['element']['#button_type'];
  }
}

/**
 * Implements hook_form_alter().
 */
function droopler_theme_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Add placeholder to contact form.
  if ($form_id == 'views_exposed_form' && in_array($form['#id'], ['views-exposed-form-products-list-products-list', 'views-exposed-form-products-list-product-list-block'])) {
    $form['aggregated_field']['#attributes']['placeholder'] = t('Search products...');
    $form['sort_by']['#title'] = '';
    $form['sort_by']['#attributes']['placeholder'] = t('Placeholder');
    unset($form['sort_order']);
    $query = \Drupal::request()->query->all();
    if (isset($query['f'])) {
      foreach ($query['f'] as $key => $value) {
        $form['f[' . $key . ']'] = [
          '#type' => 'hidden',
          '#value' => $value,
          '#weight' => -1,
        ];
      }
    }
  }
  if ($form_id == 'contact_message_feedback_form') {
    // Disable Cache on contact page.
    $form['#cache'] = [
      'contexts' => [],
      'max-age' => 0,
    ];
    // Autofill contact form.
    if (\Drupal::request()->query->has('product')) {
      $nid = \Drupal::request()->query->get('product');
      $node = Node::load($nid);
      if ($node) {
        $user = User::load(\Drupal::currentUser()->id());
        $check = $node->access('view', $user);
        if ($node->bundle() == 'd_product' && $check) {
          $alias = $node->toUrl()->setAbsolute()->toString();
          $subject = t(
            'I would like to ask about product @title',
            ['@title' => $node->getTitle()]
          );
          $subject = $subject->render();
          $subject = strlen($subject) > 100 ? substr($subject, 0, 97) . "..." : $subject;

          $message = t('I would like to ask about @title, link - @link', [
            '@title' => $node->getTitle(),
            '@link' => $alias,
          ]);
          $form['subject']['widget'][0]['value']['#default_value'] = $subject;
          $form['message']['widget'][0]['value']['#default_value'] = $message;
        }
      }
    }
  }
}
