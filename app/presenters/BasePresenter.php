<?php

namespace App\Presenters;

use Nette,
    App\Model\LideRepository,
    App\Model\VerzeRepository;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
  /**
   * @var LideRepository
   * @inject
   */
  public $lide;

  /**
   * @var VerzeRepository
   * @inject
   */
  public $verze;


  /** @persistent */
  public $pohled;

  /** @persistent */
  public $verzeId;

  /** @persistent */
  public $uziv;
  public $uzivId;

  public function beforeRender()
  {
    $this->uziv = strtolower($this->uziv);
    $this->pohled = strtolower($this->pohled);

    if($this->uziv) {
      $this->uzivId = $this->lide->getBy(array('jmeno' => $this->uziv))->id;
    }

  	$this->template->lide          = $this->lide->seznamLidi();
    $this->template->testeri       = $this->lide->seznamTesteru();
  	$this->template->vydaneVerze   = $this->verze->vydane();
  	$this->template->nevydaneVerze = $this->verze->nevydane();

    $this->template->uziv    = $this->uziv;
    $this->template->uzivId  = $this->uzivId;
    $this->template->pohled  = $this->pohled ? $this->pohled : 'dev';
    $this->template->verzeId = $this->verzeId;


  }
}
