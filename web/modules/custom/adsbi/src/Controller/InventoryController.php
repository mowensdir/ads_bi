<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Controller\InventoryDataController;
use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines the InventoryController class.
 */
class InventoryController extends ControllerBase {
  /**
   *
   */
  public function upgrades() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-upgrades-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--upgrades.html.twig';

      $context = [
        'total_cases' => InventoryDataController::getTotalCases(),
        'removed'     => InventoryDataController::getRemovedCases(),
        'shipped'     => InventoryDataController::getTotalShipped(),
      ];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }

  /**
   *
   */
  public function distributorUpgrades() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-distributorupgrades-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--distributorupgrades.html.twig';

      $data    = InventoryDataController::getDistributorUpgradeData();
      $context = [
        'total_cases'     => $data['cases'],
        'unresolved'      => count($data['unresolved']),
        'resolved'        => count($data['resolved']),
        'removed'         => count($data['removed']),
        'shipped'         => count($data['shipped']),
        'nchhvm'          => $data['nchhvm'],
        'nchh'            => $data['nchh'],
        'ncvm'            => $data['ncvm'],
        'json_unresolved' => json_encode($data['unresolved']),
        'json_resolved'   => json_encode($data['resolved']),
        'json_removed'    => json_encode($data['removed']),
        'json_dealerbd'   => json_encode($this->pivotDistributorUpgradeSummary($data)),
      ];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }

  /**
   *
   */
  public function ksSwapsRetail() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-ksswapsretail-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--ks_swaps.html.twig';

      $data    = InventoryDataController::getKSSwapsRetailData();
      $context = [
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
      ];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }

  /**
   *
   */
  public function ksSwapsDistributors() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-ksswapsdistributors-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--ks_swaps.html.twig';

      $data = InventoryDataController::getKSSwapsDistributorData();
      $context = [
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
      ];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }

  /**
   * 
   */
  public function vmUpdatesRetail() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-vmupdatesretail-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--vm_updates.html.twig';

      $data    = InventoryDataController::getVM2022UpdatesRetailData();
      $context = [
        'navtabid'         => '#inventory-vmupdatesretail-nav',
        'title_text'       => 'VM Updates Retail',
        'slug_text'        => 'VMUpdatesRetail',
        'total_cases'      => $data['cases'],
        'shipped'          => count($data['shipped']),
        'unresolved'       => count($data['unresolved']),
        'resolved'         => count($data['resolved']),
        'removed'          => count($data['removed']),
        'resolved_removed' => count($data['resolved']) + count($data['removed']),
        'json_unresolved'  => json_encode($data['unresolved']),
        'json_resolved'    => json_encode($data['resolved']),
        'json_removed'     => json_encode($data['removed']),
        'json_statebd'     => json_encode($this->pivotVMUpdatesStateSummary($data)),
      ];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }

  /**
   * 
   */
  public function vmUpdatesDistributors() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-vmupdatesdistributors-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--vm_updates--distributor.html.twig';

      $data = InventoryDataController::getVM2022UpdatesDistributorData();
      $context = [
        'navtabid'         => '#inventory-vmupdatesdistributors-nav',
        'title_text'       => 'VM Updates Distributors',
        'slug_text'        => 'VMUpdatesDistributors',
        'total_cases'      => $data['cases'],
        'shipped'          => count($data['shipped']),
        'unresolved'       => count($data['unresolved']),
        'resolved'         => count($data['resolved']),
        'removed'          => count($data['removed']),
        'resolved_removed' => count($data['resolved']) + count($data['removed']),
        'json_unresolved'  => json_encode($data['unresolved']),
        'json_resolved'    => json_encode($data['resolved']),
        'json_removed'     => json_encode($data['removed']),
        'json_statebd'     => json_encode($this->pivotVMUpdatesStateSummary($data)),
        'json_distrobd'    => json_encode($this->pivotVMUpdatesDistributorSummary($data)),
      ];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }

  /**
   * 
   */
  public function batchShipDate() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-batchshipdate-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--batch_ship_date.html.twig';

      $context = [];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }

  /**
   * 
   */
  public function batchShipDateReview() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-batchshipdate-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--batch_ship_date--review.html.twig';

      $target = \Drupal::request()->request->get('target');
      $data   = \Drupal::request()->request->get('data');
      $lines  = explode("\n", trim($data));

      $rows = [];
      foreach ($lines as $line) {
        $cols = explode("\t", trim($line));

        if (count($cols) < 2) {
          continue;
        }

        $rows[] = [intval($cols[0]), $cols[1]];
      }

      if ('inventoryCompliance' === $target) {
        $dataset = 'Inventory Upgrades';
        $review  = InventoryDataController::getInventoryUpgradesReview($rows);
      } elseif ('inventoryACS' === $target) {
        $dataset = 'KS HH Swaps';
        $review  = InventoryDataController::getKSHHSwapsReview($rows);
      } elseif ('vm2022Updates' === $target) {
        $dataset = 'VM Updates';
        $review  = InventoryDataController::getVMUpdatesReview($rows);
      } else {
        $dataset = '';
        $review  = [];
      }

      $context = [
        'dataset'     => $dataset,
        'json_review' => json_encode($review),
      ];
    }

    return [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($template_path),
      '#context'  => $context,
    ];
  }

  /**
   *
   */
  private function pivotDistributorUpgradeSummary($data) {
    $dealerbd = [];

    $template = [
      'dealer'     => '',
      'drivers'    => 0,
      'unresolved' => 0,
      'shipped'    => 0,
      'resolved'   => 0,
      'removed'    => 0,
      'nchhvm'     => 0,
      'nchh'       => 0,
      'ncvm'       => 0,
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

      if ('NCHHVM' === $driver['group']) {
        $dealerbd[$key]['nchhvm']++;
      } elseif ('NCHH' === $driver['group']) {
        $dealerbd[$key]['nchh']++;
      } elseif ('NCVM' === $driver['group']) {
        $dealerbd[$key]['ncvm']++;
      }
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

  /**
   *
   */
  private function pivotVMUpdatesStateSummary($data) {
    $statebd = [];

    $template = [
      'state'     => '',
      'drivers'    => 0,
      'unresolved' => 0,
      'shipped'    => 0,
      'resolved'   => 0,
      'removed'    => 0,
      'progress'   => '',
    ];

    foreach ($data['unresolved'] as $driver) {
      $key = $driver['tState'];
      if (!isset($statebd[$key])) {
        $statebd[$key] = $template;
        $statebd[$key]['state'] = $key;
      }

      $statebd[$key]['drivers']++;
      $statebd[$key]['unresolved']++;
    }

    foreach ($data['resolved'] as $driver) {
      $key = $driver['TerritoryState'];
      if (!isset($statebd[$key])) {
        $statebd[$key] = $template;
        $statebd[$key]['state'] = $key;
      }

      $statebd[$key]['drivers']++;
      $statebd[$key]['resolved']++;
    }

    foreach ($data['removed'] as $driver) {
      $key = $driver['TerritoryState'];
      if (!isset($statebd[$key])) {
        $statebd[$key] = $template;
        $statebd[$key]['state'] = $key;
      }

      $statebd[$key]['drivers']++;
      $statebd[$key]['removed']++;
    }

    foreach ($data['shipped'] as $driver) {
      $statebd[$driver['TerritoryState']]['shipped']++;
    }

    foreach ($statebd as $key => $val) {
      $statebd[$key]['progress'] = sprintf("%.2f%%", (($val['resolved'] + $val['removed']) / $val['drivers']) * 100);
    }

    return array_values($statebd);
  }

  /**
   *
   */
  private function pivotVMUpdatesDistributorSummary($data) {
    $distrobd = [];

    $template = [
      'state'     => '',
      'drivers'    => 0,
      'unresolved' => 0,
      'shipped'    => 0,
      'resolved'   => 0,
      'removed'    => 0,
      'progress'   => '',
    ];

    foreach ($data['unresolved'] as $driver) {
      $key = $driver['diName'];
      if (!isset($distrobd[$key])) {
        $distrobd[$key] = $template;
        $distrobd[$key]['distro'] = $key;
      }

      $distrobd[$key]['drivers']++;
      $distrobd[$key]['unresolved']++;
    }

    foreach ($data['resolved'] as $driver) {
      $key = $driver['DistributorName'];
      if (!isset($distrobd[$key])) {
        $distrobd[$key] = $template;
        $distrobd[$key]['distro'] = $key;
      }

      $distrobd[$key]['drivers']++;
      $distrobd[$key]['resolved']++;
    }

    foreach ($data['removed'] as $driver) {
      $key = $driver['DistributorName'];
      if (!isset($distrobd[$key])) {
        $distrobd[$key] = $template;
        $distrobd[$key]['distro'] = $key;
      }

      $distrobd[$key]['drivers']++;
      $distrobd[$key]['removed']++;
    }

    foreach ($data['shipped'] as $driver) {
      $distrobd[$driver['DistributorName']]['shipped']++;
    }

    foreach ($distrobd as $key => $val) {
      $distrobd[$key]['progress'] = sprintf("%.2f%%", (($val['resolved'] + $val['removed']) / $val['drivers']) * 100);
    }

    return array_values($distrobd);
  }

}
