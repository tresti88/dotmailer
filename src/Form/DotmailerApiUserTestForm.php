<?php

namespace Drupal\dotmailer\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\encrypt\EncryptionProfileManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dotmailer\Entity\DotmailerApiUser;

/**
 * Builds the form to test Dotmailer api user entities against the api.
 */
class DotmailerApiUserTestForm extends FormBase {

  protected $dotmailerApiUserMachineName;

  protected $messenger;

  /**
   * The dotmailer api user entity.
   *
   * @var \Drupal\dotmailer\Entity\DotmailerApiUser
   */
  protected $apiUser;

  protected $entityTypeManager;

  protected $cache;

  /**
   * Sets entity type manager and the api user machine name.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The route object.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheData
   *   Entity type manager.
   */
  public function __construct(CurrentRouteMatch $current_route_match, EntityTypeManager $entityTypeManager, CacheBackendInterface $cacheData, Messenger $messenger) {
    $this->dotmailerApiUserMachineName = $current_route_match->getParameter('dotmailer_api_user');
    $this->entityTypeManager = $entityTypeManager;
    $this->cache = $cacheData;
    $this->apiUser = $this->entityTypeManager->getStorage('dotmailer_api_user')->load($this->dotmailerApiUserMachineName);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('cache.data'),
      $container->get('messenger'),
      $container->get('encrypt.encryption_profile.manager'),
      $container->get('encryption')
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

    $form['test_user_info'] = [
      '#type' => 'item',
      '#markup' => $this->t('Use this form to test whether the user is connected to the api properly'),
    ];

    $form['clear_address_book_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Also clear the address book cache for: @user', [
        '@user' => $this->apiUser->getEmail(),
      ]),
    ];

    $form['test_user'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test user'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $clearCache = $form_state->getValue('clear_address_book_cache');
    $cacheId = 'dotmailer_api_user_' . $this->apiUser->id() . '_address_books';

    $accountInfo = NULL;

    if ($clearCache) {
      $this->cache->delete($cacheId);
      $this->messenger->addStatus($this->t('Address book cache cleared for dotmailer user @user', [
        '@user' => $this->apiUser->getEmail(),
      ]));
    }

    if ($this->apiUser instanceof DotmailerApiUser) {

      try {

        $this->apiUser->getDotmailerResources()->GetAccountInfo();
        $this->messenger->addStatus($this->t('Entity is connected to dotmailer'));
      }
      catch (\Exception $exception) {

        $this->messenger->addError(
          $this->t('The following error has occurred: @message', [
            '@message' => $exception->getMessage(),
          ]),
          'error'
        );

      }

    }

  }

}
