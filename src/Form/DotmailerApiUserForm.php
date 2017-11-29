<?php

namespace Drupal\dotmailer\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DotmailerApiUserForm.
 */
class DotmailerApiUserForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\dotmailer\Entity\DotmailerApiUserInterface $dotmailer_api_user */
    $dotmailer_api_user = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $dotmailer_api_user->label(),
      '#description' => $this->t("Label for the Dotmailer api user."),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#title' => $this->t('Email'),
      '#description' => $this->t("The API user's email address."),
      '#default_value' => $dotmailer_api_user->getEmail(),
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#size' => 35,
      '#title' => $this->t('Password'),
      '#description' => $this->t("The API user's password."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $dotmailer_api_user->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dotmailer\Entity\DotmailerApiUser::load',
      ],
      '#disabled' => !$dotmailer_api_user->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $dotmailer_api_user = $this->entity;
    $status = $dotmailer_api_user->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Dotmailer api user.', [
          '%label' => $dotmailer_api_user->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Dotmailer api user.', [
          '%label' => $dotmailer_api_user->label(),
        ]));
    }
    $form_state->setRedirectUrl($dotmailer_api_user->toUrl('collection'));
  }

}
