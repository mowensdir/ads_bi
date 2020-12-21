<?php

namespace Drupal\adsbi\Controller;

use Drupal\adsbi\Utils\AdsbiUtils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines the InventoryDataController class.
 */
class InventoryDataController extends ControllerBase {
/* ================================================================
 * PRIVATE STATIC METHODS
 */

  /**
   * Query ads_prod for the list of DriverID we are tracking for
   * equipment compliance
   *
   * @param bool $withPreassigned Include DriverID marked Preassigned?
   *
   * @return array
   */
  private static function getComplianceDIDs($withPreassigned = false) {
    // Array to hold return data
    $dids = [];

    // Decision SQL based on $withPreassigned param
    if ($withPreassigned) {
      $sql = 'SELECT DriverID FROM {InventoryCompliance}';
    } else {
      $sql = 'SELECT DriverID FROM {InventoryCompliance} WHERE Preassigned = 0';
    }

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run query
    $result = $ads_prod->query($sql);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $dids[] = $row['DriverID'];
      }
    }

    return $dids;
  }

  /**
   * Get list of compliance DriverID that have gone inactive
   *
   * @param bool $withPreassigned Include DriverID marked Preassigned?
   *
   * @return array
   */
  private static function getInactiveComplianceDIDs($withPreassigned = false) {
    // Array to hold return data
    $dids = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT DriverID
    FROM {InventoryCompliance}
    WHERE NOT EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = InventoryCompliance.DriverID
        AND
        Items.ProductID = 1
        AND
        Items.SerialNumber LIKE 'HH-%'
      )
      AND NOT EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = InventoryCompliance.DriverID
        AND
        Items.ProductID = 2
        AND
        Items.SerialNumber LIKE 'VM-%'
      )
SQL;

    // Modify query based on parameter value
    if (!$withPreassigned) {
      $sql .= '      AND Preassigned = 0';
    }

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run query
    $result = $ads_prod->query($sql);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $dids[] = $row['DriverID'];
      }
    }

    return $dids;
  }



