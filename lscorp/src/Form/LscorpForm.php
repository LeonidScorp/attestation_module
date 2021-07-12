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

//
//  /**
//   * Month in quartals array.
//   *
//   * @var \string[][]
//   */
//  protected static $monthsInQuartals = [
//    'q1' => [
//      'jan',
//      'feb',
//      'mar',
//    ],
//    'q2' => [
//      'arp',
//      'may',
//      'jun',
//    ],
//    'q3' => [
//      'jul',
//      'aug',
//      'sep',
//    ],
//    'q4' => [
//      'oct',
//      'nov',
//      'dec',
//    ],
//  ];

  /**
   * Names for table row cells.
   *
   * @var string[]
   */
  protected static $rowCellKeys = [
    'Year',
    'Jan',
    'Feb',
    'Mar',
    'Q1',
    'Arp',
    'May',
    'Jun',
    'Q2',
    'Jul',
    'Aug',
    'Sep',
    'Q3',
    'Oct',
    'Nov',
    'Dec',
    'Q4',
    'YTD',
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
    //Get number of tables and rows from form state.
    $tables = $form_state->get('tables');
    $tables = empty($tables) ? [0 => 1] : $tables;

    //Get triggered button.
    $triggered = $form_state->getTriggeringElement();
    $pressed_button = $triggered['#name'] ?? 'empty';

    //Add row or table
    if (str_contains($pressed_button, 'lscorp_')) {
      $tables[$triggered['#attributes']['data-table']]++;
    } elseif ($pressed_button === 'add-table') {
      $tables[] = 1;
    }

    //Save new values to form state.
    $form_state->set('tables', $tables);
    $table_count = count($tables);

    $form['tables']['#prefix'] = '<div class = "single_table" id="lscorp-tables">';
    $form['tables']['#suffix'] = '</div>';
    $form['tables']['#tree'] = TRUE;

    //Create tables.
    for ($j = 0; $j < $table_count; $j++) {
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
        '#attributes' => [
          'data-table' => $j,
        ],
      ];

      //Create basic table and it's header.
      $form['tables'][$j]['table'] = [
        '#type' => 'table',
        '#attributes' => [
          'id' => 'lscorp_' . $j,
        ],
        '#tree' => TRUE,
      ];
      $form['tables'][$j]['table']['#header'] = $this->addHeader();

      //Add row into table.
      for ($i = $tables[$j]; $i > 0; $i--) {
        $date = (int) date('Y') - $i + 1;
        $form['tables'][$j]['table'][$i] = $this->addRow($date);
        if ($pressed_button === 'op') {
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
  public function formReturn(array &$form, FormStateInterface $form_state):array {
    $table = $form_state->getTriggeringElement()['#attributes']['data-table'];
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
    $first_input = [];
    $last_input = [];

    foreach ($tables as $table_key => $table) {
      $values_row = [];
      foreach ($table['table'] as $row_key => $row) {
        foreach ($row as $cell_key => $cell) {
          if ($this->isMonth($cell_key)) {
            if (empty($first_input[$table_key]) && $cell !== '') {
              $first_input[$table_key] = [$row_key, $cell_key];
            }
            if ($cell !== '') {
              $last_input[$table_key] = [$row_key, $cell_key];
            }
            $values_row[] = $cell;
          }
        }
      }

      $values_row = array_filter(
        $values_row,
        static function ($k) {
          return $k !== '';
        }
      );
      $count = count($values_row);
      if (
        ($count !== 0)
        && ((array_key_last($values_row) - array_key_first($values_row) + 1)
          !== $count)) {
        $this->messenger->addError($this->t('Invalid'));
        return;
      }
      if (
        ($first_input[0] !== $first_input[$table_key])
        || ($last_input[0] !== $last_input[$table_key])
      ) {
        $this->messenger->addError($this->t('Invalid'));
        return;
      }
    }

    $this->messenger->addStatus($this->t('Valid'));
    $form_state->setRebuild();
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
    foreach (self::$rowCellKeys as $cell) {
      $item = strtolower($cell);
      if (!$this->isMonth($cell)) {
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
    $quartals = [];
    $row_values = [];
    $value = 0;
    $quartal_number = 1;
    $ytd = 0;
    $i = 0;


    foreach (self::$rowCellKeys as $month) {
      if (!$this->isMonth($month)) {
        continue;
      }
      $month = strtolower($month);
      $row_values[] = $form_state->getValue([
        'tables',
        $table,
        'table',
        $row,
        $month,
      ]);
    }

//    foreach (self::$monthsInQuartals as $quartal => $month_inside) {
//      $quartal_value = 0;
//      foreach ($month_inside as $month_name) {
//        $quartal_value = (float) $quartal_value +
//          (float) $month_values[$month_name];
//      }
//      if ($quartal_value !== 0) {
//        $q[$quartal] = round((($quartal_value + 1) / 3), 2);
//      }
//      else {
//        $q[$quartal] = '';
//      }
//    }

    foreach ($row_values as $cell) {
      $value += (float) $cell;
      $i++;
      if ($i === 3) {
        $quartals['q'.$quartal_number] = $value == 0 ? ''
          : round((($value + 1) / 3), 2);
        $quartal_number++;
        $i = 0;
        $value = 0;
      }

    }

    foreach ($quartals as $quartal_name => $quartal_value) {
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

  public function addHeader() {
    $header = [];

    foreach (self::$rowCellKeys as $cell) {
      $header[] = $this->t($cell);
    }
    return $header;
  }

  public function isMonth($item) {
    $item = strtolower($item);
    return !($item === 'ytd'
      || $item === 'q1'
      || $item === 'q2'
      || $item === 'q3'
      || $item === 'q4'
      || $item === 'year');
  }
}
