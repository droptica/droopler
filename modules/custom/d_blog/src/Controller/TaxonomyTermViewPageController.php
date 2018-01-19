<?php

/**
 * @file
 * Contains \Drupal\d_blog\Controller\TaxonomyTermViewPageController.
 */

namespace Drupal\d_blog\Controller;

use Drupal\views\Routing\ViewPageController;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class TaxonomyTermViewPageController.
 *
 * @package Drupal\d_blog\Controller
 */
class TaxonomyTermViewPageController extends ViewPageController {

    /**
     * {@inheritdoc}
     */
    public function handle($view_id = 'taxonomy_term' , $display_id = 'page_1', RouteMatchInterface $route_match) {
        $term = $route_match->getParameter('taxonomy_term');

        // Get the vid (vocabulary machine name) of the current term.
        $vid = $term->get('vid')->first()->getValue();
        $vid = $vid['target_id'];

        if ($vid == 'blog_posts_category') {
            // Change view.
            $view_id = 'blog_listing';
        }
        return parent::handle($view_id, $display_id, $route_match);
    }
}