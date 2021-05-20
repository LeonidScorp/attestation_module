<?php

namespace Drupal\lscorp\Services;

/**
 * Provide services for form.
 *
 * @package Drupal\lscorp\Services
 */
class FormAlter {

  /**
   * Adds single row into table.
   */
  public function addRow(string $year):array {
    $row_names = ['year', 'jan', 'feb', 'mar', 'q1', 'arp', 'may', 'jun', 'q2', 'jul', 'aug',
      'sep', 'q3', 'oct', 'nov', 'dec', 'q4', 'ytd',
    ];
    $row = [];
    foreach ($row_names as $item) {
      if ($item == 'year') {
        array_push($row, [
          '#name' => $item,
          '#plain_text' => $year,
        ]);
      }
      elseif (($item == 'q1')||($item == 'q2')||($item == 'q3')||($item == 'q4')||($item == 'ytd')) {
        array_push($row, [
          '#name' => $item,
          '#plain_text' => '',
        ]);
      }
      else {
        $field = [
          '#type' => 'number',
          '#name' => $item,
        ];
        array_push($row, $field);
      }
    }
    return $row;

  }

}
