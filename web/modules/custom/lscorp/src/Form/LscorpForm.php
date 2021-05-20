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
    $form['#type'] = 'actions';
    $form['addRow'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add row'),
      '#submit' => ['::addRowCallback'],
      '#ajax' => [
        'callback' => '::formReturn',
        'disable-refocus' => FALSE,
        'event' => 'click',
        'wrapper' => 'lscorp',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Adding row...'),
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::formReturn',
        'disable-refocus' => FALSE,
        'event' => 'click',
        'wrapper' => 'lscorp',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Submitting...'),
        ],
      ],
    ];
    $rowsCount = $form_state->get('rowsCount');
    if (empty($rowsCount)) {
      $rowsCount = 1;
      $form_state->set('rowsCount', 1);
    }
    $form['table'] = [
      '#attributes' => [
        'id' => 'lscorp',
      ],
      '#type' => 'table',
      '#caption' => $this->t('Test table'),
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
    ];
    for ($i = 0; $i < $rowsCount; $i++) {
      $date = strval(intval(date('Y') - ($rowsCount - $i) + 1));
      array_push($form['table'], \Drupal::service('FormAlter')->addRow($date));
    }

    return $form;
  }

  /**
   * Adds row to table.
   */
  public function addRowCallback(array &$form, FormStateInterface $form_state) {
    $rowsCount = $form_state->get('rowsCount');
    $rowsCount++;
    $form_state->set('rowsCount', $rowsCount);
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
//    if (strlen($form_state->getValue('phone_number')) < 3) {
//      $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number'));
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setValue('0', 2);
    $this->messenger()->addStatus($this->t('Your phone number is @number', ['@number' => $form_state->getValue('phone_number')]));
  }

}
