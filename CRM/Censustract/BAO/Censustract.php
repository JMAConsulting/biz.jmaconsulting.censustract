<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright JMAConsulting                                            |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright JMAConsulting
 */

/**
 * This is class to handle census tract related functions.
 */
class CRM_Censustract_BAO_Censustract extends CRM_Core_DAO {

  /**
   * Replace blank characters with valid separator.
   */
  public static function parseText($text) {
    return str_replace(" ", "+", $text);
  }

  /**
   * Connect to Census API to retrieve census tract information.
   */
  public static function getCensusTract($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);

    if (!empty($response)) {
      $output = json_decode($response, TRUE);
      if (!empty($output['result']) && !empty($output['result']['addressMatches'])) {
        // Return the first match.
        if (!empty($output['result']['addressMatches'][0]['geographies']['Census Tracts'][0]['BASENAME'])) {
          return $output['result']['addressMatches'][0]['geographies']['Census Tracts'][0]['BASENAME'];
        }
        else {
          return NULL;
        }
      }
      else {
        return NULL;
      }
    }
    return NULL;
  }

  /**
   * Helper function to retreive information for census tract.
   */
  public static function getTractData() {
    $table = civicrm_api3('CustomGroup', 'getvalue', array(
      'name' => 'us_census_info',
      'return' => 'table_name',
    ));
    $column = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Census_Tract',
      'return' => 'column_name',
    ));
    return array($table, $column);
  }

  /**
   * Helper function to retreive information for officials.
   */
  public static function getOfficialData() {
    list($table, $column) = self::getTractData();

    $officialCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM {$table} WHERE {$column} <> '' AND {$column} IS NOT NULL");
    $neighbourhood = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Official_for_Neighbourhood',
      'return' => 'id',
    ));

    $officialParams = array(
      'sequential' => 1,
      'return' => array("email", "custom_{$neighbourhood}", "display_name"),
      'custom_' . $neighbourhood => array('!=' => ""),
      'rowCount' => $officialCount,
    );
    $officials = civicrm_api3('Contact', 'get', $officialParams);

    return $officials;
  }

  /**
   * Send mail report for each neighbourhood to elected officials.
   */
  public static function sendReport($params) {
    $officials = self::getOfficialData();
    $neighbourhood = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Official_for_Neighbourhood',
      'return' => 'id',
    ));
    $ind = array();
    $is_error = 0;
    $messages = array("Report Mail Triggered...");
    if ($officials['count'] >= 1) {
      $officials = $officials['values'];
      foreach ($officials as $key => $value) {
        if (!empty($value['custom_' . $neighbourhood])) {
          $ind[$key]['contact_id'] = $value['contact_id'];
          $ind[$key]['email'] = $value['email'];
        }
      }
    // Now email
    $instanceId = (int)CRM_Utils_Array::value('instanceId', $params);
    $_REQUEST['instanceId'] = $instanceId;
    $_REQUEST['sendmail'] = CRM_Utils_Array::value('sendmail', $params, 1);

    // if cron is run from terminal --output is reserved, and therefore we would provide another name 'format'
    $_REQUEST['output'] = CRM_Utils_Array::value('format', $params, CRM_Utils_Array::value('output', $params, 'csv'));
    $_REQUEST['reset'] = CRM_Utils_Array::value('reset', $params, 1);

    $optionVal = CRM_Report_Utils_Report::getValueFromUrl($instanceId);
    $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', $optionVal, 'value');
    if (strstr(CRM_Utils_Array::value('name', $templateInfo), '_Form')) {
      $obj = new CRM_Report_Page_Instance();
      $instanceInfo = array();
      CRM_Report_BAO_ReportInstance::retrieve(array('id' => $instanceId), $instanceInfo);
      if (!empty($instanceInfo['title'])) {
        $obj->assign('reportTitle', $instanceInfo['title']);
      }
      else {
        $obj->assign('reportTitle', $templateInfo['label']);
      }
      foreach ($ind as $key => $value) {
        $_REQUEST['email_to_send'] = $value['email'];
        $_GET['official_id_value'] = $value['contact_id'];
        $wrapper = new CRM_Utils_Wrapper();
        $arguments = array(
          'urlToSession' => array(
             array(
               'urlVar' => 'instanceId',
               'type' => 'Positive',
               'sessionVar' => 'instanceId',
               'default' => 'null',
             ),                               
           ),
          'ignoreKey' => TRUE,
        );
        $messages[] = $wrapper->run($templateInfo['name'], NULL, $arguments);
      }
    }
  }
  if ($is_error == 0) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error($messages);
  }
  }
}