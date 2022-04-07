<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Controller\InventoryDataController;
use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        'json_dealerbd'    => json_encode($this->pivotVMUpdatesDealerSummary($data)),
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

      if (('inventoryCompliance' === $target) || ('inventoryACS' === $target) || ('vm2022Updates' === $target)) {
        $dataset = $this->getInventoryDataSetFromTarget($target);
        $review  = InventoryDataController::doBatchShipDateReview($rows, $target);

        $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--batch_ship_date--review.html.twig';

        $context = [
          'target'         => $target,
          'dataset'        => $dataset,
          'rows_total'     => count($review['data']),
          'rows_update'    => $review['update'],
          'rows_overwrite' => $review['overwrite'],
          'rows_ignore'    => $review['ignore'],
          'rows_missing'   => $review['missing'],
          'rows_error'     => $review['error'],
          'rows_affected'  => $review['update'] + $review['overwrite'],
          'json_review'    => json_encode($review['data']),
          'json_post'      => json_encode($review['post']),
        ];
      } else {
        // Unexpected target dataset value, show error screen
        $template_path = drupal_get_path('module', 'adsbi') . '/templates/error.html.twig';

        $context = [
          'navtabid' => '#inventory-batchshipdate-nav',
          'message'  => 'Unrecognized target dataset value. Could not complete your request, please try again.',
          'link'     => '/app/inventory/batch-ship-date',
        ];
      }
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
  public function batchShipDateRun() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-batchshipdate-nav'];

      return [
        '#type'     => 'inline_template',
        '#template' => file_get_contents($template_path),
        '#context'  => $context,
      ];
    } else {
      $target = \Drupal::request()->request->get('target');
      $json   = \Drupal::request()->request->get('json');

      $update = json_decode($json);

      if (is_null($update) || !is_array($update) || empty($update)) {
        // Could not decode JSON, show error screen
        $template_path = drupal_get_path('module', 'adsbi') . '/templates/error.html.twig';

        $context = [
          'navtabid' => '#inventory-batchshipdate-nav',
          'message'  => 'Malformed JSON data object. Could not complete your request, please try again.',
          'link'     => '/app/inventory/batch-ship-date',
        ];
      }

      if (('inventoryCompliance' === $target) || ('inventoryACS' === $target) || ('vm2022Updates' === $target)) {
        $result = InventoryDataController::doBatchShipDateUpdate($update, $target);
        
        if ($result['success']) {
          $url    = Url::fromRoute('adsbi.inventory.batchshipdate.complete');
          $query  = [
            'target'   => $target,
            'sql_fid'  => $result['sql_fid'],
            'json_fid' => $result['json_fid'],
            'affected' => $result['affected'],
          ];
          $path = $url->setOption('query', $query)->toString();
          $response = new RedirectResponse($path);
          $response->send();
          return;
        } else {
          // Ship Date update failed, show error screen
          $template_path = drupal_get_path('module', 'adsbi') . '/templates/error.html.twig';

          $context = [
            'navtabid' => '#inventory-batchshipdate-nav',
            'message'  => $result['message'],
            'link'     => '/app/inventory/batch-ship-date',
          ];
        }
      } else {
        // Unexpected target dataset value, show error screen
        $template_path = drupal_get_path('module', 'adsbi') . '/templates/error.html.twig';

        $context = [
          'navtabid' => '#inventory-batchshipdate-nav',
          'message'  => 'Unrecognized target dataset value. Could not complete your request, please try again.',
          'link'     => '/app/inventory/batch-ship-date',
        ];
      }
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
  public function batchShipDateComplete() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-batchshipdate-nav'];
    } else {
      $target   = \Drupal::request()->query->get('target');
      $sql_fid  = \Drupal::request()->query->get('sql_fid');
      $json_fid = \Drupal::request()->query->get('json_fid');
      $affected = \Drupal::request()->query->get('affected');

      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--batch_ship_date--complete.html.twig';

      $dash = $this->getDashMetaFromTarget($target);

      $context = [
        'dataset'          => $this->getInventoryDataSetFromTarget($target),
        'sql_url'          => file_create_url(File::load($sql_fid)->getFileUri()),
        'json_url'         => file_create_url(File::load($json_fid)->getFileUri()),
        'rows_affected'    => intval($affected),
        'retail_dash_link' => $dash['retail_link'],
        'retail_dash_name' => $dash['retail_name'],
        'dist_dash_link'   => $dash['dist_link'],
        'dist_dash_name'   => $dash['dist_name'],
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
  public function datasetAddDriver() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-datasetadddriver-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--dataset_add_driver.html.twig';

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
  public function datasetAddDriverReview() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-datasetadddriver-nav'];
    } else {
      $target = \Drupal::request()->request->get('target');
      $data   = \Drupal::request()->request->get('data');
      $lines  = explode("\n", trim($data));

      $rows = [];
      foreach ($lines as $line) {
        $cols = explode(',', trim($line));
        foreach ($cols as $col) {
          $did = trim($col);
          if (!is_numeric($did)) {
            continue;
          }

          $rows[] = intval($did);
        }
      }

      if (('inventoryCompliance' === $target) || ('inventoryACS' === $target) || ('vm2022Updates' === $target)) {
        $dataset = $this->getInventoryDataSetFromTarget($target);
        $review  = InventoryDataController::doDatasetAddDriverReview($rows, $target);

        $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--dataset_add_driver--review.html.twig';

        $context = [
          'target'         => $target,
          'dataset'        => $dataset,
          'rows_total'     => count($review['data']),
          'rows_insert'    => $review['insert'],
          'rows_ignore'    => $review['ignore'],
          'rows_missing'   => $review['missing'],
          'rows_error'     => $review['error'],
          'json_review'    => json_encode($review['data']),
          'json_post'      => json_encode($review['post']),
        ];
      } else {
        // Unexpected target dataset value, show error screen
        $template_path = drupal_get_path('module', 'adsbi') . '/templates/error.html.twig';

        $context = [
          'navtabid' => '#inventory-datasetadddriver-nav',
          'message'  => 'Unrecognized target dataset value. Could not complete your request, please try again.',
          'link'     => '/app/inventory/dataset-add-driver',
        ];
      }
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
  public function datasetAddDriverRun() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-datasetadddriver-nav'];

      return [
        '#type'     => 'inline_template',
        '#template' => file_get_contents($template_path),
        '#context'  => $context,
      ];
    } else {
      $target = \Drupal::request()->request->get('target');
      $json   = \Drupal::request()->request->get('json');

      $insert = json_decode($json);

      if (is_null($insert) || !is_array($insert) || empty($insert)) {
        // Could not decode JSON, show error screen
        $template_path = drupal_get_path('module', 'adsbi') . '/templates/error.html.twig';

        $context = [
          'navtabid' => '#inventory-datasetadddriver-nav',
          'message'  => 'Malformed JSON data object. Could not complete your request, please try again.',
          'link'     => '/app/inventory/dataset-add-driver',
        ];
      }

      if (('inventoryCompliance' === $target) || ('inventoryACS' === $target) || ('vm2022Updates' === $target)) {
        InventoryDataController::doDatasetAddDriverInsert($insert, $target);
        
        $url    = Url::fromRoute('adsbi.inventory.datasetadddriver.complete');
        $query  = [
          'target'   => $target,
          'inserted' => count($insert),
        ];
        $path = $url->setOption('query', $query)->toString();
        $response = new RedirectResponse($path);
        $response->send();
        return;
      } else {
        // Unexpected target dataset value, show error screen
        $template_path = drupal_get_path('module', 'adsbi') . '/templates/error.html.twig';

        $context = [
          'navtabid' => '#inventory-datasetadddriver-nav',
          'message'  => 'Unrecognized target dataset value. Could not complete your request, please try again.',
          'link'     => '/app/inventory/dataset-add-driver',
        ];
      }
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
  public function datasetAddDriverComplete() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('inventory')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#inventory-datasetadddriver-nav'];
    } else {
      $target   = \Drupal::request()->query->get('target');
      $inserted = \Drupal::request()->query->get('inserted');

      $template_path = drupal_get_path('module', 'adsbi') . '/templates/inventory--dataset_add_driver--complete.html.twig';

      $dash = $this->getDashMetaFromTarget($target);

      $context = [
        'dataset'          => $this->getInventoryDataSetFromTarget($target),
        'rows_inserted'    => intval($inserted),
        'retail_dash_link' => $dash['retail_link'],
        'retail_dash_name' => $dash['retail_name'],
        'dist_dash_link'   => $dash['dist_link'],
        'dist_dash_name'   => $dash['dist_name'],
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
  private function pivotVMUpdatesDealerSummary($data) {
    $dealerbd = [];

    $template = [
      'dealer'     => '',
      'state'      => '',
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
        $dealerbd[$key]['state']  = $driver['tState'];
      }

      $dealerbd[$key]['drivers']++;
      $dealerbd[$key]['unresolved']++;
    }

    foreach ($data['resolved'] as $driver) {
      $key = $driver['DealerName'];
      if (!isset($dealerbd[$key])) {
        $dealerbd[$key] = $template;
        $dealerbd[$key]['dealer'] = $key;
        $dealerbd[$key]['state']  = $driver['TerritoryState'];
      }

      $dealerbd[$key]['drivers']++;
      $dealerbd[$key]['resolved']++;
    }

    foreach ($data['removed'] as $driver) {
      $key = $driver['DealerName'];
      if (!isset($dealerbd[$key])) {
        $dealerbd[$key] = $template;
        $dealerbd[$key]['dealer'] = $key;
        $dealerbd[$key]['state']  = $driver['TerritoryState'];
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
  private function getInventoryDataSetFromTarget($target) {
    $dataset = '';
    switch ($target) {
      case 'inventoryCompliance':
        $dataset = 'Inventory Upgrades';
        break;
      case 'inventoryACS':
        $dataset = 'KS HH Swaps';
        break;
      case 'vm2022Updates':
        $dataset = 'VM Updates';
        break;
    }
    return $dataset;
  }

  /**
   * 
   */
  private function getDashMetaFromTarget($target) {
    $dash = [
      'retail_link' => '',
      'retail_name' => '',
      'dist_link'   => '',
      'dist_name'   => '',
    ];
    switch ($target) {
      case 'inventoryCompliance':
        $dash['retail_link'] = Url::fromRoute('adsbi.inventory.upgrades');
        $dash['retail_name'] = 'Device Upgrades';
        $dash['dist_link']   = Url::fromRoute('adsbi.inventory.distributorupgrades');
        $dash['dist_name']   = 'Distributor Upgrades';
        break;
      case 'inventoryACS':
        $dash['retail_link'] = Url::fromRoute('adsbi.inventory.ksswapsretail');
        $dash['retail_name'] = 'KS HH Swaps Retail';
        $dash['dist_link']   = Url::fromRoute('adsbi.inventory.ksswapsdistributors');
        $dash['dist_name']   = 'KS HH Swaps Distributors';
        break;
      case 'vm2022Updates':
        $dash['retail_link'] = Url::fromRoute('adsbi.inventory.vmupdatesretail');
        $dash['retail_name'] = 'VM Updates Retail';
        $dash['dist_link']   = Url::fromRoute('adsbi.inventory.vmupdatesdistributors');
        $dash['dist_name']   = 'VM Updates Distributors';
        break;
    }
    return $dash;
  }
}
