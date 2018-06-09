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
 * Class CRM_Smartdebit_Form_Auddis
 *
 * Path: civicrm/smartdebit/syncsd/auddis
 * This displays an overview of matched/unmatched, failed and successful contributions from Smartdebit
 * Clicking next will allow the user to confirm import of this data
 * This is the third page in the import process (starting at civicrm/smartdebit/syncsd)
 */
class CRM_Smartdebit_Form_Auddis extends CRM_Core_Form {
  /*
 * Notification of failed debits and cancelled or amended DDIs are made available via Automated Direct Debit
 * Instruction Service (AUDDIS), Automated Return of Unpaid Direct Debit (ARUDD) files and Automated Direct Debit
 * Amendment and Cancellation (ADDACS) files. Notification of any claims relating to disputed Debits are made via
 * Direct Debit Indemnity Claim Advice (DDICA) reports.
 */

  function buildQuickForm() {
    $auddisIDs = array_filter(explode(',', urldecode(CRM_Utils_Request::retrieve('auddisID', 'String', $this, false))));
    $aruddIDs = array_filter(explode(',', urldecode(CRM_Utils_Request::retrieve('aruddID', 'String', $this, false))));

    // Display the rejected payments
    $newAuddisRecords = array();
    $rejectedIds = array();
    $counts['auddis'] = 0;
    $counts['auddis_matched'] = 0;
    $counts['auddis_amount'] = 0;
    if (!empty($auddisIDs)) {
      foreach ($auddisIDs as $auddisID) {
        $auddisFile = CRM_Smartdebit_Api::getAuddisFile($auddisID);
        unset($auddisFile['auddis_date']);
        foreach ($auddisFile as $inside => $value) {
          $sql = "
SELECT ctrc.id as contribution_recur_id, ctrc.contact_id, ctrc.amount, ctrc.trxn_id, ctrc.frequency_unit, ctrc.frequency_interval, contact.display_name
FROM civicrm_contribution_recur ctrc
LEFT JOIN civicrm_contact contact ON (ctrc.contact_id = contact.id)
WHERE ctrc.trxn_id = %1
          ";

          $params = array(1 => array($value['reference'], 'String'));
          $dao = CRM_Core_DAO::executeQuery($sql, $params);
          $rejectedIds[] = "'" . $value['reference'] . "' ";

          if ($dao->fetch()) {
            $newAuddisRecords[$counts['auddis']]['contribution_recur_id'] = $dao->contribution_recur_id;
            $newAuddisRecords[$counts['auddis']]['contact_id'] = $dao->contact_id;
            $newAuddisRecords[$counts['auddis']]['contact_name'] = $dao->display_name;
            $newAuddisRecords[$counts['auddis']]['start_date'] = date('Y-m-d', strtotime($value['effective-date']));
            $newAuddisRecords[$counts['auddis']]['frequency'] = $dao->frequency_interval . ' ' . $dao->frequency_unit;
            $newAuddisRecords[$counts['auddis']]['amount'] = $dao->amount;
            $newAuddisRecords[$counts['auddis']]['transaction_id'] = $dao->trxn_id;
            $counts['auddis_matched'] = 0;
          } else {
            $newAuddisRecords[$counts['auddis']]['contact_id'] = $value['payer-reference'];
            $newAuddisRecords[$counts['auddis']]['contact_name'] = $value['payer-name'];
            $newAuddisRecords[$counts['auddis']]['start_date'] = $value['effective-date'];
            $newAuddisRecords[$counts['auddis']]['contribution_recur_id'] = 0; // We use this in tpl to decide how to display contact name
            $newAuddisRecords[$counts['auddis']]['amount'] = 0;
          }
          $newAuddisRecords[$counts['auddis']]['reference'] = $value['reference'];
          $newAuddisRecords[$counts['auddis']]['reason'] = $value['reason-code'];
          $counts['auddis_amount'] += $newAuddisRecords[$counts['auddis']]['amount'];
          $counts['auddis']++;
        }
      }
    }

    $summary['Rejected Contributions in the auddis']['count'] = $counts['auddis'];
    $summary['Rejected Contributions in the auddis']['total'] = CRM_Utils_Money::format($counts['auddis_amount']);
    $this->assign('totalMatchedAuddisRejected', $counts['auddis_amount']);

    $newAruddRecords = array();
    $counts['arudd'] = 0;
    $counts['arudd_matched'] = 0;
    $counts['arudd_amount'] = 0;
    if (!empty($aruddIDs)) {
      foreach ($aruddIDs as $aruddID) {
        $aruddFile = CRM_Smartdebit_Api::getAruddFile($aruddID);
        $newAruddRecords[$counts['arudd']]['arudd_date'] = $aruddFile['arudd_date'];
        unset($aruddFile['arudd_date']);
        foreach ($aruddFile as $inside => $value) {
          $sql = "
SELECT ctrc.contact_id as contact_id, contact.display_name as display_name
FROM civicrm_contribution_recur ctrc
LEFT JOIN civicrm_contact contact ON (ctrc.contact_id = contact.id)
WHERE ctrc.trxn_id = %1
          ";

          $params = array(1 => array($value['ref'], 'String'));
          $dao = CRM_Core_DAO::executeQuery($sql, $params);
          $rejectedIds[] = "'" . $value['ref'] . "' ";
          if ($dao->fetch()) {
            $newAruddRecords[$counts['arudd']]['contact_id'] = $dao->contact_id;
            $newAruddRecords[$counts['arudd']]['contact_name'] = $dao->display_name;
            $counts['arudd_matched']++;
          } else {
            $newAruddRecords[$counts['arudd']]['contact_id'] = $value['payerReference'];
          }
          $newAruddRecords[$counts['arudd']]['date'] = $value['originalProcessingDate'];
          $newAruddRecords[$counts['arudd']]['amount'] = $value['valueOf'];
          $newAruddRecords[$counts['arudd']]['reference'] = $value['ref'];
          $newAruddRecords[$counts['arudd']]['reason'] = $value['returnDescription'];
          $counts['arudd_amount'] += $newAruddRecords[$counts['arudd']]['amount'];
          $counts['arudd']++;
        }
      }
    }

    $summary['Rejected Contributions in the arudd']['count'] = $counts['arudd'];
    $summary['Rejected Contributions in the arudd']['total'] = CRM_Utils_Money::format($counts['arudd_amount']);

    $listArray = array();
    // Display the valid payments
    $contributionTrxnIdsList = "'dummyId'";
    $sdTrxnIds = array();
    $selectQuery = "SELECT `transaction_id` as trxn_id, receive_date as receive_date FROM `" . CRM_Smartdebit_CollectionReports::TABLENAME . "`";
    $dao = CRM_Core_DAO::executeQuery($selectQuery);
    while ($dao->fetch()) {
      $sdTrxnIds[] = "'" . $dao->trxn_id . "' ";
      $contributionTrxnIdsList .= ", '" . $dao->trxn_id . '/' . $dao->receive_date . "' ";
    }

    $contributionQuery = "
        SELECT cc.contact_id, cc.total_amount, cc.trxn_id as cc_trxn_id, ctrc.trxn_id as ctrc_trxn_id
        FROM `civicrm_contribution` cc
        INNER JOIN civicrm_contribution_recur ctrc ON (ctrc.id = cc.contribution_recur_id)
        WHERE cc.`trxn_id` IN ( $contributionTrxnIdsList )";

    $dao = CRM_Core_DAO::executeQuery($contributionQuery);
    $recurTransactionIds = array();
    $matchTrxnIds = array();
    $missingArray = array();
    while ($dao->fetch()) {
      $recurTransactionIds[] = "'" . trim($dao->ctrc_trxn_id) . "' "; //MV: trim the whitespaces and match the transaction_id.
    }
    // Get all transaction IDs that don't have recurring contributions in CiviCRM or AUDDIS/ARUDD rejections.
    $validIds = array_diff($sdTrxnIds, $recurTransactionIds, $rejectedIds);

    if (!empty($validIds)) {
      $counts['contribution_matched'] = 0;
      $counts['contribution_matched_amount'] = 0;

      // Get list of transactionIDs that have matching recurring contributions in CiviCRM and Smartdebit
      $validIdsString = implode(',', $validIds);
      $sql = "
SELECT ctrc.id contribution_recur_id, ctrc.contact_id, cont.display_name, ctrc.trxn_id, ctrc.frequency_unit, ctrc.payment_instrument_id, 
  ctrc.contribution_status_id, ctrc.frequency_interval, sdpayments.amount as sd_amount, sdpayments.receive_date as sd_receive_date
FROM civicrm_contribution_recur ctrc
INNER JOIN " . CRM_Smartdebit_CollectionReports::TABLENAME . " sdpayments ON sdpayments.transaction_id = ctrc.trxn_id
INNER JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id)
WHERE ctrc.trxn_id IN ($validIdsString)
      ";
      $dao = CRM_Core_DAO::executeQuery($sql);

      while ($dao->fetch()) {
        $matchTrxnIds[] = "'" . trim($dao->trxn_id) . "' ";
        $params = array(
          'contribution_recur_id' => $dao->contribution_recur_id,
          'contact_id' => $dao->contact_id,
          'contact_name' => $dao->display_name,
          'receive_date' => date('Y-m-d', strtotime($dao->sd_receive_date)),
          'frequency' => $dao->frequency_interval . ' ' . $dao->frequency_unit,
          'amount' => $dao->sd_amount,
          'contribution_status_id' => $dao->contribution_status_id,
          'transaction_id' => $dao->trxn_id,
        );

        $listArray[$counts['contribution_matched']] = $params;
        $counts['contribution_matched']++;
        $counts['contribution_matched_amount'] += $dao->sd_amount;
      }
      //Store the list of matched transaction IDs in settings table for use later
      if (!empty($matchTrxnIds)) {
        CRM_Smartdebit_Settings::save(array('result_ids' => $matchTrxnIds));
      }
    }

