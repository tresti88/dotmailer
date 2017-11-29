<?php

namespace Drupal\dotmailer;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DotmailerAdminFormElementService.
 *
 * @package Drupal\dotmailer
 */
class DotmailerAdminFormElementService {

  protected $entityTypeManager;

  /**
   * An array of api users.
   *
   * @var array
   *   An array of Api user objects.
   */
  protected $apiUsers;

  /**
   * DotmailerAdminFormElementService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->apiUsers = $this->entityTypeManager->getStorage('dotmailer_api_user')->loadMultiple();
  }

  /**
   * Returns an array of admin elements.
   *
   * @param array $settings
   *   A settings array that contains all the default settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object so we can get the submitted value of the api user.
   *
   * @return array
   *   An array that contains all the form elements.
   */
  public function getAdminElements(array $settings, FormStateInterface $form_state) {

    $elements = [];
    $apiUsers = [];
    $addressBooks = ['' => t('- Select -')];
    $apiUserName = NULL;

    // Temp fix as per
    // https://www.drupal.org/project/drupal/issues/2798261#comment-11688033.
    try {
      $apiUserName = isset($form_state->getValues()['settings']) ? $form_state->getValues()['settings']['dotmailer_api_user'] : $form_state->getValues()['dotmailer_api_user'];
    }
    catch (\Exception $exception) {
      $apiUserName = $form_state->getCompleteFormState()->getValues()['settings']['dotmailer_api_user'];
    }

    if (isset($this->apiUsers[$apiUserName])) {
      $addressBooks += $this->apiUsers[$apiUserName]->getActiveAddressBooks();
    }

    if (isset($this->apiUsers[$settings['dotmailer_api_user']])) {
      $addressBooks += $this->apiUsers[$settings['dotmailer_api_user']]->getActiveAddressBooks();
    }

    foreach ($this->apiUsers as $apiUserMachineName => $apiUser) {
      /* @var $apiUser \Drupal\dotmailer\Entity\DotmailerApiUserInterface */
      $apiUsers[$apiUserMachineName] = $apiUser->getEmail();
    }

    $elements['dotmailer_api_user'] = [
      '#type' => 'select',
      '#title' => t('Api User'),
      '#multiple' => FALSE,
      '#description' => t('Choose an address book'),
      '#options' => $apiUsers,
      '#default_value' => $settings['dotmailer_api_user'],
      '#required' => TRUE,
    ];

    $elements['dotmailer_address_book'] = [
      '#prefix' => '<div id="dotmailer_address_books_wrapper">',
      '#suffix' => '</div>',
      '#type' => 'select',
      '#title' => t('Address book'),
      '#multiple' => FALSE,
      '#description' => t('Choose an address book'),
      '#options' => $addressBooks,
      '#default_value' => $settings['dotmailer_address_book'],
      '#required' => TRUE,
    ];

    $elements['double_opt_in'] = [
      '#type' => 'checkbox',
      '#title' => t('Require subscribers to Double Opt-in'),
      '#description' => t('New subscribers will be sent a link with an email they must follow to confirm their subscription.'),
      '#default_value' => $settings['double_opt_in'],
    ];

    return $elements;
  }

}
