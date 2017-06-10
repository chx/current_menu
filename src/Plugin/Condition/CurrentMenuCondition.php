<?php

namespace Drupal\menu_condition\Plugin\condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Current Menu' condition.
 *
 * @Condition(
 *   id = "current_menu",
 *   label = @Translation("Current Menu"),
 * )
 */
class CurrentMenuCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['menu' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $menus = array_map(function (MenuInterface $menu) {
      return $menu->label();
    }, $this->getMenuStorage()->loadMultiple());
    $form['menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#default_value' => $this->configuration['menu'],
      '#options' => array_diff_key($menus, menu_list_system_menus()),
      '#empty_option' => t('None'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['menu'] = $form_state->getValue('menu');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (!$this->configuration['menu']) {
      return TRUE;
    }
    return $this->getMenuLinkStorage()->getQuery()
      ->condition('link.uri', \Drupal::request()->getPathInfo())
      ->condition('menu_name', $this->configuration['menu'])
      ->range(0, 1)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $args = ['@menu' => $this->getMenuStorage()->load($this->configuration['menu'])->label()];
    if ($this->isNegated()) {
      return $this->t('The current page does not have a menu link in @menu', $args);
    }

    return $this->t('The current page has a menu link in @menu', $args);
  }

  /**
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  public function getMenuStorage() {
    return $this->entityTypeManager->getStorage('menu');
  }


  /**
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  public function getMenuLinkStorage() {
    return $this->entityTypeManager->getStorage('menu_link_content');
  }

}
