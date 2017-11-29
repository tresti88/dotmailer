<?php

namespace Drupal\dotmailer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\dotmailer\ValueObject\ContactDataFieldArray;

/**
 * Provides an interface for defining Dotmailer api user entities.
 */
interface DotmailerApiUserInterface extends ConfigEntityInterface {

  /**
   * Returns a new Resources instance based on that Rest Client instance.
   *
   * @return \DotMailer\Api\Resources\Resources
   *   A new Resources instance.
   */
  public function getDotmailerResources();

  /**
   * Returns the email address of the api user.
   *
   * @return string
   *   Email address.
   */
  public function getEmail();

  /**
   * Returns the password of the api user.
   *
   * @return string
   *   Password.
   */
  public function getPassword();

  /**
   * Returns the password of the api user.
   *
   * @return array
   *   Array of assigned address books keyed by the Dotmailer address book id.
   */
  public function getAddressBooks();

  /**
   * Setter method for setting address books.
   *
   * @param array $addressBooks
   *   An array of assigned address books keyed by the
   *   Dotmailer address book id.
   */
  public function setAddressBooks(array $addressBooks);

  /**
   * Returns the address books to be used by the user.
   *
   * @return array
   *   An empty array if no options have been selected or an array of address
   *   books.
   */
  public function getActiveAddressBooks();

  /**
   * DotmailerAddressBookSubscribeFieldUpdatedEvent constructor.
   *
   * @param string $addressBookId
   *   The dotmailer address book id.
   * @param string $emailAddress
   *   A valid email address object containing a valid email address.
   * @param bool $optIn
   *   TRUE if double opt in FALSE otherwise.
   * @param bool $subscribed
   *   Whether or not the user has subscribed or not.
   */
  public function subscribeContact($addressBookId, $emailAddress, $optIn, $subscribed);

  /**
   * Sets contact data fields.
   *
   * @param \Drupal\dotmailer\ValueObject\ContactDataFieldArray $contactDataFields
   *   The contact data fields as an array of type ContactDataFieldArray.
   */
  public function setContactDataFields(ContactDataFieldArray $contactDataFields);

}
