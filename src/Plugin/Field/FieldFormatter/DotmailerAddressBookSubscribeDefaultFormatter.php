<?php

namespace Drupal\dotmailer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * @todo comment here
 *
 * @FieldFormatter(
 *   id = "dotmailer_address_book_subscribe_default",
 *   label = @Translation("Default settings for address books"),
 *   field_types = {
 *     "dotmailer_address_book_subscribe"
 *   }
 * )
 */
class DotmailerAddressBookSubscribeDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_address_books' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays subscription options within the entity.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element = [];
    foreach ($items as $delta => $item) {
      $message = $this->t('Not subscribed to list: @address_book_id', [
        '@address_book_id' => $item->address_book_id,
      ]);
      if ($item->getSubscribed()) {
        $message = $this->t('Subscribed to list: @address_book_id', [
          '@address_book_id' => $item->address_book_id,
        ]);
      }
      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $message,
      ];
    }

    return $element;

  }

}
