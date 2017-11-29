<?php

namespace Drupal\dotmailer\Entity;

use DotMailer\Api\DataTypes\ApiContact;
use DotMailer\Api\Rest\NotFoundException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use DotMailer\Api\Container;
use Drupal\dotmailer\ValueObject\ContactDataFieldArray;
use Drupal\dotmailer\ValueObject\EmailAddress;
use Drupal\dotmailer\ValueObject\OptInType;

/**
 * Defines the Dotmailer api user entity.
 *
 * @ConfigEntityType(
 *   id = "dotmailer_api_user",
 *   label = @Translation("Dotmailer api user"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dotmailer\DotmailerApiUserListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dotmailer\Form\DotmailerApiUserForm",
 *       "edit" = "Drupal\dotmailer\Form\DotmailerApiUserForm",
 *       "delete" = "Drupal\dotmailer\Form\DotmailerApiUserDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dotmailer\DotmailerApiUserHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "dotmailer_api_user",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/dotmailer_api_user/{dotmailer_api_user}",
 *     "add-form" = "/admin/structure/dotmailer_api_user/add",
 *     "edit-form" = "/admin/structure/dotmailer_api_user/{dotmailer_api_user}/edit",
 *     "test-form" = "/admin/structure/dotmailer_api_user/{dotmailer_api_user}/test",
 *     "delete-form" = "/admin/structure/dotmailer_api_user/{dotmailer_api_user}/delete",
 *     "addressbooks-form" = "/admin/structure/dotmailer_api_user/{dotmailer_api_user}/address-book",
 *     "collection" = "/admin/structure/dotmailer_api_user"
 *   }
 * )
 */
class DotmailerApiUser extends ConfigEntityBase implements DotmailerApiUserInterface {

  /**
   * The Dotmailer api user ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Resources instance.
   *
   * @var \DotMailer\Api\Resources\Resources
   */
  protected $resources;

  /**
   * The Dotmailer api user label.
   *
   * @var string
   */
  protected $label;

  /**
   * Dotmailer api user email address.
   *
   * @var string
   */
  protected $email;

  /**
   * Assigned address books.
   *
   * @var array
   */
  protected $addressBooks = [];

  /**
   * Dotmailer api user password.
   *
   * @var string
   */
  protected $password;

