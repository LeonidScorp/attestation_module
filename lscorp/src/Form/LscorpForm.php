<?php

namespace Drupal\lscorp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements form.
 */
class LscorpForm extends FormBase {

  /**
   * Month array.
   *
   * @var string[]
   */
  protected static $months = [
    'jan',
    'feb',
    'mar',
    'arp',
    'may',
    'jun',
    'jul',
    'aug',
    'sep',
    'oct',
    'nov',
    'dec',
  ];

  /**
   * Month in quartals array.
   *
   * @var \string[][]
   */
  protected static $monthsInQuartals = [
    'q1' => [
      'jan',
      'feb',
      'mar',
    ],
    'q2' => [
      'arp',
      'may',
      'jun',
    ],
    'q3' => [
      'jul',
      'aug',
      'sep',
    ],
    'q4' => [
      'oct',
      'nov',
      'dec',
    ],
  ];

  /**
   * Machine names for table row cells.
   *
   * @var string[]
   */
  protected static $rowCellKeys = [
    'year',
    'jan',
    'feb',
    'mar',
    'q1',
    'arp',
    'may',
    'jun',
    'q2',
    'jul',
    'aug',
    'sep',
    'q3',
    'oct',
    'nov',
    'dec',
    'q4',
    'ytd',
  ];

  /**
   * Create dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return \Drupal\lscorp\Form\LscorpForm|static
   *   Static values.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('messenger'));
  }

  /**
   * LscorpForm constructor.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Variable for Messenger class.
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lscorp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tables = $form_state->get('tables');
    $tables = empty($tables) ? [0 => 1] : $tables;

    $triggered = $form_state->getTriggeringElement()['#name'] ?? 'empty';
    if (str_contains($triggered, 'lscorp_')) {
      $tables[(int) str_replace('lscorp_', '', $triggered)]++;
    }
    if ($triggered === 'add-table') {
      array_push($tables, 1);
    }
    $form_state->set('tables', $tables);

    $form['tables']['#prefix'] = '<div class = "single_table" id="lscorp-tables">';
    $form['tables']['#suffix'] = '</div>';
    $form['tables']['#tree'] = TRUE;

    for ($j = 0; $j < count($tables); $j++) {
      $form['tables'][$j]['addRow'] = [
        '#type' => 'button',
        '#value' => $this->t('Add Row'),
        '#name' => 'lscorp_' . $j,
        '#ajax' => [
          'callback' => '::formReturn',
          'disable-refocus' => FALSE,
          'event' => 'click',
          'wrapper' => 'lscorp_' . $j,
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Adding a row...'),
          ],
        ],
      ];

      $form['tables'][$j]['table'] = [
        '#title' => $this->t('LScorp Table'),
        '#type' => 'table',
        '#header' => [
          $this->t('Year'),
          $this->t('Jan'),
          $this->t('Feb'),
          $this->t('Mar'),
          $this->t('Q1'),
          $this->t('Apr'),
          $this->t('May'),
          $this->t('Jun'),
          $this->t('Q2'),
          $this->t('Jul'),
          $this->t('Aug'),
          $this->t('Sep'),
          $this->t('Q3'),
          $this->t('Oct'),
          $this->t('Nov'),
          $this->t('Dec'),
          $this->t('Q4'),
          $this->t('YTD'),
        ],
        '#attributes' => [
          'id' => 'lscorp_' . $j,
        ],
        '#tree' => TRUE,
      ];

      for ($i = $tables[$j]; $i > 0; $i--) {
        $date = (int) date('Y') - $i + 1;
        $form['tables'][$j]['table'][$i] = $this->addRow($date);
        if ($triggered === 'op') {
          $this->calculateValues($j, $i, $form, $form_state);
        }
      }
    }

    $form['addTable'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Table'),
      '#name' => 'add-table',
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
      '#value' => $this->t('Submit'),
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
   * Returns form via ajax.
   *
   * @param array $form
   *   Current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Table with changed number of rows.
   */
  public function formReturn(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getTriggeringElement()['#name'];
    $table = (int) str_replace('lscorp_', '', $table);
    return $form['tables'][$table]['table'];
  }

