<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Defines the inline form for product variations.
 */
class PriceListItemInlineForm extends EntityInlineForm {

  /**
   * The loaded variation types.
   *
   * @var \Drupal\commerce_pricelist\Entity\PriceListItemInterface[]
   */
  protected $variationTypes;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface[]
   */
  protected $routeMatch;

  /**
   * Constructs the inline entity form controller.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The entity type.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, EntityTypeInterface $entity_type, RouteMatchInterface $route_match) {
    parent::__construct($entity_field_manager, $entity_type_manager, $module_handler, $entity_type);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $entity_type,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => t('price list item'),
      'plural' => t('price list items'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);
    $fields['price'] = [
      'type' => 'field',
      'label' => t('Price'),
      'weight' => 3,
    ];
    $fields['quantity'] = [
      'type' => 'field',
      'label' => t('Quantity'),
      'weight' => 4,
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);

    // This is for widget entity_auto_complete.
    if (isset($entity_form['purchased_entity']['widget'][0])) {
      $entity_form['purchased_entity']['widget'][0]['target_id']['#ajax'] = [
        'callback' => [get_class($this), 'purchasedRefresh'],
        'event' => 'autocompleteclose',
        'wrapper' => $entity_form['#ief_row_delta'] . 'purchased_entity_refresh',
      ];
    }
    // This is for widget select similarity.
    else {
      $entity_form['purchased_entity']['widget']['#ajax'] = [
        'callback' => [get_class($this), 'purchasedRefresh'],
        'wrapper' => $entity_form['#ief_row_delta'] . 'purchased_entity_refresh',
      ];
    }

    $entity_form = $this->priceForm($entity_form, $form_state);
    $entity_form['price']['#attributes']['id'] = $entity_form['#ief_row_delta'] . 'purchased_entity_refresh';
    return $entity_form;
  }

  /**
   * Handle the disable property of price.
   *
   * @param array $entity_form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   *
   * @return array
   *   The entity form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function priceForm(array $entity_form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_pricelist\Entity\PriceListItem $entity */
    $entity = $entity_form['#entity'];
    $entity_form['price']['widget']['#disabled'] = FALSE;
    if ($entity->hasPurchasedEntity()) {
      $purchasedEntity = $entity->getPurchasedEntity();
    }
    $userInput = $form_state->getUserInput();
    // When the operation is 'add'.
    if ($entity_form['#op'] == 'add') {
      if (isset($userInput['field_price_list_item']['form']['inline_entity_form']['purchased_entity'])) {
        $purchasedEntityId = $userInput['field_price_list_item']['form']['inline_entity_form']['purchased_entity'];
      }
      if (isset($purchasedEntityId[0]['target_id'])) {
        $purchasedEntityId = EntityAutocomplete::extractEntityIdFromAutocompleteInput($purchasedEntityId[0]['target_id']);
      }
    }
    // When the operation is 'edit'.
    else {
      if (isset($userInput['field_price_list_item']['form']['inline_entity_form']['entities'])) {
        $entities = $userInput['field_price_list_item']['form']['inline_entity_form']['entities'];
      }
      if (isset($entities[$entity_form['#ief_row_delta']]['form']['purchased_entity'])) {
        $purchasedEntityId = $entities[$entity_form['#ief_row_delta']]['form']['purchased_entity'];
        // If the widget type is entity_auto_complete, we can just get entity id
        // by using regular, because the entity_id can't be get directly in
        // inline_entity_form.
        if (isset($purchasedEntityId[0]['target_id'])) {
          $purchasedEntityId = EntityAutocomplete::extractEntityIdFromAutocompleteInput($purchasedEntityId[0]['target_id']);
        }
      }
    }

    if (isset($purchasedEntityId)) {
      $purchasedEntityTargetType = $entity->getFieldDefinition('purchased_entity')->getSetting('target_type');
      $purchasedEntity = $this->entityTypeManager->getStorage($purchasedEntityTargetType)->load($purchasedEntityId);
    }