/* ================================================================
 * PRIVATE METHODS
 */
  
  /**
   *
   */
  private function getUnresolved() {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , Drivers.LicenseJurisdiction AS DriverLicenseState
      , Drivers.BirthDate AS DriverDOB
      , Drivers.Phone AS DriverPhone1
      , Drivers.Fax AS DriverPhone2
      , Drivers.Email AS DriverEmail
      , Drivers.Address1 AS DriverAddress1
      , Drivers.Address2 AS DriverAddress2
      , Drivers.City AS DriverCity
      , Drivers.State AS DriverState
      , Drivers.Zip AS DriverZip
      , Drivers.ProbationEnd AS DriverProbationEnd
      , Dealers.CompanyName AS DealerName
      , Dealers.State AS DealerState
      , Dealers.Class AS DealerClass
      , Distributors.Companyname AS DistributorName
      , Territories.Label AS TerritoryName
      , Territories.State AS TerritoryState
      , Territories.ConfigServiceDue AS ConfigServiceDue
      , InventoryCompliance.Preassigned AS Preassigned
      , COALESCE(InventoryCompliance.ShipDate, '') AS ShipDate
      , COALESCE(GROUP_CONCAT(DISTINCT HH.SerialNumber ORDER BY HH.SerialNumber ASC SEPARATOR ', '), 'N/A') AS HHSN
      , COALESCE(GROUP_CONCAT(DISTINCT VM.SerialNumber ORDER BY VM.SerialNumber ASC SEPARATOR ', '), 'N/A') AS VMSN
      , (SELECT DATE(MIN(Imported)) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID) AS InstallDate
      , COALESCE((SELECT DATE(MAX(Imported)) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID AND Type = 'Details'), 'N/A') AS LastDownload
      , COALESCE((SELECT SerialNumber FROM {BaiidReports} WHERE DriverID = Drivers.DriverID AND Type = 'Details' ORDER BY Imported DESC LIMIT 1), 'N/A') AS LastEquipment
    FROM {Drivers}
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
      INNER JOIN {InventoryCompliance} USING(DriverID)
      LEFT JOIN {Items} AS HH ON (
        HH.DriverID = Drivers.DriverID
        AND
        HH.ProductID = 1
        AND
        HH.SerialNumber LIKE 'HH-%'
      )
      LEFT JOIN {Items} AS VM ON (
        VM.DriverID = Drivers.DriverID
        AND
        VM.ProductID = 2
        AND
        VM.SerialNumber LIKE 'VM-%'
      )
    WHERE Drivers.DriverID IN (:dids[])
      AND EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = InventoryCompliance.DriverID
          AND Items.ProductID IN (1, 2)
          AND (Items.SerialNumber LIKE 'HH-%' OR Items.SerialNumber LIKE 'VM-%')
      )
      AND (
        (
          InventoryCompliance.Preassigned = 0
          AND (
            EXISTS (
              SELECT NULL
              FROM {Items}
              WHERE Items.ProductID = 1
                AND Items.SerialNumber LIKE 'HH-%'
                AND SUBSTRING(Items.SerialNumber FROM 4) < 23000
                AND Items.SerialNumber = HH.SerialNumber
                AND Items.DriverID = Drivers.DriverID
            )
            OR EXISTS (
              SELECT NULL
              FROM {Items}
              WHERE Items.ProductID = 2
                AND Items.SerialNumber LIKE 'VM-%'
                AND SUBSTRING(Items.SerialNumber FROM 4) < 13300
                AND Items.SerialNumber = VM.SerialNumber
                AND Items.DriverID = Drivers.DriverID
            )
          )
        ) OR (
          InventoryCompliance.Preassigned = 1
          AND
          InventoryCompliance.ComplianceDate IS NULL
        )
      )
    GROUP BY Drivers.DriverID
      , Drivers.FullName
      , Drivers.LicenseNumber
      , Drivers.LicenseJurisdiction
      , Drivers.BirthDate
      , Drivers.Phone
      , Drivers.Fax
      , Drivers.Email
      , Drivers.Address1
      , Drivers.Address2
      , Drivers.City
      , Drivers.State
      , Drivers.Zip
      , Drivers.ProbationEnd
      , Dealers.CompanyName
      , Dealers.State
      , Dealers.Class
      , Distributors.Companyname
      , Territories.Label
      , Territories.State
      , Territories.ConfigServiceDue
      , InventoryCompliance.Preassigned
      , InventoryCompliance.ShipDate
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => self::getComplianceDIDs(true)]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $item = [
          'id'          => $row['DriverID'],
          'drName'      => $row['DriverName'],
          'drDLN'       => $row['DriverLicenseNumber'],
          'drEmail'     => $row['DriverEmail'],
          'drPhone'     => $row['DriverPhone1'],
          'tName'       => $row['TerritoryName'],
          'tState'      => $row['TerritoryState'],
          'deName'      => $row['DealerName'],
          'install'     => $row['InstallDate'],
          'shipped'     => $row['ShipDate'],
          'preassigned' => $row['Preassigned'],
          'hhsn'        => $row['HHSN'],
          'vmsn'        => $row['VMSN'],
          'lastHH'      => '',
          'lastVM'      => '',
        ];

        // Populate last service columns
        if ('N/A' === $row['LastDownload']) {
          $item['lastService'] = $row['InstallDate'];
        } else {
          $item['lastService'] = $row['LastDownload'];
          foreach (explode(' ', trim($row['LastEquipment'])) as $piece) {
            if ('HH' === substr($piece, 0, 2)) {
              $item['lastHH'] = $piece;
            } elseif ('VM' === substr($piece, 0, 2)) {
              $item['lastVM'] = $piece;
            }
          }
        }

        // Get days since last service
        $item['daysSince'] = floor((time() - strtotime($item['lastService'])) / 60 / 60 / 24);

        // Compute Next Service Date
        if ('KS' === $item['tState']) {
          if ('2020-08-22' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 30));
          }
        } elseif ('IL' === $item['tState']) {
          if ('2020-08-28' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 60));
          }
        } elseif ('OR' === $item['tState']) {
          if ('2020-09-29' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 60));
          }
        } elseif ('TN' === $item['tState']) {
          if ('2020-10-01' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 30));
          }
        } elseif ('CT' === $item['tState']) {
          if ('2020-11-04' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 30));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 60));
          }
        } else {
          $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * intval($row['ConfigServiceDue'])));
        }

        // Determine HH compliance
        $cHH  = false;
        $ncHH = false;
        foreach (explode(', ', $row['HHSN']) as $sn) {
          $num = intval(substr($sn, 3));
          if (23000 <= $num) {
            $cHH = true;
          } else {
            $ncHH = true;
          }
        }

        if ($cHH && $ncHH) {
          $item['statusHH'] = 'P';
        } elseif ($ncHH) {
          $item['statusHH'] = 'N';
        } elseif ($cHH) {
          $item['statusHH'] = 'C';
        } else {
          $item['statusHH'] = '';
        }

        // Determine VM compliance
        $cVM  = false;
        $ncVM = false;
        foreach (explode(', ', $row['VMSN']) as $sn) {
          $num = intval(substr($sn, 3));
          if (13300 <= $num) {
            $cVM = true;
          } else {
            $ncVM = true;
          }
        }

        if ($cVM && $ncVM) {
            $item['statusVM'] = 'P';
        } elseif ($ncVM) {
            $item['statusVM'] = 'N';
        } elseif ($cVM) {
            $item['statusVM'] = 'C';
        } else {
            $item['statusVM'] = '';
        }

        // Determine group membership
        if ($ncHH && $ncVM) {
          $item['group'] = 'NCHHVM';
        } elseif ($ncHH) {
          $item['group'] = 'NCHH';
        } elseif ($ncVM) {
          $item['group'] = 'NCVM';
        }

        // Add to data array
        $data[] = $item;
      }
    }

    return $data;
  }

  /**
   *
   */
  private function getResolved() {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT InventoryCompliance.DriverID
      , InventoryCompliance.Preassigned
      , COALESCE(InventoryCompliance.ComplianceDate, '') AS ComplianceDate
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , Dealers.CompanyName AS DealerName
      , Dealers.State AS DealerState
      , Dealers.Class AS DealerClass
      , Distributors.Companyname AS DistributorName
      , Territories.Label AS TerritoryName
      , Territories.State AS TerritoryState
      , COALESCE(InventoryCompliance.ShipDate, '') AS ShipDate
      , COALESCE(InventoryCompliance.ComplianceDate, DATE(MAX(BaiidReports.Imported))) AS ResolvedDate
    FROM {InventoryCompliance}
      INNER JOIN {BaiidReports} USING(DriverID)
      INNER JOIN {Drivers} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
    WHERE BaiidReports.Type IN ('Replacement', 'Installation', 'Repair', 'Removal')
      AND (
        (InventoryCompliance.Preassigned = 1 AND InventoryCompliance.ComplianceDate IS NOT NULL)
        OR
        (InventoryCompliance.Preassigned = 0)
      )
      AND EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = InventoryCompliance.DriverID
          AND Items.ProductID IN (1, 2)
          AND (Items.SerialNumber LIKE 'HH-%' OR Items.SerialNumber LIKE 'VM-%')
      )
      AND NOT EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = InventoryCompliance.DriverID
          AND Items.ProductID = 1
          AND Items.SerialNumber LIKE 'HH-%'
          AND SUBSTRING(Items.SerialNumber FROM 4) < 23000
      )
      AND NOT EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = InventoryCompliance.DriverID
          AND Items.ProductID = 2
          AND Items.SerialNumber LIKE 'VM-%'
          AND SUBSTRING(Items.SerialNumber FROM 4) < 13300
      )
    GROUP BY InventoryCompliance.DriverID
      , InventoryCompliance.Preassigned
      , InventoryCompliance.ComplianceDate
      , Drivers.FullName
      , Drivers.LicenseNumber
      , Dealers.CompanyName
      , Dealers.State
      , Dealers.Class
      , Distributors.Companyname
      , Territories.Label
      , Territories.State
      , InventoryCompliance.ShipDate
    ORDER BY ComplianceDate DESC
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql));

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $data[] = [
          'id'       => $row['DriverID'],
          'drName'   => $row['DriverName'],
          'drDLN'    => $row['DriverLicenseNumber'],
          'tName'    => $row['TerritoryName'],
          'tState'   => $row['TerritoryState'],
          'deName'   => $row['DealerName'],
          'shipped'  => $row['ShipDate'],
          'resolved' => $row['ResolvedDate'],
        ];

        if (!$row['Preassigned'] && empty($row['ComplianceDate']) && ('2020-07-01' < $row['ResolvedDate'])) {
          $ads_prod->update('InventoryCompliance')
            ->fields(['ComplianceDate' => $row['ResolvedDate']])
            ->condition('DriverID', $row['DriverID'])
            ->execute();
        }
      }
    }

    return $data;
  }

  /**
   *
   */
  private function getRemoved() {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , Dealers.CompanyName AS DealerName
      , Dealers.State AS DealerState
      , Dealers.Class AS DealerClass
      , Distributors.Companyname AS DistributorName
      , Territories.Label AS TerritoryName
      , Territories.State AS TerritoryState
      , (SELECT DATE(MAX(Imported)) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID) AS RemovalDate
    FROM {Drivers}
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
    WHERE Drivers.DriverID IN (:dids[])
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => self::getInactiveComplianceDIDs(true)]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $data[] = [
          'id'      => $row['DriverID'],
          'drName'  => $row['DriverName'],
          'drDLN'   => $row['DriverLicenseNumber'],
          'tName'   => $row['TerritoryName'],
          'tState'  => $row['TerritoryState'],
          'deName'  => $row['DealerName'],
          'removal' => $row['RemovalDate'],
        ];
      }
    }

    return $data;
  }

  /**
   *
   */
  private function getStatesBDStub() {
    // Array to hold return data
    $data = [];

    $sql = <<<SQL
    SELECT Territories.State AS State
      , COUNT(DISTINCT InventoryCompliance.DriverID) AS Cases
    FROM {InventoryCompliance}
      INNER JOIN {Drivers} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
    GROUP BY Territories.State
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql));

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $data[$row['State']] = [
          'state'      => $row['State'],
          'cases'      => $row['Cases'],
          'open'       => 0,
          'shipped'    => self::getTerritoryStateShipped($row['State']),
          'resolved'   => 0,
          'removed'    => 0,
          'nchhvm'     => 0,
          'nchh'       => 0,
          'ncvm'       => 0,
          'drivers'    => self::getTerritoryStateActiveDrivers($row['State']),
          'compliance' => 0,
        ];
      }
    }

    return $data;
  }

  /**
   *
   */
  private function getTerritoryStateActiveDrivers($state) {
    $active = 0;

    $sql = <<<SQL
    SELECT COUNT(DISTINCT Drivers.DriverID) AS ActiveDrivers
    FROM {Items}
      INNER JOIN {Drivers} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
    WHERE Items.ProductID = 1
      AND Items.SerialNumber LIKE 'HH-%'
      AND Territories.State = :state
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':state' => $state]);

    if ($result) {
      $row = $result->fetchAssoc();
      $active = $row['ActiveDrivers'];
    }

    return $active;
  }

  /**
   *
   */
  private function getTerritoryStateShipped($state) {
    $shipped = 0;

    $sql = <<<SQL
    SELECT COUNT(DISTINCT Drivers.DriverID) AS TotalShipped
    FROM {InventoryCompliance}
      INNER JOIN {Drivers} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
    WHERE InventoryCompliance.ShipDate IS NOT NULL
      AND Territories.State = :state
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':state' => $state]);

    if ($result) {
      $row = $result->fetchAssoc();
      $shipped = $row['TotalShipped'];
    }

    return $shipped;
  }

  /**
   *
   */
  private function getKSSwapCases($retail) {
    // Array to hold return data
    $cases = [];

    // SQL to retrieve all of the cases currently on our radar
    $sql = <<<SQL
    SELECT InventoryACS.DriverID
      , InventoryACS.Ignore
    FROM {InventoryACS}
      INNER JOIN {Drivers} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
SQL;

    if ($retail) {
      $sql .= " WHERE Dealers.Class = 'R'";
    } else {
      $sql .= " WHERE NOT Dealers.Class = 'R'";
    }

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query($sql, [':retail' => ($retail ? 1 : 0)]);

    $dids = [];
    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $did = intval($row['DriverID']);

        $dids[] = $did;

        if (0 == $row['Ignore']) {
          $cases[] = $did;
        }
      }
    }

    if (empty($dids)) {
      $dids[] = 0;
    }

    // SQL to look for cases not on our radar
    $sql = <<<SQL
    SELECT Drivers.DriverID
    FROM {Drivers}
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
    WHERE Distributors.TerritoryID IN (58, 139, 197)
      AND Drivers.DriverID NOT IN (:dids[])
      AND EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = Drivers.DriverID
          AND Items.ProductID = 1
          AND Items.SerialNumber LIKE 'HH-%'
      )
