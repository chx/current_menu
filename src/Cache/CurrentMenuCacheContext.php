<?php

namespace Drupal\current_menu\Cache;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\RouteMatchInterface;

class CurrentMenuCacheContext implements CacheContextInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  public function __construct(RouteMatchInterface $routeMatch, Connection $connection) {
    $this->routeMatch = $routeMatch;
    $this->connection = $connection;
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
      $paramKey = '';
      if ($routeParameters = $this->routeMatch->getRawParameters()->all()) {
        asort($routeParameters);
        $paramKey = UrlHelper::buildQuery($routeParameters);
      }
      $query = $this->connection->select('menu_tree', 'm');
      $query->addField('m', 'menu_name');
      $query->condition('route_name', $routeName);
      $query->condition('route_param_key', $paramKey);
      if ($prefix) {
        $query->condition('menu_name', $this->connection->escapeLike($prefix) . '%', 'LIKE');
      }
      $query->range(0, 1);
      return $query->execute()->fetchField();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
