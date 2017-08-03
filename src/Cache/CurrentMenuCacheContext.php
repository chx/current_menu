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
    return $this->getCurrentMenuFromRouteMatch($this->routeMatch, $prefix, $op);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * @param $prefix
   * @param $op
   * @param $routeName
   * @param $routeParameters
   *
   * @return string
   */
  protected function getMenuName($prefix, $op, $routeName, $routeParameters) {
    $n = strlen($prefix);
    foreach ($this->menuLinkManager->loadLinksByRoute($routeName, $routeParameters) as $link) {
      $menuName = $link->getMenuName();
      $comparisonValue = $menuName;
      if ($op === 'STARTS_WITH') {
        $comparisonValue = substr($comparisonValue, 0, $n);
      }
      if ($comparisonValue === $prefix) {
        return $menuName;
      }
    }
    return '';
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   * @param $prefix
   * @param $op
   * @return string
   * @internal param $return
   */
  public function getCurrentMenuFromRouteMatch(RouteMatchInterface $routeMatch, $prefix = NULL, $op = 'STARTS_WITH') {
    $return = '';
    if ($routeName = $routeMatch->getRouteName()) {
      $prefix = (string) $prefix;
      $routeParameters = $this->routeMatch->getRawParameters()->all();
      asort($routeParameters);
      $return = &$this->cache[$routeName][serialize($routeParameters)][$prefix][$op];
      if (!isset($return)) {
        $return = $this->getMenuName($prefix, $op, $routeName, $routeParameters);
      }
    }
    return $return;
  }

}
