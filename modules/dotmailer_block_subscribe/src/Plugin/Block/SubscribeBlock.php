<?php

namespace Drupal\dotmailer_block_subscribe\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SubscribeBlock' block.
 *
 * @Block(
 *  id = "dotmailer_subscribe_block",
 *  admin_label = @Translation("Dotmailer Subscribe block"),
 * )
 */
class SubscribeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * An array of available api users.
   *
   * @var array
   */
  protected $apiUsers;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->apiUsers = $this->entityTypeManager->getStorage('dotmailer_api_user')->loadMultiple();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'dotmailer_api_user' => NULL,
      'dotmailer_address_book' => NULL,
      'double_opt_in' => NULL,
      'subscription_label' => $this->t('Enter your email to subscribe'),
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

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
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['double_opt_in'] = $form_state->getValue('double_opt_in');
    $this->configuration['dotmailer_api_user'] = $form_state->getValue('dotmailer_api_user');
    $this->configuration['subscription_label'] = $form_state->getValue('subscription_label');
    $this->configuration['dotmailer_address_book'] = $form_state->getValue('dotmailer_address_book');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm(
      'Drupal\dotmailer_block_subscribe\Form\DotmailerBlockSubscribeForm',
      $this->configuration['dotmailer_address_book'],
      $this->configuration['subscription_label'],
      $this->configuration['double_opt_in'],
      $this->apiUsers[$this->configuration['dotmailer_api_user']]
    );

    return $form;
  }

}
