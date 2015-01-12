<?php

use Nette\Application\UI;

/**
 * Komponenta pro výpis chyb u změny
 */
class ChybyControl extends UI\Control
{

  protected $chybyRepo;

  /** @var array of function ($chyba, $funguje) */
  public $onOpraveno;

  public $pohled;

  public function __construct($chybyRepo)
  {
    parent::__construct();
    $this->chybyRepo = $chybyRepo;
  }

  public function handleOpraveno($id, $opraveno)
  {
    $this->onOpraveno($id, $opraveno);
  }

  public function render($chybaId)
  {
    $template = $this->template;
    $template->chyby = $this->chybyRepo->chybyVeZmene($chybaId);
    $template->pohled = $this->pohled;
    $template->render(__DIR__ . '/ChybyControl.latte');
  }

  /**
   * Továrnička na vytvoření komponenty pro výpis stavu chyby
   * @return StavControl
   */
  protected function createComponentStav()
  {
    $stav = new \StavControl();
    $stav->redrawControl();
    return $stav;
  }
}
