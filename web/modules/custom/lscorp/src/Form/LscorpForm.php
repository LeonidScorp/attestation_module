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
  public function getFormId() {
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
    $form['addRow'] = [
      '#type' => 'submit',
      '#value' => 'Add Row',
      '#submit' => ['::addRowCallback'],
      '#ajax' => [
        'callback' => '::formReturn',
        'disable-refocus' => FALSE,
        'event' => 'click',
        'wrapper' => 'lscorp',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Adding a row...'),
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Send',
      '#ajax' => [
        'callback' => '::formReturn',
        'disable-refocus' => FALSE,
        'event' => 'click',
        'wrapper' => 'lscorp',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Sending...'),
        ],
      ],
    ];
    $form['table'] = [
      '#title' => 'LScorp Table',
      '#type' => 'table',
      '#header' => ['Year', 'Jan', 'Feb', 'Mar', 'Q1', 'Apr', 'May', 'Jun', 'Q2',
        'Jul', 'Aug', 'Sep', 'Q3', 'Oct', 'Nov', 'Dec', 'Q4', 'YTD',
      ],
      '#attributes' => [
        'id' => 'lscorp',
      ],
      '#tree' => TRUE,
    ];
    $count = $form_state->get('count');
    if (empty($count)) {
      $count = 1;
      $form_state->set('count', 1);
    }
    for ($i = $count; $i > 0; $i--) {
      $date = strval(intval(date('Y') - $i + 1));
      $form['table'][$i] = $form_alter->addRow($date);
    }
    return $form;
  }

  /**
   * Adds row to table.
   */
  public function addRowCallback(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('count');
    $count++;
    $form_state->set('count', $count);
    $values = $form_state->getValues();
    $form_state->setRebuild();
  }

  /**
   * Returns form via ajax.
   */
  public function formReturn(array &$form, FormStateInterface $form_state): array {
    return $form['table'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('table');
  }

}