  /**
   * Dotmailer data fields.
   *
   * @var \Drupal\dotmailer\ValueObject\ContactDataFieldArray
   */
  protected $dataFields;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    if (!empty($this->getEmail()) && !empty($this->getPassword())) {
      $credentials = [
        Container::USERNAME => $this->getEmail(),
        Container::PASSWORD => $this->getPassword(),
      ];

      try {
        $this->resources = Container::newResources($credentials);
      }
      catch (\Exception $exception) {
        drupal_set_message(
          $this->t('The following error has occurred: @message', [
            '@message' => $exception->getMessage(),
          ]),
          'error'
        );
      }

    }

  }

  /**
   * {@inheritdoc}
   */
  public function getDotmailerResources() {
    return $this->resources;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressBooks() {
    return $this->addressBooks;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressBooks(array $addressBooks) {
    $this->addressBooks = $addressBooks;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveAddressBooks() {
    $activeAddressBooks = [];

    $addresses = $this->getAddressBooks();
    if (!empty($addresses)) {
      foreach ($addresses as $address) {
        if (!empty($address)) {
          $activeAddressBooks[$address] = $address;
        }
      }
    }
    return $activeAddressBooks;
  }

  /**
   * {@inheritdoc}
   */
  public function subscribeContact($addressBookId, $emailAddress, $optIn, $subscribed) {
    $emailAddress = new EmailAddress($emailAddress);

    $optInType = 'Single';
    if ($optIn == TRUE) {
      $optInType = 'VerifiedDouble';
    }

    $optInType = new OptInType($optInType);

    $this->createContact($emailAddress, $optInType);

    if ($subscribed) {
      $this->addUserToAddressBook($emailAddress, $addressBookId);
    }

    if (empty($subscribed)) {
      $this->removeUserFromAddressBook($emailAddress, $addressBookId);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function setContactDataFields(ContactDataFieldArray $contactDataFields) {
    $this->dataFields = $contactDataFields;
  }

  /**
   * Checks to see if the email exists as a contact.
   *
   * @param \Drupal\dotmailer\ValueObject\EmailAddress $email
   *   A valid email address object.
   *
   * @return \DotMailer\Api\DataTypes\ApiContact|bool
   *   The api contact or FALSE if one doesn't exist.
   */
  private function doesEmailExistWithContact(EmailAddress $email) {
    $emailAddress = $email->__toString();

    try {
      $apiContact = $this->resources->GetContactByEmail($emailAddress);
    }
    catch (NotFoundException $exception) {
      $apiContact = FALSE;
    }

    return $apiContact;
  }

  /**
   * Checks to see if a contact exists within an address book.
   *
   * @param \Drupal\dotmailer\ValueObject\EmailAddress $email
   *   A valid email address object.
   * @param string $addressBook
   *   The dotmailer address book id.
   *
   * @return bool
   *   TRUE if the contact is found, FALSE otherwise.
   */
  private function doesContactExistInAddressBook(EmailAddress $email, $addressBook) {
    $contacts = $this->resources->GetAddressBookContacts($addressBook)->toArray();
    foreach ($contacts as $contact) {
      if ($contact['email'] == $email->__toString()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Adds a user to an address book.
   *
   * @param \Drupal\dotmailer\ValueObject\EmailAddress $email
   *   A valid email address object.
   * @param string $addressBook
   *   The dotmailer address book id.
   *
   * @return bool
   *   TRUE if we have added a user successfully, FALSE otherwise.
   */
  public function addUserToAddressBook(EmailAddress $email, $addressBook) {
    $apiContact = $this->doesEmailExistWithContact($email);
    if ($this->doesContactExistInAddressBook($email, $addressBook) == FALSE && ($apiContact instanceof ApiContact)) {
      $this->resources->PostAddressBookContacts($addressBook, $apiContact);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Removes a user from an address book.
   *
   * @param \Drupal\dotmailer\ValueObject\EmailAddress $email
   *   A valid email address object.
   * @param string $addressBook
   *   The dotmailer address book id.
   *
   * @return bool
   *   TRUE if we have removed a user successfully, FALSE otherwise.
   */
  public function removeUserFromAddressBook(EmailAddress $email, $addressBook) {
    $apiContact = $this->doesEmailExistWithContact($email);
    if ($this->doesContactExistInAddressBook($email, $addressBook) == TRUE && ($apiContact instanceof ApiContact)) {
      $this->resources->DeleteAddressBookContact($addressBook, $apiContact->Id);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Completely deletes a contact.
   *
   * @param \Drupal\dotmailer\ValueObject\EmailAddress $email
   *   A valid email address object.
   *
   * @return bool
   *   TRUE if we have delete a contact successfully, FALSE otherwise.
   */
  public function deleteContact(EmailAddress $email) {
    $apiContact = $this->doesEmailExistWithContact($email);
    if ($apiContact instanceof ApiContact) {
      $this->resources->DeleteContact($apiContact->Id);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Updates a contact.
   *
   * @param \DotMailer\Api\DataTypes\ApiContact $apiContact
   *   The ApiContact Data type.
   *
   * @return \DotMailer\Api\DataTypes\ApiContact
   *   The updated ApiContact Data type
   */
  public function updateContact(ApiContact $apiContact) {
    return $apiContact;
  }

  /**
   * Gets the data fields for contacts.
   *
   * @return \DotMailer\Api\DataTypes\ApiDataFieldList
   *   A list of dotmailer data fields.
   */
  public function getContactFields() {
    return $this->resources->GetDataFields();
  }

  /**
   * Creates a dotmailer contact if it does not exist.
   *
   * If it does exist then get the current contact.
   *
   * @param \Drupal\dotmailer\ValueObject\EmailAddress $email
   *   A valid email address object.
   * @param \Drupal\dotmailer\ValueObject\OptInType $optInType
   *   A valid optInType.
   * @param string $emailType
   *   A valid email type.
   *
   * @return \DotMailer\Api\DataTypes\ApiContact
   *   A dotmailer contact.
   */
  public function createContact(EmailAddress $email, OptInType $optInType, $emailType = 'Html') {
    $apiContact = $this->doesEmailExistWithContact($email);
    if ($apiContact === FALSE) {
      $apiContact = new ApiContact();
      $apiContact->Email = $email->__toString();
      $apiContact->OptInType = $optInType;
      $apiContact->EmailType = $emailType;
      if ($this->dataFields instanceof ContactDataFieldArray && !empty($this->dataFields->toArray())) {
        $apiContact->DataFields = $this->dataFields->toArray();
      }
      $apiContact = $this->resources->PostContacts($apiContact);
    }

    if ($apiContact instanceof ApiContact && $this->contactNeedsUpdated($apiContact) == TRUE) {
      $this->updateContact($apiContact);
    }

    return $apiContact;
  }

  /**
   * Determines whether or not the contact needs to be updated.
   *
   * @param \DotMailer\Api\DataTypes\ApiContact $apiContact
   *   The ApiContact data type.
   *
   * @return bool
   *   TRUE if so FALSE otherwise.
   */
  public function contactNeedsUpdated(ApiContact $apiContact) {
    return FALSE;
  }

}
