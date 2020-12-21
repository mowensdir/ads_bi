<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines the DriversDataController class.
 */
class DriversDataController extends ControllerBase {
/* ================================================================
 * PRIVATE METHODS
 */
  
  /**
   *
   */
  private function driverSearch($q) {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS CustomerName
      , Drivers.LicenseNumber AS DLN
      , Territories.State AS State
      , IF(COUNT(Items.SerialNumber) >= 1, 'Y', 'N') AS Active
      , Dealers.CompanyName AS Dealer
      , DATE(MIN(BaiidReports.Imported)) AS InstallDate
      , COALESCE((SELECT MAX(Imported) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID AND Type = 'Details'), '') AS LastDownload
      , (SELECT IF(COUNT(*) > 0, 1, 0) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID AND Type ='Details') AS HasDownload
    FROM {Drivers}
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
      INNER JOIN {BaiidReports} USING(DriverID)
      LEFT JOIN {Items} ON (
        Items.DriverID = Drivers.DriverID
        AND
        Items.ProductID = 1
        AND
        Items.SerialNumber LIKE 'HH-%'
      )
    WHERE Drivers.FullName LIKE :qs
      OR Drivers.LicenseNumber LIKE :qs
      OR Drivers.DriverID = :qi
    GROUP BY Drivers.DriverID
      , Drivers.FullName
      , Drivers.LicenseNumber
      , Territories.State
      , Dealers.CompanyName
    LIMIT 500
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':qs' => "%$q%", ':qi' => "$q"]);

    if ($result) {
      $data = $result->fetchAll(\PDO::FETCH_ASSOC);
    }

    return $data;
  }

  /**
   *
   */
  private function getDriver($did) {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT FullName
    FROM Drivers
    WHERE DriverID = :did
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':did' => $did]);

    if ($result) {
      $data = $result->fetchAssoc();
    }

    return $data;
  }

  /**
   *
   */
  private function getDriverDownloads($did) {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT BaiidReports.BaiidReportID
      , BaiidReports.Imported AS Download
      , COALESCE(Dealers.CompanyName, '') AS Dealer
      , BaiidReports.SerialNumber AS Equipment
    FROM {BaiidReports}
      INNER JOIN {DealerLogins} USING(LoginID)
      LEFT JOIN {Dealers} USING(DealerID)
    WHERE BaiidReports.Type = 'Details'
      AND BaiidReports.DriverID = :did
    GROUP BY BaiidReports.BaiidReportID
      , BaiidReports.Imported
      , Dealers.CompanyName
      , BaiidReports.SerialNumber
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':did' => $did]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $item = $row;
        $item['driverlink'] = sprintf('/print/driver-report/%d', $row['BaiidReportID']);
        $item['summarylink'] = sprintf('/print/summary-report/%d', $row['BaiidReportID']);

        $data[] = $item;
      }
    }

    return $data;
  }

  /**
   *
   */
  private function loadDriverDetail($did) {
    return [
      'driver' => self::getDriver($did),
      'downloads' => self::getDriverDownloads($did)
    ];
  }



/* ================================================================
 * PUBLIC STATIC METHODS
 */

  /**
   *
   */
  public static function doDriverSearch($q) {
    return self::driverSearch($q);
  }

  /**
   *
   */
  public static function getDriverDetail($did) {
    return self::loadDriverDetail($did);
  }



/* ================================================================
 * PUBLIC METHODS
 */


}