    // Find the contributions that have already been processed
    $contributionQuery = "
SELECT cc.contact_id, cc.total_amount, cc.trxn_id, cc.receive_date as cc_receive_date, ctrc.frequency_unit, ctrc.frequency_interval, contact.display_name
FROM `civicrm_contribution` cc
LEFT JOIN civicrm_contribution_recur ctrc ON (ctrc.id = cc.contribution_recur_id)
INNER JOIN civicrm_contact contact ON (cc.contact_id = contact.id)
WHERE cc.`trxn_id` IN ( $contributionTrxnIdsList )
    ";
    $dao = CRM_Core_DAO::executeQuery($contributionQuery);
    $existArray = array();
    $counts['contribution_existing'] = 0;
    $counts['contribution_existing_amount'] = 0;

    while ($dao->fetch()) {
      $existArray[$counts['contribution_existing']]['contact_id'] = $dao->contact_id;
      $existArray[$counts['contribution_existing']]['contact_name'] = $dao->display_name;
      $existArray[$counts['contribution_existing']]['receive_date'] = date('Y-m-d', strtotime($dao->cc_receive_date));
      $existArray[$counts['contribution_existing']]['frequency'] = $dao->frequency_interval . ' ' . $dao->frequency_unit;
      $existArray[$counts['contribution_existing']]['amount'] = $dao->total_amount;
      $existArray[$counts['contribution_existing']]['transaction_id'] = $dao->trxn_id;
      $counts['contribution_existing']++;
      $counts['contribution_existing_amount'] += $dao->total_amount;
    }

