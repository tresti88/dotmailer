<?php

namespace Drupal\dotmailer\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to pull down active address books for a user.
 */
class DotmailerApiUserActiveaddressbooksForm extends FormBase {

  protected $dotmailerApiUserMachineName;

  protected $entityTypeManager;

  /**
   * The Dotmailer api user.
   *
   * @var \Drupal\dotmailer\Entity\DotmailerApiUserInterface
   */
  protected $apiUser;

  /**
   * A cache store.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Sets entity type manager and the api user machine name.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The route object.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache store.
   */
  public function __construct(CurrentRouteMatch $current_route_match, EntityTypeManager $entityTypeManager, CacheBackendInterface $cache) {
    $this->dotmailerApiUserMachineName = $current_route_match->getParameter('dotmailer_api_user');
    $this->entityTypeManager = $entityTypeManager;
    $this->apiUser = $this->entityTypeManager->getStorage('dotmailer_api_user')->load($this->dotmailerApiUserMachineName);
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('cache.data')
    );
  }

  /**
   * Returns the form id.
   *
   * @return string
   *   The form id.
   */
  public function getFormId() {
    return 'dotmailer_test_user_api';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $cacheId = 'dotmailer_api_user_' . $this->apiUser->id() . '_address_books';

    $options = $this->cache->get($cacheId);

    if ($options == FALSE) {

      $addressBooks = $this->apiUser->getDotmailerResources()->GetAddressBooks();

      while ($addressBooks->valid()) {

        /** @var \DotMailer\Api\DataTypes\ApiAddressBook $addressBookItem */
        $addressBookItem = $addressBooks->current()->toArray();
        if ($addressBookItem['name'] != 'Test') {
          $options[(int) $addressBookItem['id'] . '-' . $addressBookItem['name']] = $addressBookItem['name'] . ' (Number of contacts: ' . $addressBookItem['contacts'] . ')';
        }
        $addressBooks->next();
      }

      $this->cache->set($cacheId, $options);
    }

    $form['container'] = [
      '#type' => 'details',
      '#title' => $this->t('Address books for user: @username', [
        '@username' => $this->apiUser->getEmail(),
      ]),
    ];

    $form['container']['address_books'] = [
      '#type' => 'checkboxes',
      '#options' => isset($options->data) ? $options->data : $options,
      '#default_value' => $this->apiUser->getAddressBooks(),
    ];

    $form['address_books_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $addressBooks = $form_state->getValue('address_books');
    $this->apiUser->setAddressBooks($addressBooks);
    $this->apiUser->save();
  }

}
