<?php

namespace Drupal\dotmailer_webform\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\webformSubmissionInterface;


/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "dotmailer_webform_submission",
 *   label = @Translation("Dotmailer webform handler"),
 *   category = @Translation("Form Handler"),
 *   description = @Translation("Allows users to be regsitered to address books"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class DotmailerSubscribe extends WebformHandlerBase {

  protected $apiUsers;

  /**
   * {@inheritdoc}
   */

  public function defaultConfiguration() {
    return [
      'dotmailer_api_user' => NULL,
      'dotmailer_address_book' => NULL,
      'double_opt_in' => NULL,
      'subscription_label' => $this->t('Subscribe'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator) {
    $this->apiUsers = $entity_type_manager->getStorage('dotmailer_api_user')->loadMultiple();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\dotmailer\DotmailerAdminFormElementService $formElements */
    $formElements = \Drupal::service('dotmailer.admin_form_elements');
    $settings = [
      'dotmailer_api_user' => $this->configuration['dotmailer_api_user'],
      'dotmailer_address_book' => $this->configuration['dotmailer_address_book'],
      'double_opt_in' => $this->configuration['double_opt_in'],
    ];

    $form += $formElements->getAdminElements($settings, $form_state);

    $form['dotmailer_api_user']['#ajax'] = [
      'wrapper' => 'dotmailer_address_books_wrapper',
      'callback' => [$this, 'getActiveAddressBooksFromUser'],
    ];

    $form['subscription_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('used for the label of the checkbox'),
      '#default_value' => $this->configuration['subscription_label'],
    ];

    return $form;
  }

  /**
   * Ajax callback method which populates address books field.
   * @todo have this in a trait?
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form item.
   */
  public function getActiveAddressBooksFromUser(array &$form, FormStateInterface $form_state) {
    $addressBooks = ['' => t('- Select -')];
    $apiUserMachineName = $form_state->getValues()['settings']['dotmailer_api_user'];
    /* @var $apiUser \Drupal\dotmailer\Entity\DotmailerApiUserInterface */
    $apiUser = $this->apiUsers[$apiUserMachineName];
    $addressBooks += $apiUser->getActiveAddressBooks();
    $form['settings']['dotmailer_address_book']['#options'] = $addressBooks;
    return $form['settings']['dotmailer_address_book'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $values = $webform_submission->getData();

    return TRUE;
  }

}