SQL;

    if ($retail) {
      $sql .= " AND Dealers.Class = 'R'";
    } else {
      $sql .= " AND NOT Dealers.Class = 'R'";
    }

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $dids]);

    if ($result) {
      $new = [];
      while ($row = $result->fetchAssoc()) {
        $new[] = intval($row['DriverID']);
      }

      if (!empty($new)) {
        foreach ($new as $did) {
          $sql = <<<SQL
          SELECT COUNT(DISTINCT Items.SerialNumber) AS NoncompliantDevices
          FROM {Items}
          WHERE Items.DriverID = :did
            AND Items.ProductID = 1
            AND Items.SerialNumber LIKE 'HH-%'
            AND (Items.ACS = 0 OR Items.ACS IS NULL)
SQL;

          // Run the query
          $result = $ads_prod->query(trim($sql), [':did' => $did]);

          if ($result) {
            $row = $result->fetchAssoc();
            $ignore = (0 == $row['NoncompliantDevices']) ? 1 : 0;
            $ads_prod->insert('InventoryACS')
              ->fields([
                'DriverID' => $did,
                'Ignore'   => $ignore,
              ])
              ->execute();

            if (!$ignore) {
              $cases[] = $did;
            }
          }
        }
      }
    }

    return $cases;
  }

  /**
   *
   */
  private function getKSSwapsData($cases) {
    $numCases = count($cases);

    $shipped = self::getKSSwapCasesShipped($cases);

    $removed = self::getKSSwapCasesRemoved($cases);

    $resolved = self::getKSSwapCasesResolved($cases);

    $openCases = [];
    for ($i = 0; $i < $numCases; $i++) {
      if (!in_array($cases[$i], array_keys($removed)) && !in_array($cases[$i], array_keys($resolved))) {
        $openCases[] = $cases[$i];
      }
    }

    $unresolved = self::getKSSwapCasesUnresolved($openCases);

    return [
      'cases'      => $numCases,
      'shipped'    => $shipped,
      'unresolved' => $unresolved,
      'resolved'   => array_values($resolved),
      'removed'    => array_values($removed),
    ];
  }

  /**
   *
   */
  private function getKSSwapCasesShipped($cases) {
    // Array to hold return data
    $shipped = [];

    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Dealers.CompanyName AS DealerName
    FROM {InventoryACS}
      INNER JOIN {Drivers} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
    WHERE InventoryACS.ShipDate IS NOT NULL
      AND InventoryACS.DriverID IN (:dids[])
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $shipped[] = $row;
      }
    }

    return $shipped;
  }

  /**
   *
   */
  private function getKSSwapCasesRemoved($cases) {
    // Array to hold return data
    $removed = [];

    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , DATE(MAX(BaiidReports.Imported)) AS RemovalDate
      , Dealers.CompanyName AS DealerName
    FROM {Drivers}
      INNER JOIN {BaiidReports} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
    WHERE Drivers.DriverID IN (:dids[])
      AND NOT EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = Drivers.DriverID
          AND Items.ProductID = 1
          AND Items.SerialNumber LIKE 'HH-%'
      )
    GROUP BY Drivers.DriverID
      , Drivers.FullName
      , Drivers.LicenseNumber
      , Dealers.CompanyName
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $removed[$row['DriverID']] = $row;
      }
    }

    return $removed;
  }

  /**
   *
   */
  private function getKSSwapCasesResolved($cases) {
    // Array to hold return data
    $resolved = [];

    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , COALESCE(InventoryACS.ShipDate, '') AS ShipDate
      , COALESCE(InventoryACS.SwapDate, '') AS SwapDate
      , Dealers.CompanyName AS DealerName
      , DATE(MAX(BaiidReports.Imported)) AS ServiceDate
    FROM {Drivers}
      INNER JOIN {BaiidReports} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {InventoryACS} USING(DriverID)
    WHERE Drivers.DriverID IN (:dids[])
      AND EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = Drivers.DriverID
          AND Items.ProductID = 1
          AND Items.SerialNumber LIKE 'HH-%'
          AND Items.ACS = 1
      )
      AND NOT EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = Drivers.DriverID
          AND Items.ProductID = 1
          AND Items.SerialNumber LIKE 'HH-%'
          AND (Items.ACS = 0 OR Items.ACS IS NULL)
      )
    GROUP BY Drivers.DriverID
      , Drivers.FullName
      , Drivers.LicenseNumber
      , InventoryACS.ShipDate
      , InventoryACS.SwapDate
      , Dealers.CompanyName
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        if (empty($row['SwapDate'])) {
          $ads_prod->update('InventoryACS')
            ->fields(['SwapDate' => $row['ServiceDate']])
            ->condition('DriverID', $row['DriverID'])
            ->execute();

          $row['SwapDate'] = $row['ServiceDate'];
        }

        $resolved[$row['DriverID']] = $row;
      }
    }

    return $resolved;
  }

  /**
   *
   */
  private function getKSSwapCasesUnresolved($cases) {
    // Array to hold device map
    $sn2ACS = [];
    $sql = <<<SQL
    SELECT DISTINCT Items.SerialNumber
      , COALESCE(Items.ACS, 0) AS ACS
    FROM {Items}
    WHERE Items.ProductID = 1
      AND Items.SerialNumber LIKE 'HH-%'
      AND Items.DriverID IN (:dids[])
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $sn2ACS[$row['SerialNumber']] = $row['ACS'];
      }
    }

    // Array to hold return data
    $unresolved = [];

    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , Drivers.LicenseJurisdiction AS DriverLicenseState
      , Drivers.BirthDate AS DriverDOB
      , Drivers.Phone AS DriverPhone1
      , Drivers.Fax AS DriverPhone2
      , Drivers.Email AS DriverEmail
      , Drivers.Address1 AS DriverAddress1
      , Drivers.Address2 AS DriverAddress2
      , Drivers.City AS DriverCity
      , Drivers.State AS DriverState
      , Drivers.Zip AS DriverZip
      , Drivers.ProbationEnd AS DriverProbationEnd
      , Dealers.CompanyName AS DealerName
      , Dealers.Class AS DealerClass
      , Distributors.Companyname AS DistributorName
      , Territories.Label AS TerritoryName
      , Territories.State AS TerritoryState
      , Territories.ConfigServiceDue AS ConfigServiceDue
      , COALESCE(InventoryACS.ShipDate, '') AS ShipDate
      , COALESCE(InventoryACS.SwapDate, '') AS SwapDate
      , COALESCE(GROUP_CONCAT(DISTINCT HH.SerialNumber ORDER BY HH.SerialNumber ASC SEPARATOR ', '), 'N/A') AS HHSN
      , COALESCE(GROUP_CONCAT(DISTINCT VM.SerialNumber ORDER BY VM.SerialNumber ASC SEPARATOR ', '), 'N/A') AS VMSN
      , (SELECT DATE(MIN(Imported)) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID) AS InstallDate
      , COALESCE((SELECT DATE(MAX(Imported)) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID AND Type = 'Details'), 'N/A') AS LastDownload
      , COALESCE((SELECT SerialNumber FROM {BaiidReports} WHERE DriverID = Drivers.DriverID AND Type = 'Details' ORDER BY Imported DESC LIMIT 1), 'N/A') AS LastEquipment
    FROM {Drivers}
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
      INNER JOIN {InventoryACS} USING(DriverID)
      LEFT JOIN {Items} AS HH ON (
        HH.DriverID = Drivers.DriverID
        AND
        HH.ProductID = 1
        AND
        HH.SerialNumber LIKE 'HH-%'
      )
      LEFT JOIN {Items} AS VM ON (
        VM.DriverID = Drivers.DriverID
        AND
        VM.ProductID = 2
        AND
        VM.SerialNumber LIKE 'VM-%'
      )
    WHERE Drivers.DriverID IN (:dids[])
    GROUP BY Drivers.DriverID
      , Drivers.FullName
      , Drivers.LicenseNumber
      , Drivers.LicenseJurisdiction
      , Drivers.BirthDate
      , Drivers.Phone
      , Drivers.Fax
      , Drivers.Email
      , Drivers.Address1
      , Drivers.Address2
      , Drivers.City
      , Drivers.State
      , Drivers.Zip
      , Drivers.ProbationEnd
      , Dealers.CompanyName
      , Dealers.Class
      , Distributors.Companyname
      , Territories.Label
      , Territories.State
      , Territories.ConfigServiceDue
      , InventoryACS.ShipDate
      , InventoryACS.SwapDate
