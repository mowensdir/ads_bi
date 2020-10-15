<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Controller\InventoryDataController;
use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines the InventoryController class.
 */
class InventoryController extends ControllerBase {
  /**
   *
   */
  public function upgrades() {
    $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--upgrades.html.twig';

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => [
        'total_cases' => InventoryDataController::getTotalCases(),
        'removed'     => InventoryDataController::getRemovedCases(),
        'shipped'     => InventoryDataController::getTotalShipped(),
      ]
    ];
  }

  /**
   *
   */
  public function ksSwapsRetail() {
    $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--ks_swaps.html.twig';

    $data = InventoryDataController::getKSSwapsRetailData();

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => [
        'navtabid'         => '#inventory-ksswapsretail-nav',
        'title_text'       => 'KS HH Swaps Retail',
        'slug_text'        => 'KSHHSwapsRetail',
        'total_cases'      => $data['cases'],
        'shipped'          => count($data['shipped']),
        'unresolved'       => count($data['unresolved']),
        'resolved'         => count($data['resolved']),
        'removed'          => count($data['removed']),
        'resolved_removed' => count($data['resolved']) + count($data['removed']),
        'json_dealerbd'    => json_encode($this->pivotKSSwapsDealerSummary($data)),
        'json_unresolved'  => json_encode($data['unresolved']),
        'json_resolved'    => json_encode($data['resolved']),
        'json_removed'     => json_encode($data['removed']),
      ]
    ];
  }

  /**
   *
   */
  public function ksSwapsDistributors() {
    $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--ks_swaps.html.twig';

    $data = InventoryDataController::getKSSwapsDistributorData();

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => [
        'navtabid'         => '#inventory-ksswapsdistributors-nav',
        'title_text'       => 'KS HH Swaps Distributors',
        'slug_text'        => 'KSHHSwapsDistributors',
        'total_cases'      => $data['cases'],
        'shipped'          => count($data['shipped']),
        'unresolved'       => count($data['unresolved']),
        'resolved'         => count($data['resolved']),
        'removed'          => count($data['removed']),
        'resolved_removed' => count($data['resolved']) + count($data['removed']),
        'json_dealerbd'    => json_encode($this->pivotKSSwapsDealerSummary($data)),
        'json_unresolved'  => json_encode($data['unresolved']),
        'json_resolved'    => json_encode($data['resolved']),
        'json_removed'     => json_encode($data['removed']),
      ]
    ];
  }

  /**
   *
   */
  private function pivotKSSwapsDealerSummary($data) {
    $dealerbd = [];

    $template = [
      'dealer'     => '',
      'drivers'    => 0,
      'unresolved' => 0,
      'shipped'    => 0,
      'resolved'   => 0,
      'removed'    => 0,
      'progress'   => '',
    ];

    foreach ($data['unresolved'] as $driver) {
      $key = $driver['deName'];
      if (!isset($dealerbd[$key])) {
        $dealerbd[$key] = $template;
        $dealerbd[$key]['dealer'] = $key;
      }

      $dealerbd[$key]['drivers']++;
      $dealerbd[$key]['unresolved']++;
    }

    foreach ($data['resolved'] as $driver) {
      $key = $driver['DealerName'];
      if (!isset($dealerbd[$key])) {
        $dealerbd[$key] = $template;
        $dealerbd[$key]['dealer'] = $key;
      }

      $dealerbd[$key]['drivers']++;
      $dealerbd[$key]['resolved']++;
    }

    foreach ($data['removed'] as $driver) {
      $key = $driver['DealerName'];
      if (!isset($dealerbd[$key])) {
        $dealerbd[$key] = $template;
        $dealerbd[$key]['dealer'] = $key;
      }

      $dealerbd[$key]['drivers']++;
      $dealerbd[$key]['removed']++;
    }

    foreach ($data['shipped'] as $driver) {
      $dealerbd[$driver['DealerName']]['shipped']++;
    }

    foreach ($dealerbd as $key => $val) {
      $dealerbd[$key]['progress'] = sprintf("%.2f%%", (($val['resolved'] + $val['removed']) / $val['drivers']) * 100);
    }

    return array_values($dealerbd);
  }
}
