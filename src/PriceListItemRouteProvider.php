<?php

namespace Drupal\commerce_pricelist;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the price list item entity.
 */
class PriceListItemRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $variation_entity_type = $this->entityTypeManager->getDefinition('commerce_product_variation');

    if ($variation_add_price_route = $this->getVariationAddPriceFormRoute($entity_type, $variation_entity_type)) {
      $collection->add('entity.commerce_product_variation.add_price_form', $variation_add_price_route);
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_pricelist' => [
        'type' => 'entity:commerce_pricelist',
      ],
    ]);
    // Replace the "Add price list item" title with "Add price".
    // The t() function is used to ensure the string is picked up for
    // translation, even though _title is supposed to be untranslated.
    $route->setDefault('_title_callback', '');
    $route->setDefault('_title', t('Add price')->getUntranslatedString());

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getEditFormRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_pricelist' => [
        'type' => 'entity:commerce_pricelist',
      ],
    ]);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_pricelist' => [
        'type' => 'entity:commerce_pricelist',
      ],
    ]);
    // AdminHtmlRouteProvider sets _admin_route for all routes except this one.
    $route->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * Gets the variation add price form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The price list item entity type.
   * @param \Drupal\Core\Entity\EntityTypeInterface $product_variation_entity_type
   *   The product variation entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getVariationAddPriceFormRoute(EntityTypeInterface $entity_type, EntityTypeInterface $product_variation_entity_type) {
    if ($product_variation_entity_type->hasLinkTemplate('add-price-form')) {
      $route = new Route($product_variation_entity_type->getLinkTemplate('add-price-form'));
      $entity_type_id = $entity_type->id();
      // Use the add form handler, if available, otherwise default.
      $operation = 'default';
      if ($entity_type->getFormClass('add')) {
        $operation = 'add';
      }
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.{$operation}",
          'entity_type_id' => $entity_type_id,
          '_title_callback' => '',
          '_title' => t('Add price')->getUntranslatedString(),
        ])
        ->setOption('parameters', [
          'commerce_product' => [
            'type' => 'entity:commerce_product',
          ],
          'commerce_product_variation' => [
            'type' => 'entity:commerce_product_variation',
          ],
        ])
        ->setRequirement('_entity_create_access', $entity_type_id)
        ->setRequirement('commerce_product_variation', '\d+')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

}
