<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines the StatesDataController class.
 */
class StatesDataController extends ControllerBase {
/* ================================================================
 * PRIVATE METHODS
 */
  
  /**
   *
   */
  private function loadTNViolationsPending() {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT TennesseeViolations.DriverID
      , Drivers.FullName AS CustomerName
      , Drivers.LicenseNumber AS DLN
      , TennesseeViolations.Occurred AS ViolationTimestamp
      , TennesseeViolations.Code AS ViolationCode
      , TennesseeViolations.Source AS ViolationSource
      , TennesseeViolations.Status AS ViolationStatus
      , CASE
        WHEN TennesseeViolations.Code = '*22' THEN 'Power Loss'
        WHEN TennesseeViolations.Code = '*24' THEN 'Unauthorized Start'
        ELSE TennesseeViolations.Code
      END AS Violation
    FROM {TennesseeViolations}
      INNER JOIN {Drivers} USING(DriverID)
    WHERE TennesseeViolations.Status IN ('New', 'Approved')
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql));

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        if ('PROD' === $row['ViolationSource']) {
          $item = $row;
          $item['reviewlink'] = '';

          if ('Approved' === $item['ViolationStatus']) {
            $item['ViolationStatus'] = 'Queued';
          }
          
          $data[] = $item;
        }
      }
    }

    return $data;
  }

  /**
   *
   */
  private function loadTNViolationsExceptions() {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT TennesseeViolations.DriverID
      , Drivers.FullName AS CustomerName
      , Drivers.LicenseNumber AS DLN
      , TennesseeViolations.Occurred AS ViolationTimestamp
      , TennesseeViolations.Code AS ViolationCode
      , TennesseeViolations.Source AS ViolationSource
      , TennesseeViolations.Status AS ViolationStatus
      , CASE
        WHEN TennesseeViolations.Code = '*22' THEN 'Power Loss'
        WHEN TennesseeViolations.Code = '*24' THEN 'Unauthorized Start'
        ELSE TennesseeViolations.Code
      END AS Violation
      , TennesseeViolations.Exception AS Exception
      , TennesseeViolations.TennesseeViolationID AS TennesseeViolationID
      , TennesseeViolations.Rejected AS Rejected
    FROM {TennesseeViolations}
      INNER JOIN {Drivers} USING(DriverID)
    WHERE TennesseeViolations.Status IN ('Exception', 'Resend')
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql));

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        if ('PROD' === $row['ViolationSource']) {
          $item = $row;
          $item['action'] = '';

          if ('Resend' === $item['ViolationStatus']) {
            $item['ViolationStatus'] = 'Queued';
          }

          $data[] = $item;
        }
      }
    }

    return $data;
  }

  /**
   *
   */
  private function loadTNViolationsReported() {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT TennesseeViolations.DriverID
      , Drivers.FullName AS CustomerName
      , Drivers.LicenseNumber AS DLN
      , TennesseeViolations.Occurred AS ViolationTimestamp
      , TennesseeViolations.Code AS ViolationCode
      , TennesseeViolations.Source AS ViolationSource
      , TennesseeViolations.Status AS ViolationStatus
      , CASE
        WHEN TennesseeViolations.Code = '*22' THEN 'Power Loss'
        WHEN TennesseeViolations.Code = '*24' THEN 'Unauthorized Start'
        ELSE TennesseeViolations.Code
      END AS Violation
      , TennesseeViolations.TennesseeViolationID AS TennesseeViolationID
    FROM {TennesseeViolations}
      INNER JOIN {Drivers} USING(DriverID)
    WHERE TennesseeViolations.Status = 'Reported'
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql));

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $data[] = $row;
      }
    }

    return $data;
  }

  /**
   *
   */
  private function loadTNViolationsIgnored() {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT TennesseeViolations.DriverID
      , Drivers.FullName AS CustomerName
      , Drivers.LicenseNumber AS DLN
      , TennesseeViolations.Occurred AS ViolationTimestamp
      , TennesseeViolations.Code AS ViolationCode
      , TennesseeViolations.Source AS ViolationSource
      , TennesseeViolations.Status AS ViolationStatus
      , CASE
        WHEN TennesseeViolations.Code = '*22' THEN 'Power Loss'
        WHEN TennesseeViolations.Code = '*24' THEN 'Unauthorized Start'
        ELSE TennesseeViolations.Code
      END AS Violation
      , TennesseeViolations.TennesseeViolationID AS TennesseeViolationID
    FROM {TennesseeViolations}
      INNER JOIN {Drivers} USING(DriverID)
    WHERE TennesseeViolations.Status = 'Ignored'
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql));

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $item = $row;
          $item['action'] = '';

          $data[] = $item;
      }
    }

    return $data;
  }

  /**
   * 
   */
  private function getStartCollarMarkup($did, $occurred, $code) {
    // Array to hold return data
    $data = [];

    // Only continue if code parameter is one we're expecting
    if (!in_array($code, ['*22', '*24'])) {
      return $data;
    }

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    $t1 = null;
    $t2 = null;

    // SQL to get first 18 timestamp from ads_prod
    $sql = <<<SQL
    SELECT MAX(BaiidEvents.Occurred) AS Occurred
    FROM {BaiidReports}
      INNER JOIN {BaiidEvents} ON (
        BaiidEvents.BaiidReportID = BaiidReports.BaiidReportID
        AND
        BaiidReports.DriverID = :did
        AND
        BaiidReports.Type = 'Details'
        AND
        BaiidEvents.EventCodeID = 25
        AND
        BaiidEvents.Occurred < :occurred
      )
SQL;

    // Run the query
    $result = $ads_prod->query(trim($sql), [':did' => $did, ':occurred' => $occurred]);

    if ($result) {
      $row = $result->fetchAssoc();
      $t1 = $row['Occurred'];
    }

    if (mb_strlen($t1) !== 19) {
      // SQL to get max timestamp from list of 50 events before
      $sql = <<<SQL
      SELECT MIN(T.Occurred) AS Occurred
      FROM (
        SELECT DISTINCT BaiidEvents.EventCodeID
          , BaiidEvents.Occurred
        FROM {BaiidReports}
          INNER JOIN {BaiidEvents} ON (
            BaiidEvents.BaiidReportID = BaiidReports.BaiidReportID
            AND
            BaiidReports.DriverID = :did
            AND
            BaiidReports.Type = 'Details'
            AND
            BaiidEvents.Occurred <= :occurred
          )
        ORDER BY BaiidEvents.Occurred DESC
        LIMIT 50
      ) AS T
SQL;

      // Run the query
      $result = $ads_prod->query(trim($sql), [':did' => $did, ':occurred' => $occurred]);

      if ($result) {
        $row = $result->fetchAssoc();
        $t1 = $row['Occurred'];
      }
    }

    // SQL to get second 18 timestamp from ads_prod
    $sql = <<<SQL
    SELECT MIN(BaiidEvents.Occurred) AS Occurred
    FROM {BaiidReports}
      INNER JOIN {BaiidEvents} ON (
        BaiidEvents.BaiidReportID = BaiidReports.BaiidReportID
        AND
        BaiidReports.DriverID = :did
        AND
        BaiidReports.Type = 'Details'
        AND
        BaiidEvents.EventCodeID = 25
        AND
        BaiidEvents.Occurred > :occurred
      )
SQL;

    // Run the query
    $result = $ads_prod->query(trim($sql), [':did' => $did, ':occurred' => $occurred]);

    if ($result) {
      $row = $result->fetchAssoc();
      $t2 = $row['Occurred'];
    }

    if (mb_strlen($t2) !== 19) {
      // SQL to get max timestamp from list of 50 events after
      $sql = <<<SQL
      SELECT MAX(T.Occurred) AS Occurred
      FROM (
        SELECT DISTINCT BaiidEvents.EventCodeID
          , BaiidEvents.Occurred
        FROM {BaiidReports}
          INNER JOIN {BaiidEvents} ON (
            BaiidEvents.BaiidReportID = BaiidReports.BaiidReportID
            AND
            BaiidReports.DriverID = :did
            AND
            BaiidReports.Type = 'Details'
            AND
            BaiidEvents.Occurred >= :occurred
          )
        ORDER BY BaiidEvents.Occurred ASC
        LIMIT 50
      ) AS T
SQL;

      // Run the query
      $result = $ads_prod->query(trim($sql), [':did' => $did, ':occurred' => $occurred]);

      if ($result) {
        $row = $result->fetchAssoc();
        $t2 = $row['Occurred'];
      }
    }

    // SQL to get timestamp of first indicator event
    $sql = <<<SQL
    SELECT MAX(BaiidEvents.Occurred) AS Occurred
    FROM {BaiidReports}
      INNER JOIN {BaiidEvents} ON (
        BaiidEvents.BaiidReportID = BaiidReports.BaiidReportID
        AND
        BaiidReports.DriverID = :did
        AND
        BaiidReports.Type = 'Details'
        AND
        BaiidEvents.EventCodeID = :ecid
        AND
        BaiidEvents.Occurred <= :occurred
      )
SQL;

    // Run the query
    if ($code == '*22') {
      $result = $ads_prod->query(trim($sql), [':did' => $did, ':ecid' => 66, ':occurred' => $occurred]);
    } elseif ($code == '*24') {
      $result = $ads_prod->query(trim($sql), [':did' => $did, ':ecid' => 120, ':occurred' => $occurred]);
    }

    $o1 = '';
    if ($result) {
      $row = $result->fetchAssoc();
      $o1 = $row['Occurred'];
    }

    // SQL to get timestamp of second indicator event
    $sql = <<<SQL
    SELECT MIN(BaiidEvents.Occurred) AS Occurred
    FROM {BaiidReports}
      INNER JOIN {BaiidEvents} ON (
        BaiidEvents.BaiidReportID = BaiidReports.BaiidReportID
        AND
        BaiidReports.DriverID = :did
        AND
        BaiidReports.Type = 'Details'
        AND
        BaiidEvents.EventCodeID = :ecid
        AND
        BaiidEvents.Occurred >= :occurred
      )
SQL;

    // Run the query
    if ($code == '*22' && !empty($o1)) {
      $result = $ads_prod->query(trim($sql), [':did' => $did, ':ecid' => 67, ':occurred' => $o1]);
    } elseif ($code == '*24' && !empty($o1)) {
      $result = $ads_prod->query(trim($sql), [':did' => $did, ':ecid' => 122, ':occurred' => $o1]);
    }

    $o2 = '';
    if ($result) {
      $row = $result->fetchAssoc();
      $o2 = $row['Occurred'];
    }

    // SQL to get events between 18 collar from ads_prod
    $sql = <<<SQL
    SELECT DISTINCT BaiidEvents.Occurred
      , EventCodes.Label
      , BaiidEventDetails.Description
    FROM {BaiidReports}
      INNER JOIN {BaiidEvents} ON (
        BaiidEvents.BaiidReportID = BaiidReports.BaiidReportID
        AND
        BaiidReports.DriverID = :did
        AND
        BaiidReports.Type = 'Details'
        AND
        BaiidEvents.Occurred BETWEEN :t1 AND :t2
      )
      INNER JOIN {EventCodes} USING(EventCodeID)
      INNER JOIN {BaiidEventDetails} USING(BaiidEventID)
    ORDER BY BaiidEvents.Occurred ASC
      , BaiidEvents.Sequence ASC
SQL;

    if(!is_null($t1) && !is_null($t2) && (mb_strlen($t1) == 19) && (mb_strlen($t2) == 19)) {
      // Run the query
      $result = $ads_prod->query(trim($sql), [':did' => $did, ':t1' => $t1, ':t2' => $t2]);

      if ($result) {
        $log = [];
        while ($row = $result->fetchAssoc()) {
          if (empty($log) && ($row['Label'] != '18')) {
            continue;
          }

          $str = '';
          $highlight = false;

          if (($row['Occurred'] == $occurred) && ($row['Label'] == $code)) {
            $highlight = true;
            $str .= "<strong style=\"color: red;\">";
          } elseif (
             (($code == '*22') && ($row['Label'] == '*08') && ($row['Occurred'] == $o1))
          || (($code == '*22') && ($row['Label'] == '*09') && ($row['Occurred'] == $o2))
          || (($code == '*24') && ($row['Label'] == '*40') && ($row['Occurred'] == $o1))
          || (($code == '*24') && ($row['Label'] == '*41') && ($row['Occurred'] == $o2))
          ) {
            $highlight = true;
            $str .= "<strong>";
          }

          $str .= $row['Occurred'] . '  ';
          $str .= str_pad($row['Label'], 3, ' ', STR_PAD_LEFT) . '  ';
          $str .= $row['Description'];

          if ($highlight) {
            $str .= "</strong>";
          }

          $log[] = $str;

          if(($row['Label'] == '18') && (count($log) > 1)) {
            break;
          }
        }

        $data[] = implode("\n", $log);
      }
    } else {
      $data[] = 'Error retrieving event log';
    }

    return $data;
  }

  private function doTNViolationStatusUpdate($did, $occurred, $code, $status) {
    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    if (isset($_SERVER['REQUEST_TIME']) && is_numeric($_SERVER['REQUEST_TIME'])) {
      $dtz = new \DateTimeZone('America/New_York');
      $dt = new \DateTime();
      $dt->setTimestamp($_SERVER['REQUEST_TIME']);
      $dt->setTimezone($dtz);
      $updated = $dt->format('Y-m-d H:i:s');
    } else {
      $updated = date('Y-m-d H:i:s', strtotime('now'));
    }

    $query = $ads_prod->update('TennesseeViolations')
      ->fields(['Status' => $status, 'Updated' => $updated])
      ->condition('DriverID', $did)
      ->condition('Occurred', $occurred)
      ->condition('Code', $code);

    return $query->execute();
  }



/* ================================================================
 * PUBLIC STATIC METHODS
 */

  /**
   *
   */
  public static function getTNViolations() {
    $violations = [];
    $violations['pending']    = self::loadTNViolationsPending();
    $violations['exceptions'] = self::loadTNViolationsExceptions();
    $violations['reported']   = self::loadTNViolationsReported();
    $violations['ignored']    = self::loadTNViolationsIgnored();
    return $violations;
  }



/* ================================================================
 * PUBLIC METHODS
 */

  /**
   *
   */
  public function getStartCollar($did, $occurred, $code) {
    return new JsonResponse(['data' => self::getStartCollarMarkup($did, $occurred, $code)]);
  }

  /**
   * 
   */
  public function updateTNViolationStatus($did, $occurred, $code, $status) {
    $affectedRows = self::doTNViolationStatusUpdate($did, $occurred, $code, $status);
    return new JsonResponse(sprintf('OK (%d)', $affectedRows));
  }
}
