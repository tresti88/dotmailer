<?php

namespace Drupal\dotmailer;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Dotmailer api user entities.
 */
class DotmailerApiUserListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Dotmailer api user');
    $header['id'] = $this->t('Machine name');
    $header['email'] = $this->t('Email');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['email'] = $entity->getEmail();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    if ($entity->hasLinkTemplate('test-form')) {
      $operations['test'] = [
        'title' => t('Test user'),
        'weight' => 10,
        'url' => $entity->toUrl('test-form'),
      ];
    }

    if ($entity->hasLinkTemplate('addressbooks-form')) {
      $operations['address_books'] = [
        'title' => t('Active Address book/s'),
        'weight' => 10,
        'url' => $entity->toUrl('addressbooks-form'),
      ];
    }

    return $operations;
  }

}