SQL;

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $item = [
          'id'          => $row['DriverID'],
          'drName'      => $row['DriverName'],
          'drDLN'       => $row['DriverLicenseNumber'],
          'drEmail'     => $row['DriverEmail'],
          'drPhone'     => $row['DriverPhone1'],
          'tName'       => $row['TerritoryName'],
          'deName'      => $row['DealerName'],
          'install'     => $row['InstallDate'],
          'shipped'     => $row['ShipDate'],
          'vmsn'        => $row['VMSN'],
          'hhsn'        => '',
          'lastHH'      => '',
        ];

        if (!empty($row['SwapDate'])) {
          $ads_prod->update('InventoryACS')
            ->fields(['SwapDate' => null])
            ->condition('DriverID', $row['DriverID'])
            ->execute();
        }

        // Populate last service columns
        if ('N/A' === $row['LastDownload']) {
          $item['lastService'] = $row['InstallDate'];
        } else {
          $item['lastService'] = $row['LastDownload'];
          foreach (explode(' ', trim($row['LastEquipment'])) as $piece) {
            if ('HH' === substr($piece, 0, 2)) {
              $item['lastHH'] = $piece;
            }
          }
        }

        // Get days since last service
        $item['daysSince'] = floor((time() - strtotime($item['lastService'])) / 60 / 60 / 24);

        // Compute Next Service Date
        if ('KS' === $row['TerritoryState']) {
          if ('2020-08-22' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 30));
          }
        } elseif ('IL' === $row['TerritoryState']) {
          if ('2020-08-28' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 60));
          }
        } else {
          $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * intval($row['ConfigServiceDue'])));
        }

        // Determine HH compliance
        $cHH  = false;
        $ncHH = false;
        $hhsn = [];
        foreach (explode(', ', $row['HHSN']) as $sn) {
          $acs = null;
          if (isset($sn2ACS[$sn])) {
            $acs = $sn2ACS[$sn];
          } else {
            $query = $ads_prod->query('SELECT ACS FROM {Items} WHERE SerialNumber = :sn', [':sn' => $sn]);
            if ($query) {
              $r = $query->fetchAssoc();
              $acs = $r['ACS'];
            }
          }

          if (1 == $acs) {
            $cHH = true;
            $hhsn[] = sprintf('%s (Y)', $sn);
          } elseif (0 == $acs) {
            $ncHH = true;
            $hhsn[] = sprintf('%s (N)', $sn);
          } else {
            $hhsn[] = $sn;
          }
        }

        $item['hhsn'] = implode(', ', $hhsn);

        if ($cHH && $ncHH) {
          $item['statusHH'] = 'P';
        } elseif ($ncHH) {
          $item['statusHH'] = 'N';
        } elseif ($cHH) {
          $item['statusHH'] = 'C';
        } else {
          $item['statusHH'] = '';
        }

        $unresolved[] = $item;
      }
    }

    return $unresolved;
  }

  /**
   *
   */
  private function getDistributorUpgradeCases() {
    // Array to hold return data
    $cases = [];

    // SQL to retrieve all of the cases currently on our radar
    $sql = <<<SQL
    SELECT InventoryUpgrades.DriverID
      , InventoryUpgrades.Ignore
    FROM {InventoryUpgrades}
      INNER JOIN {Drivers} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query($sql);

    $dids = [];
    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $did = intval($row['DriverID']);

        $dids[] = $did;

        if (0 == $row['Ignore']) {
          $cases[] = $did;
        }
      }
    }

    if (empty($dids)) {
      $dids[] = 0;
    }

    // SQL to look for cases not on our radar
    $sql = <<<SQL
    SELECT DISTINCT Drivers.DriverID
    FROM {Drivers}
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
    WHERE Territories.State IN ('CA', 'IL', 'IN', 'KS', 'NE', 'OH')
      AND NOT Dealers.Class = 'R'
      AND Drivers.DriverID NOT IN (:dids[])
      AND EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = Drivers.DriverID
          AND (
            (Items.ProductID = 1 AND Items.SerialNumber LIKE 'HH-%')
            OR
            (Items.ProductID = 2 AND Items.SerialNumber LIKE 'VM-%')
          )
      )
