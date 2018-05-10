<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\commerce\EntityTraitManagerInterface;
use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PriceListTypeForm.
 *
 * @package Drupal\commerce_pricelist\Form
 */
class PriceListTypeForm extends CommerceBundleEntityFormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new CommerceBundleEntityFormBase object.
   *
   * @param \Drupal\commerce\EntityTraitManagerInterface $trait_manager
   *   The entity trait manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTraitManagerInterface $trait_manager, MessengerInterface $messenger) {
    parent::__construct($trait_manager);
    $this->traitManager = $trait_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_entity_trait'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_pricelist\Entity\PriceListTypeInterface $price_list_type */
    $price_list_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $price_list_type->label(),
      '#description' => $this->t("Label for the Price list type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $price_list_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_pricelist\Entity\PriceListType::load',
      ],
      '#disabled' => !$price_list_type->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $price_list_type->getDescription(),
    ];

    $form = $this->buildTraitForm($form, $form_state);

    /* You will need additional form elements for your custom properties. */

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $price_list_type = $this->entity;
    $status = $price_list_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Price list type.', [
          '%label' => $price_list_type->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Price list type.', [
          '%label' => $price_list_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($price_list_type->urlInfo('collection'));
  }

}
