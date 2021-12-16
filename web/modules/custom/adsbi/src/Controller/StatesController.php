<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Controller\StatesDataController;
use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines the StatesController class.
 */
class StatesController extends ControllerBase {
  /**
   *
   */
  public function tnViolations() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('compliance')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#states-tnviolations-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/states--tn_violations.html.twig';

      $violations = StatesDataController::getTNViolations();
      $pending    = $violations['pending'];
      $exceptions = $violations['exceptions'];
      $reported   = $violations['reported'];
      $ignored    = $violations['ignored'];

      $context = [
        'json_pending'    => json_encode($pending),
        'json_exceptions' => json_encode($exceptions),
        'json_reported'   => json_encode($reported),
        'json_ignored'    => json_encode($ignored),
      ];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }
}