<?php

declare(strict_types = 1);

namespace Drupal\d_twig_extensions;

use Drupal\Core\Template\Attribute;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Bem Twig function.
 */
class BemFunction extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('bem', [
        $this,
        'bemTwigFunction',
      ], [
        'needs_context' => TRUE,
        'is_safe' => [
          'html',
        ],
      ]),
    ];
  }

  /**
   * Bem twig function.
   *
   * @param array $context
   *   Context.
   * @param string $base_class
   *   Base class.
   * @param array $modifiers
   *   Modifiers.
   * @param string $blockname
   *   Blockname.
   * @param array $extra
   *   Extra classes.
   * @param array $attributes
   *   Attributes.
   *
   * @return \Drupal\Core\Template\Attribute|string
   *   The defined attributes.
   */
  public function bemTwigFunction(array $context, string $base_class, array $modifiers = [], string $blockname = '', array $extra = [], array $attributes = []): Attribute | string {
    $classes = [];

    // If using a blockname to override default class.
    if ($blockname) {
      // Set blockname class.
      $classes[] = $blockname . '__' . $base_class;

      // Set blockname--modifier classes for each modifier.
      foreach ($modifiers as $modifier) {
        $classes[] = $blockname . '__' . $base_class . '--' . $modifier;
      }
    }
    // If not overriding base class.
    else {
      // Set base class.
      $classes[] = $base_class;

      // Set base--modifier class for each modifier.
      foreach ($modifiers as $modifier) {
        $classes[] = $base_class . '--' . $modifier;
      }
    };

    // If extra non-BEM classes are added.
    foreach ($extra as $extra_class) {
      $classes[] = $extra_class;
    };

    if (class_exists('Drupal')) {
      // Ensure that the context attributes exist.
      if (!isset($context['attributes'])) {
        $context['attributes'] = new Attribute();
      }

      if (is_array($context['attributes'])) {
        $context['attributes'] = new Attribute($context['attributes']);
      }

      if (!empty($attributes)) {
        $attributes = new Attribute($attributes);
        $context['attributes']->merge($attributes);
      }

      $attributes = new Attribute();
      // Iterate the attributes available in context.
      foreach ($context['attributes'] as $key => $value) {
        // If there are classes, add them to the classes array.
        if ($key === 'class') {
          foreach ($value as $class) {
            $classes[] = $class;
          }
        }
        // Otherwise add the attribute straightaway.
        else {
          $attributes->setAttribute($key, $value);
        }

        // Remove the attribute from context so it doesn't trickle down to
        // includes.
        $context['attributes']->removeAttribute($key);
      }

      // Add class attribute.
      $attributes->setAttribute('class', $classes);

      return $attributes;
    }
    else {
      return 'class="' . implode(' ', $classes) . '"';
    }
  }

}
