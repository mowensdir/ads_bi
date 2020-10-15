<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines the DashboardController class.
 */
class DashboardController extends ControllerBase {
  /**
   *
   */
  public function default() {
    $template_path = drupal_get_path('module', 'adsbi') . '/templates/dashboard--default.html.twig';

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => []
    ];
  }
}
