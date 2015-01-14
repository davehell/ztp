<?php

use Nette\Application\UI;

/**
 * Komponenta na přiřazení testera ke změně
 */
class TesteriControl extends UI\Control
{

  /** @var array of function ($tester, $zmena) */
  public $onPrirazeni;

  private $lide = null;

  public function __construct($lide)
  {
    parent::__construct();
    barDump($lide);
    $this->lide = $lide;
  }


  public function handlePrirazeni($tester, $zmena)
  {
    $this->onPrirazeni($tester, $zmena);
  }

  public function render($zmena = null)
  {
    $template = $this->template;
    $template->lide = $this->lide;
    $template->zmena = $zmena;
    $template->render(__DIR__ . '/TesteriControl.latte');
  }
}
