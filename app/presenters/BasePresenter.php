<?php

namespace App\Presenters;

use Nette,
    App\Model\LideRepository,
    App\Model\VerzeRepository,
    App\Model\ZmenyRepository,
    Nette\Caching\Cache,
    Nette\Caching\IStorage;


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

  /**
   * @inject
   * @var IStorage
   */
  public $storage;

  public $cache;

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
    $template->registerHelper('ireplace', 'MyHelper::ireplace');
    $template->registerHelper('vlna', function ($string) {
      $string = preg_replace('<([^a-zA-Z0-9])([ksvzaiou])\s([a-zA-Z0-9]{1,})>i', "$1$2\xc2\xa0$3", $string); //&nbsp; === \xc2\xa0
      return $string;
    });
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

    $this->cache = new Cache($this->storage);

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
    $this->response->setCookie('verzeId', $id, '100 days');

    return $verze;
  }

  public function beforeRender()
  {
    $this->template->lide          = $this->lide->aktivniLide();
    $this->template->vsechnyVerze  = $this->verze->vsechny();

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

    $this->template->pocetZmenBezTestera     = null;
    $this->template->pocetNeotestovanychZmen = null;
    $this->template->pocetAutorovychZmen     = null;
    $this->template->pocetTesterovychZmen    = null;
    if($this->verzeId && $this->uzivId && $this->pohled == 'dev') {
      $this->template->pocetAutorovychZmen = $this->zmeny->pocetZmenVeVerzi($this->verzeId, $this->uzivId);
      $this->template->pocetNeotestovanychZmen = $this->zmeny->pocetNeotestovanych($this->verzeId, $this->uzivId);
    }
    if($this->verzeId && $this->uzivId && $this->pohled == 'test') {
      $this->template->pocetTesterovychZmen = $this->zmeny->pocetZmenVeVerzi($this->verzeId, null, $this->uzivId);
      $this->template->pocetNeotestovanychZmen = $this->zmeny->pocetNeotestovanych($this->verzeId, null, $this->uzivId);
    }
    if($this->verzeId && $this->pohled == 'boss') {
      $this->template->pocetZmenBezTestera = $this->zmeny->pocetBezTestera($this->verzeId);
      $this->template->pocetNeotestovanychZmen = $this->zmeny->pocetNeotestovanych($this->verzeId);
    }
  }
}
