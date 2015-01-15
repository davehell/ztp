<?php

namespace App;

use Nette\Application\Routers\RouteList,
  Nette\Application\Routers\Route;


/**
 * Router factory.
 */
class RouterFactory
{

  /**
   * @return \Nette\Application\IRouter
   */
  public function createRouter($isSecured = false)
  {
    $flags = ($isSecured ? Route::SECURED : 0);
    $router = new RouteList();
    $router[] = new Route('<uziv [a-z]{3}>/<presenter>/<verzeId>/<action>', 'Verze:default', $flags);
    $router[] = new Route('<presenter>/<action> ? verze=<verzeId>', 'Verze:default', $flags);
    return $router;
  }

}
