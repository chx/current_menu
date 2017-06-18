<?php

namespace Drupal\current_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\current_menu\Cache\CurrentMenuCacheContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the current menu block
 *
 * @Block(
 *   id = "current_menu_block",
 *   admin_label = @Translation("Current menu block"),
 *   category = @Translation("Menus"),
 * )
 */
class CurrentMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * @var string
   */
  protected $menuName;

  /**
   * @var \Drupal\current_menu\Cache\CurrentMenuCacheContext
   */
  protected $currentMenuCacheContext;

  /**
   * CurrentMenuBlock constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\Core\Entity\EntityStorageInterface $menuLinkStorage
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuTree
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentMenuCacheContext $currentMenuCacheContext, MenuLinkTreeInterface $menuTree)  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentMenuCacheContext = $currentMenuCacheContext;
    $this->menuTree = $menuTree;
  }


  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache_context.current_menu'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu prefix'),
      '#default_value' => $this->configuration['prefix'],
      '#description' => $this->t('The block is only visible if the current page has a link in a menu which starts with this prefix'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['prefix'] = $form_state->getValue('prefix');
  }

  public function defaultConfiguration() {
    return ['prefix' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->getMenuName()) {
      $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($this->getMenuName());
      $tree = $this->menuTree->load($this->getMenuName(), $parameters);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $this->menuTree->transform($tree, $manipulators);
      $build = $this->menuTree->build($tree);
      $build['#contextual_links'] = [
        'menu' => [
          'route_parameters' => ['menu' => $this->getMenuName()]
        ],
      ];
      return $build;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Even when the menu block renders to the empty string for a user, we want
    // the cache tag for this menu to be set: whenever the menu is changed, this
    // menu block must also be re-rendered for that user, because maybe a menu
    // link that is accessible for that user has been added.
    $cache_tags = parent::getCacheTags();
    if ($this->getMenuName()) {
      $cache_tags[] = 'config:system.menu.' . $this->getMenuName();
    }
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // ::build() uses MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters()
    // to generate menu tree parameters, and those take the active menu trail
    // into account. Therefore, we must vary the rendered menu by the active
    // trail of the rendered menu.
    // Additional cache contexts, e.g. those that determine link text or
    // accessibility of a menu, will be bubbled automatically.
    $cacheContexts = Cache::mergeContexts(parent::getCacheContexts(), ['current_menu:' .  $this->configuration['prefix']]);
    if ($this->getMenuName()) {
      $cacheContexts = Cache::mergeContexts($cacheContexts, ['route.menu_active_trails:' . $this->getMenuName()]);
    }
    return $cacheContexts;
  }

  /**
   * @return string
   */
  protected function getMenuName() {
    if (!isset($this->menuName)) {
      $this->menuName = $this->currentMenuCacheContext->getContext($this->configuration['prefix']);
    }

    return $this->menuName;
  }

}
