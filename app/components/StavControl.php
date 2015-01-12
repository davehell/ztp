<?php

use Nette\Application\UI;

/**
 * Komponenta na vypsání stavu změny / chyby
 */
class StavControl extends UI\Control
{

  public function __construct()
  {
    parent::__construct();
  }

  public function render($stav)
  {
    $template = $this->template;
    $template->stav = $stav;
    $template->render(__DIR__ . '/StavControl.latte');
  }
}
