<?php

namespace Drupal\dotmailer\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

use Drupal\dotmailer\ValueObject\ContactDataFieldArray;
use Drupal\dotmailer\ValueObject\EmailAddress;

/**
 * Provides a field type of dotmailer_addressbook_subscribe.
 *
 * @FieldType(
 *   id = "dotmailer_address_book_subscribe",
 *   label = @Translation("Subscribe to dotmailer"),
 *   module = "dotmailer",
 *   default_formatter = "dotmailer_address_book_subscribe_default",
 *   default_widget = "dotmailer_address_book_subscribe_widget",
 * )
 */
class DotmailerAddressBookSubscribe extends FieldItemBase implements FieldItemInterface {

  protected $apiUsers;

  /**
   * The previous state of subscribed.
   *
   * @var null
   */
  protected $previousSubscribedState;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $entityTypeManager = \Drupal::entityTypeManager();
    $this->previousSubscribedState = NULL;
    $this->apiUsers = $entityTypeManager->getStorage('dotmailer_api_user')->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'dotmailer_address_book' => NULL,
      'dotmailer_api_user' => NULL,
      'double_opt_in' => FALSE,
      'email_property' => 'mail',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'subscribe_checkbox_label' => NULL,
      'unsubscribe_on_delete' => FALSE,
      'contact_data_fields' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        // The id of the Dotmailer address book (from Dotmailer).
        'address_book_id' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => TRUE,
        ],
        // Whether or not the address book has been subscribed to or not.
        'subscribed' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $entity = $this->getEntity();
    $fieldName = $this->getFieldDefinition()->get('field_name');
    $previousFieldValue = isset($entity->original) ? $entity->original->get($fieldName)->getValue() : NULL;
    if (isset($previousFieldValue[0]['subscribed'])) {
      $this->previousSubscribedState = ($previousFieldValue[0]['subscribed'] == '1');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);

    if ($this->previousSubscribedState !== $this->getSubscribed()) {
      $entity = $this->getEntity();
      $email = $entity->get($this->getEntityField())->value;
      $addressBookId = $this->getAddressBookId();

      /** @var \Drupal\dotmailer\Entity\DotmailerApiUserInterface $apiUser */
      $apiUser = $this->apiUsers[$this->getApiUser()];
      $contactDataFields = $this->getContactDataFields();
      $optIn = $this->getDoubleOptInSetting();
      $subscribed = $this->getSubscribed();
      $dataFields = new ContactDataFieldArray($contactDataFields, $entity);
      $apiUser->setContactDataFields($dataFields);
      $apiUser->subscribeContact($addressBookId, $email, $optIn, $subscribed);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    /** @var \Drupal\dotmailer\Entity\DotmailerApiUserInterface $apiUser */
    $apiUser = $this->apiUsers[$this->getApiUser()];
    $unSubscribeOnDelete = $this->definition->getSetting('unsubscribe_on_delete');
    if ($unSubscribeOnDelete == TRUE) {
      $entity = $this->getEntity();
      $email = $entity->get($this->getEntityField())->value;
      $emailAddress = new EmailAddress($email);
      $apiUser->deleteContact($emailAddress);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['address_book_id'] = DataDefinition::create('string')
      ->setLabel(t('Address book'))
      ->setDescription(t('The dotmailer Address book to sign up to.'));
    $properties['subscribed'] = DataDefinition::create('integer')
      ->setLabel(t('Subscribe'))
      ->setDescription(t('True when an entity has been subscribed to an address book.'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return (empty($this->getAddressBookId()) && $this->getSubscribed());
  }

  /**
   * Returns the field 'subscribed' value.
   *
   * @return bool
   *   TRUE if subscribed FALSE otherwise.
   */
  public function getSubscribed() {
    if (isset($this->values['subscribed'])) {
      return ($this->values['subscribed'] == '1');
    }
    return FALSE;
  }

  /**
   * Returns the field 'address_book_id' value.
   *
   * @return string|null
   *   The address book id as a string or NULL if nothing exists.
   */
  public function getAddressBookId() {
    if (isset($this->values['address_book_id'])) {
      return $this->values['address_book_id'];
    }
    return NULL;
  }

  /**
   * Returns api user.
   *
   * @return string
   *   The machine name of the api user.
   */
  public function getApiUser() {
    return $this->getSetting('dotmailer_api_user');
  }

  /**
   * Returns the field being used by the entity.
   *
   * @return string
   *   An entity field name.
   */
  public function getEntityField() {
    return $this->getSetting('email_property');
  }

  /**
   * Returns the double opt in setting.
   *
   * @return bool
   *   TRUE if you have to double opt in FALSE if not.
   */
  public function getDoubleOptInSetting() {
    return $this->getSetting('double_opt_in');
  }

  /**
   * Returns the contact data fields to be used for accounts.
   *
   * @return array
   *   A list of contact fields or an empty array if no have been filled.
   */
  public function getContactDataFields() {
    $fields = [];
    $contact_data_fields = $this->definition->getSetting('contact_data_fields');
    if (!empty($contact_data_fields)) {
      foreach ($contact_data_fields as $dotmailerContactDataFieldName => $drupalMachineName) {
        if (!empty($drupalMachineName)) {
          $fields[$dotmailerContactDataFieldName] = $drupalMachineName;
        }
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $fieldOptions = [];

    /** @var \Drupal\dotmailer\DotmailerAdminFormElementService $formElementService */
    $formElementService = \Drupal::service('dotmailer.admin_form_elements');

    $settings = [
      'dotmailer_address_book' => $this->getSetting('dotmailer_address_book'),
      'dotmailer_api_user' => $this->getSetting('dotmailer_api_user'),
    ];

    $element = $formElementService->getAdminElements($settings, $form_state);

    $element['dotmailer_api_user']['#ajax'] = [
      'wrapper' => 'dotmailer_address_books_wrapper',
      'callback' => [$this, 'getActiveAddressBooksFromUser'],
    ];

    $element['dotmailer_api_user']['#disabled'] = $has_data;

    $element['dotmailer_address_book']['#disabled'] = $has_data;

    $fields = $this->getEntity()->getFieldDefinitions();
    foreach ($fields as $machine_name => $field) {
      if ($field->getType() == 'email') {
        $fieldOptions[$machine_name] = $field->getLabel() . ' (' . $machine_name . ')';
      }
    }

    $element['email_property'] = [
      '#type' => 'select',
      '#title' => $this->t('Email field'),
      '#multiple' => FALSE,
      '#description' => $this->t('Choose an email field from the entity to send to dotmailer.'),
      '#options' => $fieldOptions,
      '#default_value' => $this->getSetting('email_property'),
      '#required' => TRUE,
      '#disabled' => $has_data,
    ];

    return $element;
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
    $addressBooks = ['' => $this->t('- Select -')];
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
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $instance_settings = $this->definition->getSettings();

    /** @var \Drupal\dotmailer\Entity\DotmailerApiUser $apiUser */
    $apiUser = $this->apiUsers[$this->getApiUser()];

    $contactDataFields = $apiUser->getDotmailerContactFields();

    $addressBook = $this->getSetting('dotmailer_address_book');

    $element['subscribe_checkbox_label'] = [
      '#title' => $this->t('Subscribe Checkbox @address_book Label', [
        '@address_book' => $addressBook,
      ]),
      '#type' => 'textfield',
      '#default_value' => isset($instance_settings['subscribe_checkbox_label']) ? $instance_settings['subscribe_checkbox_label'] : $this->t('Subscribe'),
    ];

    $element['contact_data_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact data fields'),
    ];

    $fields = $this->getEntity()->getFieldDefinitions();
    $fieldOptions = ['' => $this->t('- Select -')];

    foreach ($fields as $manchine_name => $field) {
      $fieldOptions[$manchine_name] = $field->getLabel() . ' (' . $manchine_name . ')';
    }

    while ($contactDataFields->valid()) {
      $item = $contactDataFields->current()->toArray();
      if ($item['visibility'] == 'Public') {
        $element['contact_data_fields'][$item['name']] = [
          '#type' => 'select',
          '#title' => $item['name'],
          '#options' => $fieldOptions,
          '#default_value' => isset($instance_settings['contact_data_fields'][$item['name']]) ? $instance_settings['contact_data_fields'][$item['name']] : '',
        ];
      }
      $contactDataFields->next();
    }

    $element['unsubscribe_on_delete'] = [
      '#title' => $this->t("Unsubscribe on deletion"),
      '#type' => "checkbox",
      '#description' => $this->t('Unsubscribe entities when they are deleted.'),
      '#default_value' => $instance_settings['unsubscribe_on_delete'],
    ];

    return $element;
  }

}
