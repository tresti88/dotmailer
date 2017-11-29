<?php

namespace Drupal\dotmailer\ValueObject;

use Drupal\dotmailer\Exception\DotmailerBadOptInTypeException;

/**
 * Class OptInType.
 *
 * @package Drupal\dotmailer
 */
class OptInType {

  /**
   * An opt in property.
   *
   * @var string
   *   Opt in value.
   */
  private $optInType;

  /**
   * OptInType constructor.
   *
   * @param string $value
   *   The opt in type.
   *
   * @throws \Drupal\dotmailer\Exception\DotmailerBadOptInTypeException
   *   If validation fails ie not a opt in type.
   */
  public function __construct($value) {

    // Valid options taken from Dotmailer api.
    // See https://developer.dotmailer.com/docs/add-contact-to-address-book.
    $validOptions = ['Unknown', 'Single', 'Double', 'VerifiedDouble'];

    if (!in_array($value, $validOptions)) {
      throw new DotmailerBadOptInTypeException("Opt in type $value is not valid");
    }

    $this->optInType = $value;
  }

  /**
   * Getter method.
   *
   * @return string
   *   The opt in string.
   */
  private function get() {
    return $this->optInType;
  }

  /**
   * Returns the opt in type as a string.
   *
   * @return string
   *   The opt in string.
   */
  public function __toString() {
    return $this->get();
  }

}
