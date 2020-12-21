<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Controller\DriversDataController;
use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use mikehaertl\wkhtmlto\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the DriversController class.
 */
class DriversController extends ControllerBase {
  /**
   *
   */
  public function customerReports() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('compliance')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#drivers-customerreports-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/drivers--customer_reports.html.twig';

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
  public function customerReportsDriverSearch() {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('compliance')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#drivers-customerreports-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/drivers--customer_reports--search.html.twig';
      
      $rows = DriversDataController::doDriverSearch(\Drupal::request()->query->get('q'));
      $context = [
        'q'            => \Drupal::request()->query->get('q'),
        'json_results' => json_encode($rows),
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
  public function customerReportsDriverDetail(int $did) {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$user->hasRole('compliance')) {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/not-authorized.html.twig';

      $context = ['navtabid' => '#drivers-customerreports-nav'];
    } else {
      $template_path = drupal_get_path('module', 'adsbi') . '/templates/drivers--customer_reports--driver_detail.html.twig';
      
      $data    = DriversDataController::getDriverDetail($did);
      $driver  = $data['driver'];
      $context = [
        'driver_name'    => $driver['FullName'],
        'json_downloads' => json_encode($data['downloads']),
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
  public function printDriverReport(int $rid) {
    $tz = new \DateTimeZone(drupal_get_user_timezone());
    $dt = new \DateTime();
    $dt->setTimestamp($_SERVER['REQUEST_TIME']);
    $dt->setTimezone($tz);

    $url = sprintf('https://prod2.stopdwi.com/index.php?page=BaiidEvent:ComplianceDriverReport&BaiidReportID=%d', $rid);
    $pdf = new Pdf($url);
    $pdf->setOptions([
      'footer-font-size' => 8,
      'footer-left'      => $dt->format('l, F m, Y g:i a'),
      'footer-right'     => 'Page [page] of [toPage]',
    ]);

    $basename = sprintf('DriverReport%d.pdf', $rid);
    $content  = $pdf->toString();
    if (false !== $content) {
      $response = new Response($content);
      $response->headers->set('Content-Type', 'application/pdf');
      $response->headers->set('Content-Disposition', sprintf('inline;filename="%s"', $basename));
      $response->headers->set('Content-Transfer-Encoding', 'binary');
      $response->headers->set('Content-Length', strlen($content));
      return $response;
    } else {
      die("<pre>".print_r($pdf->getError(), 1)."</pre>");
    }
  }

  /**
   *
   */
  public function printSummaryReport(int $rid) {
    $url = sprintf('https://prod2.stopdwi.com/index.php?page=BaiidEvent:ComplianceSummaryReport&BaiidReportID=%d', $rid);
    $pdf = new Pdf($url);
    $pdf->setOptions([
      'header-html' => drupal_get_path('module', 'adsbi') . '/templates/summary_report_header.html',
      'footer-html' => drupal_get_path('module', 'adsbi') . '/templates/summary_report_footer.html',
    ]);

    $basename = sprintf('SummaryReport%d.pdf', $rid);
    $content  = $pdf->toString();
    if (false !== $content) {
      $response = new Response($content);
      $response->headers->set('Content-Type', 'application/pdf');
      $response->headers->set('Content-Disposition', sprintf('inline;filename="%s"', $basename));
      $response->headers->set('Content-Transfer-Encoding', 'binary');
      $response->headers->set('Content-Length', strlen($content));
      return $response;
    } else {
      die("<pre>".print_r($pdf->getError(), 1)."</pre>");
    }
  }
}
