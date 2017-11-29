<?php

namespace Drupal\dotmailer\ValueObject;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\dotmailer\Exception\DotmailerBadContactDataFieldArrayException;

/**
 * Class ContactDataFieldArray.
 *
 * @package Drupal\dotmailer
 */
class ContactDataFieldArray {

  /**
   * A contact field data array.
   *
   * @var array
   *   An key-value paired array, the key must be a string and the value can be
   *   text, date, bool or numeric.
   */
  private $contactDataArray = [];

  /**
   * ContactDataFieldArray constructor.
   *
   * @param array $dataArray
   *   The data array.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The fieldable entity.
   *
   * @throws \Drupal\dotmailer\Exception\DotmailerBadContactDataFieldArrayException
   *   If validation fails the data array supplied is invalid.
   */
  public function __construct(array $dataArray, FieldableEntityInterface $entity) {

    // @todo some more checks here to make sure we have a valid array.
    $arrayDepth = $this->arrayDepth($dataArray);

    if ($arrayDepth > 2) {
      throw new DotmailerBadContactDataFieldArrayException("Invalid array depth $arrayDepth supplied");
    }

    $this->contactDataArray = $this->processDataFields($dataArray, $entity);
  }

  /**
   * Measures the depth of the array.
   *
   * @param array $array
   *   The array in question.
   *
   * @return int
   *   The depth number of the array.
   */
  private function arrayDepth(array $array) {
    $maxDepth = 1;
    foreach ($array as $value) {
      if (is_array($value)) {
        $depth = $this->arrayDepth($value) + 1;

        if ($depth > $maxDepth) {
          $maxDepth = $depth;
        }
      }
    }

    return $maxDepth;
  }

  /**
   * Maps the entities data field values with the dotamiler fields.
   *
   * @param array $dataFields
   *   An array of data fields.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A drupal entity.
   *
   * @return array
   *   An array suitable for the dotmailer api.
   */
  private function processDataFields(array $dataFields, FieldableEntityInterface $entity) {
    $validDataFields = [];
    foreach ($dataFields as $dotmailerDataFieldKey => $drupalMachineNameField) {
      $validDataFields[] = [
        'key' => $dotmailerDataFieldKey,
        'value' => $entity->get($drupalMachineNameField)->value,
      ];
    }
    return $validDataFields;
  }

  /**
   * Getter method.
   *
   * @return array
   *   The opt in string.
   */
  public function toArray() {
    return $this->contactDataArray;
  }

}
