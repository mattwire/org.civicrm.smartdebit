<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_DateUtils
 *
 * Date related functions for Smartdebit
 *
 */
class CRM_Smartdebit_DateUtils {

  /**
   * Calculate the earliest possible collection date based on today's date plus the collection interval setting.
   * From the selected collection day determine when the actual collection start date could be
   * For direct debit we need to allow 10 working days prior to collection for cooling off
   * We also may need to send them a letter etc
   *
   * @param int $collectionDay
   * @param bool $first
   *
   * @return \DateTime
   * @throws \Exception
   */
  public static function getNextAvailableCollectionDate($collectionDay = NULL, $first = FALSE) {
    if (empty($collectionDay)) {
      $collectionDay = CRM_Utils_Array::first(self::getCollectionDaysOptions(FALSE));
    }

    // Initialise date objects with today's date
    $today                    = new DateTime();
    $earliestCollectionDate   = new DateTime();
    $collectionDateThisMonth  = new DateTime();
    $collectionDateNextMonth  = new DateTime();
    $collectionDateMonthAfter = new DateTime();
    if ($first) {
      $noticePeriod = (int) CRM_Smartdebit_Settings::getValue('collection_interval');
    }
    else {
      $noticePeriod = (int) CRM_Smartdebit_Settings::getValue('notice_period');
    }

    // Calculate earliest possible collection date
    $earliestCollectionDate->add(new DateInterval( 'P'.($noticePeriod + 1).'D' ));

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
    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
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
   * @param bool $formatted
   *
   * @return array [1 => '1st'] or [1 => 1]
   */
  public static function getCollectionDaysOptions($formatted = TRUE) {
    $intervalDate = new DateTime();
    $interval = (int) CRM_Smartdebit_Settings::getValue('collection_interval');

    $intervalDate->modify("+$interval day");
    $intervalDay = $intervalDate->format('d');

    $collectionDays = CRM_Smartdebit_Settings::getValue('collection_days');

    // Split the array
    $tempCollectionDaysArray  = explode(',', $collectionDays);
    $earlyCollectionDaysArray = [];
    $lateCollectionDaysArray  = [];

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
    $collectionDaysArray = [];
    foreach ($allCollectionDays as $key => $value) {
      if ($formatted) {
        $collectionDaysArray[$value] = self::formatPreferredCollectionDay($value);
      }
      else {
        $collectionDaysArray[$value] = $value;
      }
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
    return [$unit, $interval];
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

    if ($recurParams['frequency_unit'] === 'month') {
      // If we are adding a monthly interval, cope with the situation where it's the last day of the month
      // Add a day on so we roll over to the next day of the month (or not)
      $day = $paymentDate->format('d');
      $year = $paymentDate->format('Y');
      $month = $paymentDate->format('m');
      $paymentDate->setDate($year, $month, 1);
    }
    $paymentDate->modify('+' . $recurParams['frequency_interval'] . ' ' . $recurParams['frequency_unit']);
    if ($recurParams['frequency_unit'] === 'month') {
      // Set the day
      $lastDayOfMonth = $paymentDate->format('t');
      if ($day > $lastDayOfMonth) {
        $day = $lastDayOfMonth;
      }
      $year = $paymentDate->format('Y');
      $month = $paymentDate->format('m');
      $paymentDate->setDate($year, $month, $day);
    }
    return $paymentDate->format('Ymd');
  }

  /**
   * Return difference between two dates in format
   *
   * @param string $date_1
   * @param string $date_2
   * @param string $differenceFormat (default days = %a)
   *
   * @return string
   */
  public static function dateDifference($date_1, $date_2, $differenceFormat = '%a') {
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
        $days = $frequencyInterval * 14;
        break;
      case 'year':
        $days = $frequencyInterval * 182;
        break;
      case 'lifetime':
        $days = 0;
        break;
      default:
        $days = 182;
        break;
    }
    return $days;
  }

  /**
   * Get a transaction ID in the format ABC123456/20190102000000 as required for smartdebit contributions in CiviCRM
   *
   * @param string $trxnId
   * @param string $receiveDate
   *
   * @return string
   */
  public static function getContributionTransactionId($trxnId, $receiveDate) {
    $receiveDate = CRM_Utils_Date::processDate(date('Y-m-d', strtotime($receiveDate)));
    return $trxnId . '/' . $receiveDate;
  }

}
