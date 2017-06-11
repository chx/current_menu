<?php

namespace Drupal\current_menu\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

class CurrentMenuCacheContext implements CacheContextInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(RouteMatchInterface $routeMatch, PathMatcherInterface $pathMatcher, EntityTypeManagerInterface $entityTypeManager) {
    $this->routeMatch = $routeMatch;
    $this->pathMatcher = $pathMatcher;
    $this->entityTypeManager = $entityTypeManager;
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
      $menuLinkStorage = $this->entityTypeManager->getStorage('menu_link_content');
      $menuLLinkIds = $menuLinkStorage->getQuery()
        ->condition('link.uri', $uri)
        ->condition('menu_name', (string) $prefix, $op)
        ->range(0, 1)
        ->execute();
      /** @var \Drupal\Core\Menu\MenuLinkInterface $menuLink */
      if ($menuLLinkIds && ($menuLink = $menuLinkStorage->load(reset($menuLLinkIds)))) {
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
