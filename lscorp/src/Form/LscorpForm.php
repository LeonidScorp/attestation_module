<?php

namespace Drupal\lscorp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\lscorp\Services\FormAlter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements form.
 */
class LscorpForm extends FormBase {

  /**
   * Create dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return \Drupal\lscorp\Form\LscorpForm|static
   *   Static values.
   */
  public static function create(ContainerInterface $container): LscorpForm {
    return new static($container->get('form_alter'), $container->get('messenger'));
  }

  /**
   * Object for FormAlter class.
   *
   * @var formAlter
   */
  protected $formAlter;

  /**
   * Object for Drupal Messenger.
   *
   * @var messenger
   */
  protected $messenger;

  /**
   * LscorpForm constructor.
   *
   * @param \Drupal\lscorp\Services\FormAlter $formAlterInterface
   *   Variable for FormAlter class.
   * @param \Drupal\Core\Messenger\Messenger $messengerInterface
   *   Variable for Messenger class.
   */
  public function __construct(FormAlter $formAlterInterface, Messenger $messengerInterface) {
    $this->formAlter = $formAlterInterface;
    $this->messenger = $messengerInterface;
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
  public function buildForm(array $form, FormStateInterface $form_state):array {
    $months = ['jan', 'feb', 'mar', 'arp', 'may', 'jun', 'jul', 'aug',
      'sep', 'oct', 'nov', 'dec',
    ];
    $months_in_quartals = [
      'q1' => [
        'jan', 'feb', 'mar',
      ],
      'q2' => [
        'arp', 'may', 'jun',
      ],
      'q3' => [
        'jul', 'aug', 'sep',
      ],
      'q4' => [
        'oct', 'nov', 'dec',
      ],
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
        $form['tables'][$j]['table'][$i] = $this->formAlter->addRow($date);
        $process = $form_state->getTriggeringElement()['#name'];
        if ($process === 'op') {
          $month_values = [];
          foreach ($months as $month) {
            $month_values[$month] = $form_state->getValue(['tables', $j,
              'table', $i, $month,
            ]);
          }
          $q = [];
          $ytd = 0;
          foreach ($months_in_quartals as $quartal => $month_inside) {
            $quartal_value = 0;
            foreach ($month_inside as $month_name) {
              $quartal_value = floatval($quartal_value) + floatval($month_values[$month_name]);
            }
            if ($quartal_value != 0) {
              $q[$quartal] = round((($quartal_value + 1) / 3), 2);
            }
            else {
              $q[$quartal] = '';
            }
          }
          foreach ($q as $quartal_name => $quartal_value) {
            $form['tables'][$j]['table'][$i][$quartal_name]['#value'] = $quartal_value;
            $ytd = floatval($ytd) + floatval($quartal_value);
          }
          if ($ytd != 0) {
            $ytd = round((($ytd + 1) / 4), 2);
          }
          else {
            $ytd = '';
          }
          $form['tables'][$j]['table'][$i]['ytd']['#value'] = $ytd;
        }
      }
    }
    $form['addTable'] = [
      '#type' => 'submit',
      '#value' => 'Add Table',
      '#submit' => ['::addTableCallback'],
      '#name' => 'op table',
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
  public function tableReturn(array &$form) {
    return $form['tables'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): bool {
    $months = ['jan', 'feb', 'mar', 'arp', 'may', 'jun', 'jul', 'aug',
      'sep', 'oct', 'nov', 'dec',
    ];
    $errors = FALSE;
    $tables = $form_state->getValue('tables');
    $table_count = $form_state->get('tables');
    $first_input = [];
    $last_input = [];
    $row_count_array = [];
    foreach ($tables as $table_key => $table) {
      $values_row = [];
      foreach ($table['table'] as $row_key => $row) {
        foreach ($row as $cell_key => $cell) {
          if (in_array($cell_key, $months)) {
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
      $count = count($values_row);
      for ($i = 0; $i < $count; $i++) {
        if ($values_row[$i] === '') {
          unset($values_row[$i]);
        }
        else {
          break;
        }
      }
      for ($i = $count - 1; $i >= 0; $i--) {
        if ($values_row[$i] === '') {
          unset($values_row[$i]);
        }
        else {
          break;
        }
      }
      if (in_array('', $values_row)) {
        $errors = TRUE;
      }
    }
    if ($table_count > 1) {
      for ($i = 0; $i < $table_count; $i++) {
        array_push($row_count_array, $form_state->get('count' . $i));
      }
      if (in_array(1, $row_count_array)) {
        for ($i = 1; $i < $table_count; $i++) {
          if (($first_input[0] != $first_input[$i]) || ($last_input[0] != $last_input[$i])) {
            $errors = TRUE;
          }
        }
      }
    }
    if (!$errors) {
      $this->messenger->addStatus('Valid');
      $form_state->setRebuild();
    }
    else {
      $this->messenger->addError('Invalid');
    }
    return $errors;
  }

}
