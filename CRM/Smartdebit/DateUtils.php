<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +-------------------------------------------------------------------*/

/**
 * Class CRM_Smartdebit_DateUtils
 *
 * Date related functions for Smartdebit
 *
 */
class CRM_Smartdebit_DateUtils {

  /**
   * Calculate the earliest possible collection date based on todays date plus the collection interval setting.
   * @param $collectionDay
   *
   * @return DateTime
   * @throws \Exception
   */
  public static function firstCollectionDate($collectionDay) {
    // Initialise date objects with today's date
    $today                    = new DateTime();
    $earliestCollectionDate   = new DateTime();
    $collectionDateThisMonth  = new DateTime();
    $collectionDateNextMonth  = new DateTime();
    $collectionDateMonthAfter = new DateTime();
    $collectionInterval = (int) CRM_Smartdebit_Settings::getValue('collection_interval');

    // Calculate earliest possible collection date
    $earliestCollectionDate->add(new DateInterval( 'P'.$collectionInterval.'D' ));

    // Get the current year, month and next month to create the 2 potential collection dates
    $todaysMonth = (int) $today->format('m');
    $nextMonth   = (int) $today->format('m') + 1;
    $monthAfter  = (int) $today->format('m') + 2;
    $todaysYear  = (int) $today->format('Y');

    $collectionDateThisMonth->setDate($todaysYear, $todaysMonth, $collectionDay);
    $collectionDateNextMonth->setDate($todaysYear, $nextMonth, $collectionDay);
    $collectionDateMonthAfter->setDate($todaysYear, $monthAfter, $collectionDay);

    // Calculate first collection date
    if ($earliestCollectionDate > $collectionDateNextMonth) {
      // Month after next
      return $collectionDateMonthAfter;
    }
    elseif ($earliestCollectionDate > $collectionDateThisMonth) {
      // Next Month
      return $collectionDateNextMonth;
    }
    else {
      // This month
      return $collectionDateThisMonth;
    }
  }

  /**
   * Format collection day like 1st, 2nd, 3rd, 4th etc.
   *
   * @param $collectionDay
   *
   * @return string
   */
  public static function formatPreferredCollectionDay($collectionDay) {
    $ends = array( 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    if ((($collectionDay%100) >= 11) && (($collectionDay%100) <= 13)) {
      $abbreviation = $collectionDay . 'th';
    }
    else {
      $abbreviation = $collectionDay . $ends[$collectionDay % 10];
    }

    return $abbreviation;
  }

  /**
   * Function will return the possible array of collection days with formatted label
   *
   * @return mixed
   */
  public static function getCollectionDaysOptions() {
    $intervalDate = new DateTime();
    $interval = (int) CRM_Smartdebit_Settings::getValue('collection_interval');

    $intervalDate->modify("+$interval day");
    $intervalDay = $intervalDate->format('d');

    $collectionDays = CRM_Smartdebit_Settings::getValue('collection_days');

    // Split the array
    $tempCollectionDaysArray  = explode(',', $collectionDays);
    $earlyCollectionDaysArray = array();
    $lateCollectionDaysArray  = array();

    // Build 2 arrays around next collection date
    foreach($tempCollectionDaysArray as $key => $value){
      if ($value >= $intervalDay) {
        $earlyCollectionDaysArray[] = $value;
      }
      else {
        $lateCollectionDaysArray[]  = $value;
      }
    }
    // Merge arrays for select list
    $allCollectionDays = array_merge($earlyCollectionDaysArray, $lateCollectionDaysArray);

    // Loop through and format each label
    $collectionDaysArray = array();
    foreach($allCollectionDays as $key => $value){
      $collectionDaysArray[$value] = self::formatPreferredCollectionDay($value);
    }
    return $collectionDaysArray;
  }

  /**
   * Translate Smart Debit Frequency Unit/Factor to CiviCRM frequency unit/interval (eg. W,1 = day,7)
   * @param $sdFrequencyUnit
   * @param $sdFrequencyFactor
   *
   * @return array ($civicrm_frequency_unit, $civicrm_frequency_interval)
   */
  public static function translateSmartdebitFrequencytoCiviCRM($sdFrequencyUnit, $sdFrequencyFactor) {
    if (empty($sdFrequencyFactor)) {
      $sdFrequencyFactor = 1;
    }
    switch ($sdFrequencyUnit) {
      case 'W':
        $unit = 'day';
        $interval = $sdFrequencyFactor * 7;
        break;
      case 'M':
        $unit = 'month';
        $interval = $sdFrequencyFactor;
        break;
      case 'Q':
        $unit = 'month';
        $interval = $sdFrequencyFactor * 3;
        break;
      case 'Y':
      default:
        $unit = 'year';
        $interval = $sdFrequencyFactor;
    }
    return array ($unit, $interval);
  }

  /**
   * Get the next scheduled date for the recurring contribution
   *
   * @param string $paymentDateString
   * @param array $recurParams
   *
   * @return string
   */
  public static function getNextScheduledDate($paymentDateString, $recurParams) {
    $paymentDate = new DateTime($paymentDateString);
    $paymentDate->modify('+' . $recurParams['frequency_interval'] . ' ' . $recurParams['frequency_unit']);
    return $paymentDate->format('Ymd');
  }

  /**
   * Return difference between two dates in format
   *
   * @param string $date_1
   * @param string $date_2
   * @param string $differenceFormat
   *
   * @return string
   */
  public static function dateDifference($date_1, $date_2, $differenceFormat = '%a')
  {
    $datetime1 = date_create($date_1);
    $datetime2 = date_create($date_2);

    $interval = date_diff($datetime1, $datetime2);

    return $interval->format($differenceFormat);
  }

  /**
   * Function to return number of days difference to check between current date
   * and payment date to determine if this is first payment or not
   *
   * @param string $frequencyUnit
   * @param int $frequencyInterval
   *
   * @return int
   */
  public static function daysDifferenceForFrequency($frequencyUnit, $frequencyInterval) {
    switch ($frequencyUnit) {
      case 'day':
        $days = $frequencyInterval * 1;
        break;
      case 'month':
        $days = $frequencyInterval * 7;
        break;
      case 'year':
        $days = $frequencyInterval * 30;
        break;
      case 'lifetime':
        $days = 0;
        break;
      default:
        $days = 30;
        break;
    }
    return $days;
  }

}