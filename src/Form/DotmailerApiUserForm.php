<?php

namespace Drupal\dotmailer\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\encrypt\EncryptionProfileManager;
use Drupal\encrypt\EncryptService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DotmailerApiUserForm.
 */
class DotmailerApiUserForm extends EntityForm {

  protected $messenger;
  protected $encryptionService;

  /**
   * The encryption profile manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Messenger $messenger, EncryptionProfileManager $encryptionProfileManager, EncryptService $encryptionService) {
    $this->messenger = $messenger;
    $this->encryptionProfileManager = $encryptionProfileManager;
    $this->encryptionService = $encryptionService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('encrypt.encryption_profile.manager'),
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\dotmailer\Entity\DotmailerApiUserInterface $dotmailer_api_user */
    $dotmailer_api_user = $this->entity;
    $profiles = [];
    $encryptionProfiles = $this->encryptionProfileManager->getAllEncryptionProfiles();

    if(!empty($encryptionProfiles)) {
      /* @var \Drupal\encrypt\Entity\EncryptionProfile $encryptionProfile */
      foreach ($encryptionProfiles as $encryptionProfile) {
        $profiles[$encryptionProfile->id()] = $encryptionProfile->label();
      }
    }

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

    $form['encryption_profile'] = [
      '#type' => 'select',
      '#options' => $profiles,
      '#title' => $this->t('Encryption profile'),
      '#required' => TRUE,
      '#default_value' => $dotmailer_api_user->getEncryptionProfileId(),
      '#description' => $this->t('Used for encrypting the dotmailer password.')
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
    $encryptionProfile = $this->encryptionProfileManager->getEncryptionProfile($this->entity->getEncryptionProfileId());
    $encryptedPassword = $this->encryptionService->encrypt($form_state->getValue('password'), $encryptionProfile);
    $this->entity->setPassword($encryptedPassword);

    $dotmailer_api_user = $this->entity;
    $status = $dotmailer_api_user->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Dotmailer api user.', [
          '%label' => $dotmailer_api_user->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Dotmailer api user.', [
          '%label' => $dotmailer_api_user->label(),
        ]));
    }
    $form_state->setRedirectUrl($dotmailer_api_user->toUrl('collection'));
  }

}