    if (isset($purchasedEntity)) {
      $price = $purchasedEntity->getPrice();
      if (!$price) {
        $entity_form['price']['widget']['#disabled'] = TRUE;
      }
    }
    return $entity_form;
  }

  /**
   * Auto get the purchased entity's price.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   *
   * @return array
   *   The price element.
   */
  public static function purchasedRefresh(array $form, FormStateInterface $form_state) {
    $element = [];
    $triggering_element = $form_state->getTriggeringElement();

    // Remove the action and the actions container.
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -2);
    while (!(isset($element['#type']) && ($element['#type'] == 'inline_entity_form'))) {
      $element = NestedArray::getValue($form, $array_parents);
      array_pop($array_parents);
    }

    $elementPrice = $element['price'];

    $targetType = $element['#entity']->getFieldDefinition('purchased_entity')->getSetting('target_type');
    $fieldPriceListItem = $form_state->getValue('field_price_list_item');

    // When the operation is 'add'.
    if ($element['#op'] == 'add') {
      if (isset($fieldPriceListItem['form']['inline_entity_form']['purchased_entity'][0]['target_id'])) {
        $purchasedEntityId = $fieldPriceListItem['form']['inline_entity_form']['purchased_entity'][0]['target_id'];
      }
    }
    // When the operation is 'edit'.
    else {
      $entities = $fieldPriceListItem['form']['inline_entity_form']['entities'];
      if (isset($entities[$element['#ief_row_delta']]['form']['purchased_entity'][0]['target_id'])) {
        $purchasedEntityId = $entities[$element['#ief_row_delta']]['form']['purchased_entity'][0]['target_id'];
      }
    }

    if (isset($purchasedEntityId)) {
      // Because there's no object($this) in the ajax callback when we use
      // inline_entity_form, so we can't use $this->entityTypeManager directly.
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchasedEntity */
      $purchasedEntity = \Drupal::entityTypeManager()->getStorage($targetType)->load($purchasedEntityId);
      $price = $purchasedEntity->getPrice();
      if (!is_null($price)) {
        $elementPrice['widget'][0]['number']['#value'] = sprintf("%.2f", $price->getNumber());
      }
      else {
        $elementPrice['widget'][0]['number']['#value'] = NULL;
        $elementPrice['widget'][0]['number']['#placeholder'] = NULL;
      }
    }
    return $elementPrice;
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    $product   = $entity->getPurchasedEntity();
    $priceList = $entity->getPriceList();

    // Set quantity if quantity is null.
    if (!$entity->getQuantity()) {
      $entity->setQuantity(1);
    }

    // Set price if price is null.
    if ($product && !$entity->getPrice()) {
      if ($product->getPrice()) {
        $entity->setPrice($product->getPrice());
      }
    }

    $entity->save();
    $entity_id = $entity->id();

    if ($product && $product->field_price_list_item) {
      $target_id = [];
      $field_price_list_item = $product->field_price_list_item->getValue();
      foreach ($field_price_list_item as $item) {
        $target_id[] = $item['target_id'];
      }
      if (!in_array($entity_id, $target_id)) {
        $product->field_price_list_item[] = ['target_id' => $entity_id];
        $product->save();
      }
    }

    if ($priceList && $priceList->field_price_list_item) {
      $target_id = [];
      $field_price_list_item = $priceList->field_price_list_item->getValue();
      foreach ($field_price_list_item as $item) {
        $target_id[] = $item['target_id'];
      }
      if (!in_array($entity_id, $target_id)) {
        $priceList->field_price_list_item[] = ['target_id' => $entity_id];
        $priceList->save();
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getEntityLabel(EntityInterface $entity) {
    return is_null($entity->label()) ? (empty($entity->getPurchasedEntity()) ? NULL : $entity->getPurchasedEntity()->label()) : $entity->label();
  }

}
