<?php

namespace App\Presenters;

use Nette,
    App\Model\LideRepository,
    App\Model\VerzeRepository,
    App\Model\ZmenyRepository;


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

  /**
   * @var ZmenyRepository
   * @inject
   */
  public $zmeny;

  /** @persistent */
  public $pohled = 'dev';

  /** @persistent */
  public $uziv;

  /** @persistent */
  public $filtr;

  public $verzeId;
  public $uzivId;
  public $texy;

  protected $request;
  protected $response;



  public function injectTexy(\Texy $texy) {
    $this->texy = $texy;
  }

  protected function createTemplate($class = NULL) {
    $template = parent::createTemplate($class);
    $template->registerHelper('texy', callback($this->texy, 'process'));
    return $template;
  }

  public function __construct(\Nette\Http\IRequest $request, \Nette\Http\Response $response)
  {
    $this->request = $request;
    $this->response = $response;
  }

  public function startup()
  {
    parent::startup();

    //pokud nejsou parametry v url, načtou se z cookie
    if(!$this->uziv) $this->uziv    = $this->request->getCookie('uziv');
    $this->uziv = strtolower($this->uziv);
    if($this->uziv) {
      $clovek = $this->lide->getBy(array('jmeno' => $this->uziv));
      if(!$clovek) {
        $this->response->setCookie('uziv', '', '-1');
        throw new \Nette\Application\BadRequestException('Neexistující osoba');
      }
      $this->uzivId = $clovek->id;
      $this->response->setCookie('uziv', $this->uziv, '100 days');
    }
  }

  /**
   *
   */
  public function vybratVerzi($id)
  {
    $verze = $this->verze->get($id);
    if(!$verze) {
      $this->response->setCookie('verzeId', '', '-1');
      throw new \Nette\Application\BadRequestException('Neexistující verze');
    }
    $this->verzeId = $id;
    $this->aktualizaceMenu();
    $this->response->setCookie('verzeId', $this->verzeId, '100 days');

    return $verze;
  }

  public function beforeRender()
  {
    $this->template->lide          = $this->lide->seznamLidi();
    $this->template->vydaneVerze   = $this->verze->vydane();
    $this->template->nevydaneVerze = $this->verze->nevydane();

    $this->aktualizaceMenu();
  }

  public function aktualizaceMenu()
  {
    $this->pohled = strtolower($this->pohled);

    $this->template->uziv    = $this->uziv;
    $this->template->uzivId  = $this->uzivId;
    $this->template->pohled  = $this->pohled;
    $this->template->verzeId = $this->verzeId;
    $this->template->filtr   = $this->filtr;

    $this->template->zmenyBezTestera   = null;
    $this->template->neotestovaneZmeny = null;
    $this->template->autorovyZmeny     = null;
    $this->template->testerovyZmeny    = null;
    if($this->verzeId && $this->uzivId && $this->pohled == 'dev') {
      $this->template->autorovyZmeny = $this->zmeny->zmenyVeVerzi($this->verzeId, $this->uzivId)->count();
      $this->template->neotestovaneZmeny = $this->zmeny->neotestovane($this->verzeId, $this->uzivId)->count();
    }
    if($this->verzeId && $this->uzivId && $this->pohled == 'test') {
      $this->template->testerovyZmeny = $this->zmeny->zmenyVeVerzi($this->verzeId, null, $this->uzivId)->count();
      $this->template->neotestovaneZmeny = $this->zmeny->neotestovane($this->verzeId, null, $this->uzivId)->count();
    }
    if($this->verzeId && $this->pohled == 'boss') {
      $this->template->zmenyBezTestera = $this->zmeny->bezTestera($this->verzeId)->count();
      $this->template->neotestovaneZmeny = $this->zmeny->neotestovane($this->verzeId)->count();
    }
  }
}
