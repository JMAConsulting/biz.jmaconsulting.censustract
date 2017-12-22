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

  public static function parseText($text) {
    return str_replace(" ", "+", $text);
  }

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
        return $output['result']['addressMatches'][0]['geographies']['Census Tracts'][0]['TRACT'];
      }
      else {
        return NULL;
      }
    }
    return NULL;
  }
}