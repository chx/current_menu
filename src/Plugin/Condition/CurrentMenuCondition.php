<?php

namespace Drupal\current_menu\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\current_menu\Cache\CurrentMenuCacheContext;
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

  /**
   * @var \Drupal\current_menu\Cache\CurrentMenuCacheContext
   */
  protected $currentMenuCacheContext;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentMenuCacheContext $cacheContext, EntityStorageInterface $menuStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentMenuCacheContext = $cacheContext;
    $this->menuStorage = $menuStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache_context.current_menu'),
      $container->get('entity_type.manager')->getStorage('menu')
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
    }, $this->menuStorage->loadMultiple());
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
    return $this->currentMenuCacheContext->getContext($this->configuration['menu'], '=');
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $args = ['@menu' => $this->menuStorage->load($this->configuration['menu'])->label()];
    if ($this->isNegated()) {
      return $this->t('The current page does not have a menu link in @menu', $args);
    }

    return $this->t('The current page has a menu link in @menu', $args);
  }

}
