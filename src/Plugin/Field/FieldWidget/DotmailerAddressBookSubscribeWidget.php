<?php

namespace Drupal\dotmailer\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Used for displaying field info.
 *
 * @FieldWidget(
 *   id = "dotmailer_address_book_subscribe_widget",
 *   label = @Translation("Subscribe to an address book"),
 *   field_types = {
 *     "dotmailer_address_book_subscribe"
 *   },
 *   settings = {
 *     "placeholder" = "Select a Dotmailer address book."
 *   }
 * )
 */
class DotmailerAddressBookSubscribeWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $instance = $items[0];
    $element = [];
    $addressBook = explode('-', $this->fieldDefinition->getSetting('dotmailer_address_book'));
    $label = $this->fieldDefinition->getSetting('subscribe_checkbox_label');
    $subscribe_default = $instance->getSubscribed();

    $element['subscribed'] = [
      '#title' => $label ?: $this->t('Subscribe'),
      '#type' => 'checkbox',
      '#default_value' => ($subscribe_default) ? TRUE : $this->fieldDefinition->isRequired(),
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    $element['address_book_id'] = [
      '#type' => 'hidden',
      '#value' => $addressBook[0],
    ];

    return $element;
  }

}
