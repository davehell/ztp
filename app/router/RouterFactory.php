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
  public function createRouter()
  {
    $router = new RouteList();
    //$router[] = new Route('<presenter>[/<verzeId>]/<action>[/<id>]', 'Verze:seznam');
    $router[] = new Route('<presenter>/<action> ? verze=<verzeId>', 'Verze:default');
    return $router;
  }

}
