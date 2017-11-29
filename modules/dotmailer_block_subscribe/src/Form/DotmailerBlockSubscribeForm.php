<?php

namespace Drupal\dotmailer_block_subscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BlockSubscribeForm.
 */
class DotmailerBlockSubscribeForm extends FormBase {

  /**
   * The dotmailer api user object.
   *
   * @var \Drupal\dotmailer\Entity\DotmailerApiUserInterface
   */
  protected $apiUser;

  /**
   * Whether or not the user has to double opt in.
   *
   * @var bool
   */
  protected $doubleOptIn;

  /**
   * The address book id.
   *
   * @var string
   */
  protected $addressBookId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dotmailer_block_subscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->addressBookId = func_get_arg(2);
    $labelValue = func_get_arg(3);
    $this->doubleOptIn = func_get_arg(4);
    $this->apiUser = func_get_arg(5);

    $form['email'] = [
      '#type' => 'email',
      '#title' => $labelValue,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $email = $form_state->getValue('email');
    $subscribed = TRUE;
    // @todo tidy this up.
    $addressbookId = explode('-', $this->addressBookId);
    $this->apiUser->subscribeContact($addressbookId[0], $email, $this->doubleOptIn, $subscribed);

  }

}