SQL;

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $dids]);

    if ($result) {
      $new = [];
      while ($row = $result->fetchAssoc()) {
        $new[] = intval($row['DriverID']);
      }

      if (!empty($new)) {
        foreach ($new as $did) {
          $sql = <<<SQL
          SELECT COUNT(DISTINCT Items.SerialNumber) AS NoncompliantDevices
          FROM {Items}
          WHERE Items.DriverID = :did
            AND (
              (Items.ProductID = 1 AND Items.SerialNumber LIKE 'HH-%' AND SUBSTRING(Items.SerialNumber FROM 4) < 23000)
              OR
              (Items.ProductID = 2 AND Items.SerialNumber LIKE 'VM-%' AND SUBSTRING(Items.SerialNumber FROM 4) < 13300)
            )
SQL;

          // Run the query
          $result = $ads_prod->query(trim($sql), [':did' => $did]);

          if ($result) {
            $row = $result->fetchAssoc();
            $ignore = (0 == $row['NoncompliantDevices']) ? 1 : 0;
            $ads_prod->insert('InventoryUpgrades')
              ->fields([
                'DriverID' => $did,
                'Ignore'   => $ignore,
              ])
              ->execute();

            if (!$ignore) {
              $cases[] = $did;
            }
          }
        }
      }
    }

    return $cases;
  }

  /**
   *
   */
  private function getDistributorUpgradeCasesShipped($cases) {
    // Array to hold return data
    $shipped = [];

    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Dealers.CompanyName AS DealerName
      , Distributors.CompanyName AS DistributorName
    FROM {InventoryUpgrades}
      INNER JOIN {Drivers} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
    WHERE InventoryUpgrades.ShipDate IS NOT NULL
      AND InventoryUpgrades.DriverID IN (:dids[])
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $shipped[] = $row;
      }
    }

    return $shipped;
  }

  /**
   *
   */
  private function getDistributorUpgradeCasesRemoved($cases) {
    // Array to hold return data
    $removed = [];

    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , DATE(MAX(BaiidReports.Imported)) AS RemovalDate
      , Dealers.CompanyName AS DealerName
      , Distributors.CompanyName AS DistributorName
      , Territories.State AS TerritoryState
    FROM {Drivers}
      INNER JOIN {BaiidReports} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
    WHERE Drivers.DriverID IN (:dids[])
      AND NOT EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = Drivers.DriverID
          AND (
            (Items.ProductID = 1 AND Items.SerialNumber LIKE 'HH-%')
            OR
            (Items.ProductID = 2 AND Items.SerialNumber LIKE 'VM-%')
          )
      )
    GROUP BY Drivers.DriverID
      , Drivers.FullName
      , Drivers.LicenseNumber
      , Dealers.CompanyName
      , Distributors.CompanyName
      , Territories.State
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $removed[$row['DriverID']] = $row;
      }
    }

    return $removed;
  }

  /**
   *
   */
  private function getDistributorUpgradeCasesResolved($cases) {
    // Array to hold return data
    $resolved = [];

    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , COALESCE(InventoryUpgrades.ShipDate, '') AS ShipDate
      , COALESCE(InventoryUpgrades.ComplianceDate, '') AS ComplianceDate
      , Dealers.CompanyName AS DealerName
      , Distributors.CompanyName AS DistributorName
      , Territories.State AS TerritoryState
      , DATE(MAX(BaiidReports.Imported)) AS ServiceDate
    FROM {Drivers}
      INNER JOIN {BaiidReports} USING(DriverID)
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
      INNER JOIN {InventoryUpgrades} USING(DriverID)
    WHERE Drivers.DriverID IN (:dids[])
      AND EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = Drivers.DriverID
          AND (
            (Items.ProductID = 1 AND Items.SerialNumber LIKE 'HH-%' AND SUBSTRING(Items.SerialNumber FROM 4) >= 23000)
            OR
            (Items.ProductID = 2 AND Items.SerialNumber LIKE 'VM-%' AND SUBSTRING(Items.SerialNumber FROM 4) >= 13300)
          )
      )
      AND NOT EXISTS (
        SELECT NULL
        FROM {Items}
        WHERE Items.DriverID = Drivers.DriverID
          AND (
            (Items.ProductID = 1 AND Items.SerialNumber LIKE 'HH-%' AND SUBSTRING(Items.SerialNumber FROM 4) < 23000)
            OR
            (Items.ProductID = 2 AND Items.SerialNumber LIKE 'VM-%' AND SUBSTRING(Items.SerialNumber FROM 4) < 13300)
          )
      )
    GROUP BY Drivers.DriverID
      , Drivers.FullName
      , Drivers.LicenseNumber
      , InventoryUpgrades.ShipDate
      , InventoryUpgrades.ComplianceDate
      , Dealers.CompanyName
      , Distributors.CompanyName
      , Territories.State
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        if (empty($row['ComplianceDate'])) {
          $ads_prod->update('InventoryUpgrades')
            ->fields(['ComplianceDate' => $row['ServiceDate']])
            ->condition('DriverID', $row['DriverID'])
            ->execute();

          $row['ComplianceDate'] = $row['ServiceDate'];
        }

        $resolved[$row['DriverID']] = $row;
      }
    }

    return $resolved;
  }

  /**
   *
   */
  private function getDistributorUpgradeCasesUnresolved($cases) {
    // Array to hold return data
    $data = [];

    // SQL to retrieve info from ads_prod
    $sql = <<<SQL
    SELECT Drivers.DriverID
      , Drivers.FullName AS DriverName
      , Drivers.LicenseNumber AS DriverLicenseNumber
      , Drivers.LicenseJurisdiction AS DriverLicenseState
      , Drivers.BirthDate AS DriverDOB
      , Drivers.Phone AS DriverPhone1
      , Drivers.Fax AS DriverPhone2
      , Drivers.Email AS DriverEmail
      , Drivers.Address1 AS DriverAddress1
      , Drivers.Address2 AS DriverAddress2
      , Drivers.City AS DriverCity
      , Drivers.State AS DriverState
      , Drivers.Zip AS DriverZip
      , Drivers.ProbationEnd AS DriverProbationEnd
      , Dealers.CompanyName AS DealerName
      , Dealers.State AS DealerState
      , Dealers.Class AS DealerClass
      , Distributors.Companyname AS DistributorName
      , Territories.Label AS TerritoryName
      , Territories.State AS TerritoryState
      , Territories.ConfigServiceDue AS ConfigServiceDue
      , COALESCE(InventoryUpgrades.ShipDate, '') AS ShipDate
      , COALESCE(GROUP_CONCAT(DISTINCT HH.SerialNumber ORDER BY HH.SerialNumber ASC SEPARATOR ', '), 'N/A') AS HHSN
      , COALESCE(GROUP_CONCAT(DISTINCT VM.SerialNumber ORDER BY VM.SerialNumber ASC SEPARATOR ', '), 'N/A') AS VMSN
      , (SELECT DATE(MIN(Imported)) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID) AS InstallDate
      , COALESCE((SELECT DATE(MAX(Imported)) FROM {BaiidReports} WHERE DriverID = Drivers.DriverID AND Type = 'Details'), 'N/A') AS LastDownload
      , COALESCE((SELECT SerialNumber FROM {BaiidReports} WHERE DriverID = Drivers.DriverID AND Type = 'Details' ORDER BY Imported DESC LIMIT 1), 'N/A') AS LastEquipment
    FROM {Drivers}
      INNER JOIN {Dealers} USING(DealerID)
      INNER JOIN {Distributors} USING(DistributorID)
      INNER JOIN {Territories} USING(TerritoryID)
      INNER JOIN {InventoryUpgrades} USING(DriverID)
      LEFT JOIN {Items} AS HH ON (
        HH.DriverID = Drivers.DriverID
        AND
        HH.ProductID = 1
        AND
        HH.SerialNumber LIKE 'HH-%'
      )
      LEFT JOIN {Items} AS VM ON (
        VM.DriverID = Drivers.DriverID
        AND
        VM.ProductID = 2
        AND
        VM.SerialNumber LIKE 'VM-%'
      )
    WHERE Drivers.DriverID IN (:dids[])
    GROUP BY Drivers.DriverID
      , Drivers.FullName
      , Drivers.LicenseNumber
      , Drivers.LicenseJurisdiction
      , Drivers.BirthDate
      , Drivers.Phone
      , Drivers.Fax
      , Drivers.Email
      , Drivers.Address1
      , Drivers.Address2
      , Drivers.City
      , Drivers.State
      , Drivers.Zip
      , Drivers.ProbationEnd
      , Dealers.CompanyName
      , Dealers.State
      , Dealers.Class
      , Distributors.Companyname
      , Territories.Label
      , Territories.State
      , Territories.ConfigServiceDue
      , InventoryUpgrades.ShipDate
SQL;

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query(trim($sql), [':dids[]' => $cases]);

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $item = [
          'id'          => $row['DriverID'],
          'drName'      => $row['DriverName'],
          'drDLN'       => $row['DriverLicenseNumber'],
          'drEmail'     => $row['DriverEmail'],
          'drPhone'     => $row['DriverPhone1'],
          'tName'       => $row['TerritoryName'],
          'tState'      => $row['TerritoryState'],
          'deName'      => $row['DealerName'],
          'install'     => $row['InstallDate'],
          'shipped'     => $row['ShipDate'],
          'preassigned' => $row['Preassigned'],
          'hhsn'        => $row['HHSN'],
          'vmsn'        => $row['VMSN'],
          'lastHH'      => '',
          'lastVM'      => '',
        ];

        // Populate last service columns
        if ('N/A' === $row['LastDownload']) {
          $item['lastService'] = $row['InstallDate'];
        } else {
          $item['lastService'] = $row['LastDownload'];
          foreach (explode(' ', trim($row['LastEquipment'])) as $piece) {
            if ('HH' === substr($piece, 0, 2)) {
              $item['lastHH'] = $piece;
            } elseif ('VM' === substr($piece, 0, 2)) {
              $item['lastVM'] = $piece;
            }
          }
        }

        // Get days since last service
        $item['daysSince'] = floor((time() - strtotime($item['lastService'])) / 60 / 60 / 24);

        // Compute Next Service Date
        if ('KS' === $item['tState']) {
          if ('2020-08-22' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 30));
          }
        } elseif ('IL' === $item['tState']) {
          if ('2020-08-28' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 60));
          }
        } elseif ('OR' === $item['tState']) {
          if ('2020-09-29' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 60));
          }
        } elseif ('TN' === $item['tState']) {
          if ('2020-10-01' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 90));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 30));
          }
        } elseif ('CT' === $item['tState']) {
          if ('2020-11-04' > $item['lastService']) {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 30));
          } else {
            $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * 60));
          }
        } else {
          $item['nextService'] = date('Y-m-d', strtotime($item['lastService']) + (86400 * intval($row['ConfigServiceDue'])));
        }

        // Determine HH compliance
        $cHH  = false;
        $ncHH = false;
        foreach (explode(', ', $row['HHSN']) as $sn) {
          $num = intval(substr($sn, 3));
          if (23000 <= $num) {
            $cHH = true;
          } else {
            $ncHH = true;
          }
        }

        if ($cHH && $ncHH) {
          $item['statusHH'] = 'P';
        } elseif ($ncHH) {
          $item['statusHH'] = 'N';
        } elseif ($cHH) {
          $item['statusHH'] = 'C';
        } else {
          $item['statusHH'] = '';
        }

        // Determine VM compliance
        $cVM  = false;
        $ncVM = false;
        foreach (explode(', ', $row['VMSN']) as $sn) {
          $num = intval(substr($sn, 3));
          if (13300 <= $num) {
            $cVM = true;
          } else {
            $ncVM = true;
          }
        }

        if ($cVM && $ncVM) {
            $item['statusVM'] = 'P';
        } elseif ($ncVM) {
            $item['statusVM'] = 'N';
        } elseif ($cVM) {
            $item['statusVM'] = 'C';
        } else {
            $item['statusVM'] = '';
        }

        // Determine group membership
        if ($ncHH && $ncVM) {
          $item['group'] = 'NCHHVM';
        } elseif ($ncHH) {
          $item['group'] = 'NCHH';
        } elseif ($ncVM) {
          $item['group'] = 'NCVM';
        }

        $item['diName'] = $row['DistributorName'];

        // Add to data array
        $data[] = $item;
      }
    }

    return $data;
  }



