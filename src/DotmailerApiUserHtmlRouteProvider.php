<?php

namespace Drupal\dotmailer;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Dotmailer api user entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class DotmailerApiUserHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $entity_type_id = $entity_type->id();

    if ($testRoute = $this->getTestRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.test_form", $testRoute);
    }

    if ($addressbooksRoute = $this->getaddressbooksRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.addressbooks_form", $addressbooksRoute);
    }

    return $collection;
  }

  /**
   * Gets the test route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getTestRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('test-form')) {
      $route = new Route($entity_type->getLinkTemplate('test-form'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\dotmailer\Form\DotmailerApiUserTestForm',
          '_title' => 'Test api user',
        ])
        ->setRequirement('_permission', 'access content')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the address book route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getaddressbooksRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('addressbooks-form')) {
      $route = new Route($entity_type->getLinkTemplate('addressbooks-form'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\dotmailer\Form\DotmailerApiUserActiveaddressbooksForm',
          '_title' => 'Active Address book',
        ])
        ->setRequirement('_permission', 'access content')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
    return NULL;
  }

}