    // Get a list of transactionIDs that are in Smartdebit but not CiviCRM
    $missingTrxnIds = array_diff($validIds, $matchTrxnIds);
    if (!empty($missingTrxnIds)) {
      $counts['contribution_missing'] = 0;
      $counts['contribution_missing_amount'] = 0;

      $missingTrxnIdsString = implode(',', $missingTrxnIds);
      $findMissingQuery = "
          SELECT `transaction_id` as trxn_id, contact as display_name, contact_id as contact_id, amount as amount, receive_date as receive_date
          FROM `" . CRM_Smartdebit_CollectionReports::TABLENAME . "`
          WHERE transaction_id IN ($missingTrxnIdsString)";
      $dao = CRM_Core_DAO::executeQuery($findMissingQuery);
      while ($dao->fetch()) {
        $missingArray[$counts['contribution_missing']]['contact_name'] = $dao->display_name;
        $missingArray[$counts['contribution_missing']]['contact_id'] = $dao->contact_id;
        $missingArray[$counts['contribution_missing']]['amount'] = $dao->amount;
        $missingArray[$counts['contribution_missing']]['transaction_id'] = $dao->trxn_id;
        $missingArray[$counts['contribution_missing']]['receive_date'] = date('Y-m-d', strtotime($dao->receive_date));
        $counts['contribution_missing']++;
        $counts['contribution_missing_amount']+=$dao->amount;
      }
    }

