<?php

namespace Drupal\adsbi\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines the HomeController class.
 */
class HomeController extends ControllerBase {
  /**
   *
   */
  public function default() {
    $template_path = drupal_get_path('module', 'adsbi') . '/templates/home--default.html.twig';

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => []
    ];
  }
}
