<?php

namespace Drupal\lscorp\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Define custom controller class.
 */
class FormController extends ControllerBase {

  /**
   * Display page.
   *
   * @return array
   *   Return markup array.
   */
  public function content(): array {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello, World!'),
    ];

  }

}
