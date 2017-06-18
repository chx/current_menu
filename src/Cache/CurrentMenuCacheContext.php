<?php

namespace Drupal\current_menu\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class CurrentMenuCacheContext implements CacheContextInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * @var array
   */
  protected $cache;

  public function __construct(RouteMatchInterface $routeMatch, MenuLinkManagerInterface $menuLinkManager) {
    $this->routeMatch = $routeMatch;
    $this->menuLinkManager = $menuLinkManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Current menu');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($prefix = NULL, $op = 'STARTS_WITH') {
    if ($routeName = $this->routeMatch->getRouteName()) {
      $n = strlen($prefix);
      $prefix = (string) $prefix;
      $routeParameters = $this->routeMatch->getRawParameters()->all();
      asort($routeParameters);
      $key = serialize($routeParameters);
      $storage = &$this->cache[$routeName][$key][$prefix][$op];
      if (!isset($storage)) {
        $storage = '';
        foreach ($this->menuLinkManager->loadLinksByRoute($routeName, $routeParameters) as $link) {
          $actual = $op === 'STARTS_WITH' ? substr($link->getMenuName(), 0, $n) : $link->getMenuName();
          if ($actual === $prefix) {
            $storage = $link->getMenuName();
            break;
          }
        }
      }
      return $storage;
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
