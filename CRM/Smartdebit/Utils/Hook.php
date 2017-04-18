<?php

/**
 * Class CRM_Smartdebit_Utils_Hook
 *
 * This class implements hooks for direct debit functions
 *
 * FIXME: It has not been tested since the migration to a separate (uk.co.vedaconsulting.smartdebit) extension
 */
abstract class CRM_Smartdebit_Utils_Hook {

  static $_nullObject = null;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = null;

  /**
   * Constructor and getter for the singleton instance
   * @return instance of $config->userHookClass
   */
  static function singleton() {
    if (self::$_singleton == null) {
      $config = CRM_Core_Config::singleton();
      $class = $config->userHookClass;
      require_once( str_replace( '_', DIRECTORY_SEPARATOR, $config->userHookClass ) . '.php' );
      self::$_singleton = new $class();
    }
    return self::$_singleton;
  }

  abstract function invoke( $numParams,
                            &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
                            $fnSuffix );

  /**
   * This hook allows to validate contribution params when importing smart debit charge file
   * @param array   $params     Contribution params
   *
   * @access public
   */
  static function validateSmartdebitContributionParams( &$params ) {
    return self::singleton( )->invoke( 1, $params, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_validateSmartdebitContributionParams' );
  }

  /**
   * This hook allows to alter contribution params when importing smart debit charge file
   * @param array   $params     Contribution params
   *
   * @access public
   */
  static function alterSmartdebitContributionParams( &$params ) {
    return self::singleton( )->invoke( 1, $params, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_alterSmartdebitContributionParams' );
  }

  /**
   * This hook allows to handle AUDDIS rejected contributions
   * @param integer   $contributionId   Contribution ID of the failed/rejected contribuition
   *
   * @access public
   */
  static function handleAuddisRejectedContribution( $contributionId ) {
    return self::singleton( )->invoke( 1, $contributionId, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_handleAuddisRejectedContribution' );
  }

  /**
   * This hook allows to handle membership renewal for the DD import
   * Set the flag $params['renew'] = 0 to skip membership renewal
   * @param integer   $params   Membership params
   *
   * @access public
   */
  static function handleSmartdebitMembershipRenewal( &$params ) {
    return self::singleton( )->invoke( 1, $params, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_handleSmartdebitMembershipRenewal' );
  }
}
