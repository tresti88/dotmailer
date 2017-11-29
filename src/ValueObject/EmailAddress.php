<?php

namespace Drupal\dotmailer\ValueObject;

use Drupal\dotmailer\Exception\DotmailerBadEmailAddressException;

/**
 * Class EmailAddress.
 *
 * @package Drupal\dotmailer
 */
class EmailAddress {

  /**
   * An email address property.
   *
   * @var string
   *   Email address value.
   */
  private $emailAddress;

  /**
   * EmailAddress constructor.
   *
   * @param string $value
   *   The email address.
   *
   * @throws \Drupal\dotmailer\Exception\DotmailerBadEmailAddressException
   *   If validation fails ie not a valid email.
   */
  public function __construct($value) {

    $valid = \Drupal::service('email.validator')->isValid($value);

    if ($valid == FALSE) {
      throw new DotmailerBadEmailAddressException("Email address $value is not valid");
    }

    $this->emailAddress = $value;

  }

  /**
   * Get method.
   *
   * @return string
   *   The email address string.
   */
  private function get() {
    return $this->emailAddress;
  }

  /**
   * Returns the email address as a string.
   *
   * @return string
   *   The email address string.
   */
  public function __toString() {
    return $this->get();
  }

}