/* ================================================================
 * PUBLIC STATIC METHODS
 */

  /**
   * Get the total number of compliance cases we're tracking
   *
   * @return int
   */
  public static function getTotalCases() {
    return number_format(count(self::getComplianceDIDs(true)));
  }

  /**
   * Get the number of compliance cases that have been resolved
   * via removal
   *
   * @return string
   */
  public static function getRemovedCases() {
    return number_format(count(self::getInactiveComplianceDIDs(true)));
  }

  /**
   *
   */
  public static function getTotalShipped() {
    $shipped = 0;

    $sql = 'SELECT COUNT(*) AS TotalShipped FROM {InventoryCompliance} WHERE ShipDate IS NOT NULL';

    // Get ads_prod database connection
    $ads_prod = Database::getConnection('default', 'ads_prod');

    // Run the query
    $result = $ads_prod->query($sql);

    if ($result) {
      $row = $result->fetchAssoc();
      $shipped = $row['TotalShipped'];
    }

    return number_format($shipped);
  }

  /**
   *
   */
  public static function getKSSwapsRetailData() {
    $cases = self::getKSSwapCases($retail = true);

    return self::getKSSwapsData($cases);
  }

  /**
   *
   */
  public static function getKSSwapsDistributorData() {
    $cases = self::getKSSwapCases($retail = false);

    return self::getKSSwapsData($cases);
  }

  /**
   *
   */
  public static function getDistributorUpgradeData() {
    $cases = self::getDistributorUpgradeCases();

    $numCases = count($cases);

    $shipped = self::getDistributorUpgradeCasesShipped($cases);

    $removed = self::getDistributorUpgradeCasesRemoved($cases);

    $resolved = self::getDistributorUpgradeCasesResolved($cases);

    $openCases = [];
    for ($i = 0; $i < $numCases; $i++) {
      if (!in_array($cases[$i], array_keys($removed)) && !in_array($cases[$i], array_keys($resolved))) {
        $openCases[] = $cases[$i];
      }
    }

    $unresolved = self::getDistributorUpgradeCasesUnresolved($openCases);

    $nchhvm = 0;
    $nchh   = 0;
    $ncvm   = 0;

    foreach ($unresolved as $row) {
      if ('NCHHVM' === $row['group']) {
        $nchhvm++;
      } elseif ('NCHH' === $row['group']) {
        $nchh++;
      } elseif ('NCVM' === $row['group']) {
        $ncvm++;
      }
    }

    return [
      'cases'      => $numCases,
      'shipped'    => $shipped,
      'unresolved' => $unresolved,
      'resolved'   => array_values($resolved),
      'removed'    => array_values($removed),
      'nchhvm'     => $nchhvm,
      'nchh'       => $nchh,
      'ncvm'       => $ncvm,
    ];
  }



