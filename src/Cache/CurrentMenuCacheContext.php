<?php

namespace Drupal\current_menu\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

class CurrentMenuCacheContext implements CacheContextInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  public function __construct(RouteMatchInterface $routeMatch, PathMatcherInterface $pathMatcher, EntityStorageInterface $menuLinkStorage) {
    $this->routeMatch = $routeMatch;
    $this->pathMatcher = $pathMatcher;
    $this->menuLinkStorage = $menuLinkStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('User');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($prefix = NULL) {
    if ($this->pathMatcher->isFrontPage()) {
      $uri = 'internal:/';
    }
    elseif ($routeName = $this->routeMatch->getRouteName()) {
      if ($routeName == 'entity.node.canonical') {
        $uri = 'entity:node/' . $this->routeMatch->getRawParameter('node');
      }
      else {
        $uri = 'internal:' . Url::fromRouteMatch($this->routeMatch)->toString();
      }
    }
    if (isset($uri)) {
      $menuLLinkIds = $this->menuLinkStorage->getQuery()
        ->condition('link.uri', $uri)
        ->condition('menu_name', (string) $prefix, 'STARTS_WITH')
        ->range(0, 1)
        ->execute();
      if ($menuLLinkIds) {
        /** @var \Drupal\Core\Menu\MenuLinkInterface $menuLink */
        $menuLink = $this->menuLinkStorage->load(reset($menuLLinkIds));
        return $menuLink->getMenuName();
      }
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