  /**
   * Returns all tables with a new one.
   *
   * @param array $form
   *   Current form.
   *
   * @return mixed
   *   All tables.
   */
  public function tableReturn(array &$form) {
    return $form['tables'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tables = $form_state->getValue('tables');
    $table_count = count($tables);
    $first_input = [];
    $last_input = [];

    foreach ($tables as $table_key => $table) {
      $values_row = [];
      foreach ($table['table'] as $row_key => $row) {
        foreach ($row as $cell_key => $cell) {
          if (in_array($cell_key, self::$months)) {
            if (empty($first_input[$table_key]) && !empty($cell)) {
              $first_input[$table_key] = [$row_key, $cell_key];
            }
            if (!empty($cell)) {
              $last_input[$table_key] = [$row_key, $cell_key];
            }
            array_push($values_row, $cell);
          }
        }
      }

      $values_row = array_filter(
        $values_row,
        function ($k) {
          return $k !== '';
        }
      );
      $count = count($values_row);
      if (((array_key_last($values_row) - array_key_first($values_row) + 1)
          != $count) && ($count != 0)) {
        $this->messenger->addError($this->t('Invalid'));
        return FALSE;
      }
    }

    for ($i = 1; $i < $table_count; $i++) {
      if (
        ($first_input[0] != $first_input[$i])
        || ($last_input[0] != $last_input[$i])
      ) {
        $this->messenger->addError($this->t('Invalid'));
        return FALSE;
      }
    }

    $this->messenger->addStatus($this->t('Valid'));
    $form_state->setRebuild();
    return TRUE;
  }

  /**
   * Builds table row.
   *
   * @param int $year
   *   Year to add.
   *
   * @return array
   *   Table row.
   */
  public function addRow(int $year): array {
    $row = [];
    foreach (self::$rowCellKeys as $item) {
      if (
        ($item == 'year')
        ||($item == 'q1')
        || ($item == 'q2')
        || ($item == 'q3')
        || ($item == 'q4')
        || ($item == 'ytd')
      ) {
        $row[$item] = [
          '#title' => $item,
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#disabled' => TRUE,
          '#value' => $item === 'year' ? $year : '',
        ];
      }
      else {
        $row[$item] = [
          '#type' => 'number',
          '#title' => $item,
          '#title_display' => 'invisible',
        ];
      }
    }
    return $row;

  }

  /**
   * Calculates values for quartals and year.
   *
   * @param int $table
   *   Table to process.
   * @param int $row
   *   Row to process.
   * @param array $form
   *   Current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function calculateValues(
    int $table,
    int $row,
    array &$form,
    FormStateInterface $form_state
  ) {
    $month_values = [];
    $q = [];
    $ytd = 0;

    foreach (self::$months as $month) {
      $month_values[$month] = $form_state->getValue([
        'tables',
        $table,
        'table',
        $row,
        $month,
      ]);
    }

    foreach (self::$monthsInQuartals as $quartal => $month_inside) {
      $quartal_value = 0;
      foreach ($month_inside as $month_name) {
        $quartal_value = (float) $quartal_value +
          (float) $month_values[$month_name];
      }
      if ($quartal_value != 0) {
        $q[$quartal] = round((($quartal_value + 1) / 3), 2);
      }
      else {
        $q[$quartal] = '';
      }
    }

    foreach ($q as $quartal_name => $quartal_value) {
      $form['tables'][$table]['table'][$row][$quartal_name]['#value'] =
        $quartal_value;

      $ytd = (float) $ytd + (float) $quartal_value;
    }
    if ($ytd != 0) {
      $ytd = round((($ytd + 1) / 4), 2);
    }
    else {
      $ytd = '';
    }

    $form['tables'][$table]['table'][$row]['ytd']['#value'] = $ytd;
  }

}