    // Create query url for continue
    $queryParams = [];
    if (isset($auddisIDs)) {
      $queryParams['auddisID'] = urlencode(implode(',', $auddisIDs));
    }
    if (isset($aruddIDs)) {
      $queryParams['aruddID'] = urlencode(implode(',',$aruddIDs));
    }
    $queryParams['reset'] = 1;
    $bQueryParams = ['reset' => 1];

    $redirectUrlBack = CRM_Utils_System::url('civicrm/smartdebit/syncsd/select', $bQueryParams);
    $buttons[] = array(
      'type' => 'back',
      'js' => array('onclick' => "location.href='{$redirectUrlBack}'; return false;"),
      'name' => ts('Back'),
    );

    // Show next button to perform sync
    $redirectUrlContinue  = CRM_Utils_System::url('civicrm/smartdebit/syncsd/confirm', $queryParams);
    $buttons[] = array(
      'type' => 'next',
      'js' => array('onclick' => "location.href='{$redirectUrlContinue}'; return false;"),
      'name' => ts('Continue'),
    );

    $this->addButtons($buttons);
    CRM_Utils_System::setTitle(ts('Synchronise CiviCRM with Smart Debit'));

    $summary['Contributions already processed']['count'] = CRM_Utils_Array::value('contribution_existing', $counts);
    $summary['Contributions already processed']['total'] = CRM_Utils_Money::format(CRM_Utils_Array::value('contribution_existing_amount', $counts));
    $summary['Contributions not matched to contacts']['count'] = CRM_Utils_Array::value('contribution_missing', $counts);
    $summary['Contributions not matched to contacts']['total'] = CRM_Utils_Money::format(CRM_Utils_Array::value('contribution_missing_amount', $counts));
    $summary['Contributions matched to contacts']['count'] = CRM_Utils_Array::value('contribution_matched', $counts);
    $summary['Contributions matched to contacts']['total'] = CRM_Utils_Money::format(CRM_Utils_Array::value('contribution_matched_amount', $counts));

    $totalSummaryNumber = CRM_Utils_Array::value('auddis', $counts) + CRM_Utils_Array::value('arudd', $counts) + CRM_Utils_Array::value('contribution_existing', $counts) + CRM_Utils_Array::value('contribution_missing', $counts) + CRM_Utils_Array::value('contribution_matched', $counts);
    $totalSummaryAmount = CRM_Utils_Array::value('auddis_amount', $counts) + CRM_Utils_Array::value('arudd_amount', $counts) + CRM_Utils_Array::value('contribution_existing_amount', $counts) + CRM_Utils_Array::value('contribution_missing_amount', $counts) + CRM_Utils_Array::value('contribution_matched_amount', $counts);

    $this->assign('newAuddisRecords', $newAuddisRecords);
    $this->assign('newAruddRecords', $newAruddRecords);
    $this->assign('listArray', $listArray);
    $this->assign('totalMatched', CRM_Utils_Money::format(CRM_Utils_Array::value('contribution_matched_amount', $counts)));
    $this->assign('totalMatchedCount', CRM_Utils_Array::value('contribution_matched', $counts));
    $this->assign('totalMatchedAuddis', CRM_Utils_Array::value('auddis_matched', $counts));
    $this->assign('totalMatchedArudd', CRM_Utils_Array::value('arudd_matched', $counts));
    $this->assign('totalRejectedArudd', CRM_Utils_Array::value('arudd_amount', $counts));
    $this->assign('totalExist', CRM_Utils_Money::format(CRM_Utils_Array::value('contribution_existing_amount', $counts)));
    $this->assign('totalMissing', CRM_Utils_Money::format(CRM_Utils_Array::value('contribution_missing_amount', $counts)));
    $this->assign('existArray', $existArray);
    $this->assign('missingArray', $missingArray);
    $this->assign('summaryNumber', $totalSummaryNumber);
    $this->assign('totalSummaryAmount', CRM_Utils_Money::format($totalSummaryAmount));
    $this->assign('summary', $summary);

    parent::buildQuickForm();
  }
}
