<?php

namespace Drupal\lscorp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements form.
 */
class LscorpForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lscorp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state):array {
    $form_alter = \Drupal::service('FormAlter');
    $row_names = ['year', 'jan', 'feb', 'mar', 'q1', 'arp', 'may', 'jun', 'q2', 'jul', 'aug',
      'sep', 'q3', 'oct', 'nov', 'dec', 'q4', 'ytd',
    ];
    $tables = $form_state->get('tables');
    if (empty($tables)) {
      $tables = 1;
      $form_state->set('tables', 1);
    }
    $form['tables']['#prefix'] = '<div class = "single_table" id="lscorp-tables">';
    $form['tables']['#suffix'] = '</div>';
    $form['tables']['#tree'] = TRUE;
    for ($j = 0; $j < $tables; $j++) {

      $form['tables'][$j]['addRow'] = [
        '#type' => 'submit',
        '#value' => 'Add Row',
        '#name' => 'op ' . $j,
        '#submit' => ['::addRowCallback'],
        '#ajax' => [
          'callback' => '::formReturn',
          'disable-refocus' => FALSE,
          'event' => 'click',
          'wrapper' => 'lscorp' . $j,
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Adding a row...'),
          ],
        ],
      ];
      $form['tables'][$j]['table'] = [
        '#title' => 'LScorp Table',
        '#type' => 'table',
        '#header' => ['Year', 'Jan', 'Feb', 'Mar', 'Q1', 'Apr', 'May', 'Jun', 'Q2',
          'Jul', 'Aug', 'Sep', 'Q3', 'Oct', 'Nov', 'Dec', 'Q4', 'YTD',
        ],
        '#attributes' => [
          'id' => 'lscorp' . $j,
        ],
        '#tree' => TRUE,
      ];
      $count = $form_state->get('count' . $j);
      if (empty($count)) {
        $count = 1;
        $form_state->set('count' . $j, 1);
      }
      for ($i = $count; $i > 0; $i--) {
        $date = strval(intval(date('Y') - $i + 1));
        $form['tables'][$j]['table'][$i] = $form_alter->addRow($date);
      }
    }
    $form['addTable'] = [
      '#type' => 'submit',
      '#value' => 'Add Table',
      '#submit' => ['::addTableCallback'],
      '#ajax' => [
        'callback' => '::tableReturn',
        'disable-refocus' => FALSE,
        'event' => 'click',
        'wrapper' => 'lscorp-tables',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Adding new table...'),
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
      '#ajax' => [
        'callback' => '::tableReturn',
        'disable-refocus' => FALSE,
        'event' => 'click',
        'wrapper' => 'lscorp-tables',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Sending...'),
        ],
      ],
    ];
    $form['#attached']['library'][] = 'lscorp/table';
    return $form;
  }

  /**
   * Adds row to table.
   */
  public function addRowCallback(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getTriggeringElement()['#name'];
    $table = explode(' ', $table);
    $count = $form_state->get('count' . $table[1]);
    $count++;
    $form_state->set('count' . $table[1], $count);
    $form_state->setRebuild();
  }

  /**
   * Adds row to table.
   */
  public function addTableCallback(array &$form, FormStateInterface $form_state) {
    $tables = $form_state->get('tables');
    $tables++;
    $form_state->set('tables', $tables);
    $form_state->setRebuild();
  }

  /**
   * Returns form via ajax.
   */
  public function formReturn(array &$form, FormStateInterface $form_state): array {
    $table = $form_state->getTriggeringElement()['#name'];
    $table = explode(' ', $table);
    return $form['tables'][$table[1]]['table'];
  }

  /**
   * Returns full new table.
   */
  public function tableReturn(array &$form, FormStateInterface $form_tate) {
    return $form['tables'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $months = ['jan', 'feb', 'mar', 'arp', 'may', 'jun', 'jul', 'aug',
      'sep', 'oct', 'nov', 'dec',
    ];
    $quartals = ['q1', 'q2', 'q3', 'q4'];
    $i = 0;
    $tables = $form_state->getValue('tables');
    $first_input = '';
    $last_input = '';
    foreach ($tables as $table_key => $table) {
      foreach ($table['table'] as $row_key => $row) {
        foreach ($row as $cell_key => $value) {
          if (in_array($cell_key, $months)) {

          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
