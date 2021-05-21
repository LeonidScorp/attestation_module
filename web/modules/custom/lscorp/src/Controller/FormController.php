<?php

namespace Drupal\lscorp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define custom controller class.
 */
class FormController extends ControllerBase {

  /**
   * Create controller interface.
   *
   * @return FormController
   *   Returns FormController object.
   */
  public static function create(ContainerInterface $container): FormController {
    return new static($container->get('form_builder'));
  }

  /**
   * Var for FormBuilder interface.
   *
   * @var formBuilder
   *   FormBuilder Interface.
   */
  protected $formBuilder;

  /**
   * FormController constructor.
   */
  public function __construct(FormBuilderInterface $builder) {
    $this->formBuilder = $builder;
  }

  /**
   * Display page.
   *
   * @return array
   *   Return markup array.
   */
  public function content(): array {
    $formClass = '\Drupal\lscorp\Form\LscorpForm';
    $form = $this->formBuilder->getForm($formClass);
    return [
      '#type' => 'markup',
      'form' => $form,
    ];

  }

}