/* ================================================================
 * PUBLIC METHODS
 */

  /**
   * Callback for /data/inventory/upgrades/unresolved
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function upgradesUnresolved() {
    return new JsonResponse(['data' => self::getUnresolved()]);
  }

  /**
   * Callback for /data/inventory/upgrades/resolved
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function upgradesResolved() {
    return new JsonResponse(['data' => self::getResolved()]);
  }

  /**
   * Callback for /data/inventory/upgrades/removed
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function upgradesRemoved() {
    return new JsonResponse(['data' => self::getRemoved()]);
  }

  /**
   * Callback for /data/inventory/upgrades/statebd
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function upgradesStateBD() {
    $data = [];

    $states     = self::getStatesBDStub();
    $removed    = self::getRemoved();
    $unresolved = self::getUnresolved();

    for ($i = 0; $i < count($removed); $i++) {
      $state = $removed[$i]['tState'];
      $states[$state]['removed']++;
    }

    for ($i = 0; $i < count($unresolved); $i++) {
      $case  = $unresolved[$i];
      $state = $case['tState'];
      $group = strtolower($case['group']);
      
      $states[$state]['open']++;
      $states[$state][$group]++;
    }

    foreach ($states as $state) {
      $state['resolved'] = $state['cases'] - $state['open'] - $state['removed'];
      $state['progress'] = sprintf("%.2f%%", (($state['resolved'] + $state['removed']) / $state['cases']) * 100);
      $data[] = $state;
    }

    return new JsonResponse(['data' => $data]);
  }
}
