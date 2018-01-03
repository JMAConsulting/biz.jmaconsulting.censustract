<?php

/**
 * Job to send contact reports to officials for each neighbourhood.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_census_tract_sendReport($params) {
  $results = CRM_Censustract_BAO_Censustract::sendReport($params);
}