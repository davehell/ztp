<?php

namespace App\Presenters;

use App\Model\VerzeRepository,
    App\Model\ZmenyRepository,
    App\Model\ChybyRepository,
    App\Model\LideRepository,
    Nette\Application\UI\Form,
    PdfResponse\PdfResponse,
    Nextras\Forms\Rendering\Bs3FormRenderer,
    Nette\Caching\Cache,
    ZTPException;

/**
 * Verze presenter.
 */
final class VerzePresenter extends BasePresenter
{

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
   * @var ChybyRepository
   * @inject
   */
  public $chyby;

  /**
   * @var LideRepository
   * @inject
   */
  public $lide;


  /**
   *
   */
  public function beforeRender()
  {
    parent::beforeRender();
  }


  /**
   *
   */
  public function renderDefault($verzeId = null, $uziv = null)
  {
    if($verzeId == null) {
      $verzeId = $this->request->getCookie('verzeId');
    }
    $this->template->verze = ($verzeId == null) ? "" : $this->vybratVerzi($verzeId);
  }


  /**
   *
   */
  public function renderSeznam()
  {
    $this->template->protokol = $this->getParameter('protokol');
    $this->template->export = $this->getParameter('export');
  }

  /**
   * Pokud je v url zadán parametr format==pdf, vykreslí se pdf. Na renderExport se v tom případě nepokračuje.
   * Pokud je zadáno více ID verzí, zobrazí se změny ze všech verzí. Do hlavičky se použijí údaje z první zadané verze.,
   * @param  [string] $id       listing ID verzí oddělený čárkami
   * @param  [string] $protokol "testy" | "zmeny"
   * @param  [string] $format   "pdf"
   * @param  [string] $podnik   číslo podniku (pro filtrování na základě tagu)
   */
  public function actionExport($verzeId, $protokol, $format, $podnik = null)
  {
    //musí být zadána aspoň jedna verze
    if(!$verzeId) $this->redirect('Verze:seznam', array('protokol' => $this->getParameter('protokol'), 'export' => true));

    $seznamVerzi = explode(',', $verzeId);
    $vsechnyVerze = array(); //může se exportovat i více protokolů najednou

    //všechny zadané verze musí existovat
    foreach ($seznamVerzi as $verze) {
      $verze = $this->verze->get($verze);
      if(!$verze) {
        throw new \Nette\Application\BadRequestException('Neexistující verze');
      }
      $vsechnyVerze[] = $verze;
    }

    if($format == 'pdf') {
      $template = $this->createTemplate()->setFile(__DIR__ . '/../templates/Verze/export.latte');

      $template->verze = $vsechnyVerze;
      $template->zmeny = $this->zmeny->verejneZmenyVeVerzi($seznamVerzi, $podnik);
      $template->testeriVeVerzi = $this->zmeny->testeriVeVerzi($seznamVerzi);
      $template->testovaci = ($protokol == 'testy');
      $template->typyZmen = $this->zmeny->seznamTypuZmen();
      $jeMyEne = !(strpos($vsechnyVerze[0]->nazev, 'my') === false);

      $pdf = new PDFResponse($template);
      $pdf->documentAuthor = '';
      $pdf->documentTitle = '';

      if($jeMyEne) {
        $pdf->documentTitle .= ($podnik ? $podnik : 'myEnergis');
      }
      else {
        if($podnik) $pdf->documentTitle .= 'podnik ' . $podnik . ' ';
        $pdf->documentTitle .= 'webenergis';
      }

      $pdf->documentTitle .= ' ' . ($template->testovaci ? 'testy' : 'zmeny') . ' ' . $vsechnyVerze[0]->nazev;
      $pdf->outputDestination = PDFResponse::OUTPUT_DOWNLOAD;

      $this->sendResponse($pdf);
    }
  }

  /**
   * Export protokolů.
   */
  public function renderExport($verzeId, $protokol, $podnik)
  {
    $seznamVerzi = explode(',', $verzeId);
    $this->template->verze = array();
    foreach($seznamVerzi as $verzeId) {
      $this->template->verze[] = $this->verze->get($verzeId);
    }
    $this->template->zmeny = $this->zmeny->verejneZmenyVeVerzi($seznamVerzi, $podnik);
    $this->template->testeriVeVerzi = $this->zmeny->testeriVeVerzi($seznamVerzi);
    $this->template->testovaci = ($protokol == 'testy');
    $this->template->typyZmen = $this->zmeny->seznamTypuZmen();
    $this->template->title = $this->template->verze[0]->nazev;
  }


  /**
   * Export přehledu změn ve verzi. Pouze pracovní dokument - ne oficiální protokol (protože obsahuje i skryté položky).
   */
  public function actionZmeny($verzeId, $format)
  {
    if($format == 'pdf') {
      $this->setLayout("export");
      $template = $this->createTemplate()->setFile(__DIR__ . '/../templates/Verze/zmeny.latte');

      $template->verze = $this->vybratVerzi($verzeId);
      $template->zmeny = $this->zmeny->zmenyVeVerzi($verzeId);

      $pdf = new PDFResponse($template);
      $pdf->documentAuthor = '';
      $pdf->documentTitle = 'Změny verze ' . $template->verze->nazev;
      $pdf->outputDestination = PDFResponse::OUTPUT_DOWNLOAD;

      $this->sendResponse($pdf);
    }
  }


  /**
   * Přehled změn ve verzi
   */
  public function renderZmeny($verzeId)
  {
    if(!$verzeId) $this->redirect('Verze:seznam');
    $this->template->verze = $this->vybratVerzi($verzeId);
    $this->template->pocetVsechZmen = $this->zmeny->pocetZmenVeVerzi($verzeId);

    if($this->pohled == 'test' || $this->pohled == 'boss') {
      $this->template->testeriVeVerzi = $this->zmeny->testeriVeVerzi($verzeId);
    }

    $autor  = $this->filtr == 'autor'  ? $this->uzivId : null;
    $tester = $this->filtr == 'tester' ? $this->uzivId : null;

    if($this->filtr == 'autor') {
      $this->template->zmeny = $this->zmeny->zmenyVeVerzi($verzeId, $this->uzivId, null);
    }
    else if($this->filtr == 'autor-chyby') {
      $this->template->zmeny = $this->zmeny->neotestovane($verzeId, $this->uzivId, null);
    }
    else if($this->filtr == 'tester') {
      $this->template->zmeny = $this->zmeny->zmenyVeVerzi($verzeId, null, $this->uzivId);
    }
    else if($this->filtr == 'tester-chyby') {
      $this->template->zmeny = $this->zmeny->neotestovane($verzeId, null, $this->uzivId);
    }
    else if($this->filtr == 'bez-testera') {
      $this->template->zmeny = $this->zmeny->bezTestera($verzeId);
    }
    else if($this->filtr == 'boss-chyby') {
      $this->template->zmeny = $this->zmeny->neotestovane($verzeId);
    }
    else {
      $this->template->zmeny = $this->zmeny->zmenyVeVerzi($verzeId);
    }
  }


  /**
   * Vytvoření nové verze / Editace informací o verzi
   */
  public function renderInfo($verzeId)
  {
    $this->template->verze = null;
    if($verzeId) { //editace
      $verze = $this->vybratVerzi($verzeId);
      $this->template->verze = $verze;
      $this['infoForm']->setDefaults($verze);
      if($verze->datum) {
        $this['infoForm']['datum']->setDefaultValue($verze->datum->format('d.m.Y'));
      }
    }
  }

  /**
   * Editace testovacího prostředí u testera
   */
  public function renderOsoba($id, $verzeId = null)
  {
    $clovek = $this->lide->get($id);
    if(!$clovek) throw new \Nette\Application\BadRequestException("Neexistující osoba");
    $this->template->clovek = $clovek;
    $this['osobaForm']->setDefaults($clovek);

    //zapamatuju si verzi, abych se po odeslání formuláře mohl vrátit do správného protokolu
    if($verzeId) {
      $this->response->setCookie('verzeId', $verzeId, '');
    }
  }

  /**
   * Uložení pořadí změn v protokolu
   */
  public function handlePoradi($seznam)
  {
    $poradi = explode(",", $seznam);
    try {
      $this->zmeny->aktualizovatPoradiZmen($poradi);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při nastavení pořadí změn v protokolu.', 'danger');
    }

    if ($this->isAjax()) {
      $this->invalidateControl('zmeny');
    }
  }

  /**
   * Zamčení/Odemčení verze pro úpravy
   */
  public function handleZamceni($verzeId, $zamek)
  {
    $this->verze->zamknout($verzeId, $zamek);
    try {
      $this->verze->zamknout($verzeId, $zamek);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při zamykání verze.', 'danger');
    }
    $this->redirect('this');
  }

  /**
   * Smazání verze
   */
  public function actionSmazat($verzeId)
  {
    try {
      $this->verze->delete($verzeId);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při mazání.', 'danger');
      $this->redirect('Verze:zmeny');
    }

    $this->cache->clean([
      Cache::TAGS => array("verze/$verzeId"),
    ]);

    $this->flashMessage('Smazáno.' , 'success');
    $this->redirect('Verze:seznam', array('verzeId' => null));
  }


  /**
   * Továrnička na vytvoření komponenty pro výpis stavu chyby / změny
   * @return StavControl
   */
  protected function createComponentStav()
  {
    $stav = new \StavControl();
    $stav->redrawControl();
    return $stav;
  }

  /**
   * Továrnička na vytvoření komponenty pro výpis chyb u změny
   * @return ChybyControl
   */
  protected function createComponentChyby()
  {
    $chyby = new \ChybyControl($this->chyby);
    $chyby->onOpraveno[] = $this->chybaOpravena;
    $chyby->pohled = $this->pohled;
    $chyby->redrawControl();
    return $chyby;
  }

  /**
   * Chybě se nastaví příznak, jestli je opravena nebo ne
   * @param  [type] $chyba   [description]
   * @param  [type] $funguje [description]
   */
  public function chybaOpravena($id, $opraveno)
  {
    //pokud vývojář opraví chybu, nastaví se jí příznak, že čeká na otestování
    if($this->pohled == 'dev' && $opraveno) $opraveno = null;

    $chyba = $this->chyby->get($id);
    if(!$chyba) throw new \Nette\Application\BadRequestException("Neexistující chyba");

    $this->cache->clean([
      Cache::TAGS => array("zmena/$chyba->zmeny_id"),
    ]);

    try {
      $this->chyby->nastavOk($id, $opraveno);
    } catch (\Exception $e) {
      $this->flashMessage('Problém při úpravě chyby.', 'danger');
    }

    //u změny existuje neopravená chyba - takže i celá změna musí být označena jako nefunkční
    if(!$opraveno) {
      try {
        $this->zmeny->nastavOk($chyba->zmeny_id, false);
      } catch (\Exception $e) {
        $this->flashMessage('Problém při úpravě změny.', 'danger');
      }
    }

    if ($this->isAjax()) {
      $this->invalidateControl('zmeny');
      $this->invalidateControl('filtr');
    }
  }

  /**
   * Továrnička na vytvoření komponenty pro přiřazení testera ke změně
   * @return TesteriControl
   */
  protected function createComponentTesteri()
  {
    $testeri = new \TesteriControl($this->lide->aktivniLide());
    $testeri->onPrirazeni[] = $this->prirazeniTestera;
    $testeri->redrawControl();
    return $testeri;
  }

  public function prirazeniTestera($testerId, $zmenaId)
  {
    try {
      $this->zmeny->priraditTestera($zmenaId, $testerId);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při přiřazení testera.', 'danger');
    }

    $this->cache->clean([
      Cache::TAGS => array("zmena/$zmenaId"),
    ]);

    if ($this->isAjax()) {
      $this->invalidateControl('zmeny');
      $this->invalidateControl('filtr');
    }
  }

  /**
   * Formulář pro vytvoření nové verze nebo editaci stávající verze
   * @return Form
   */
  protected function createComponentInfoForm()
  {
    $form = new Form;

    $form->addText('nazev', 'Název')
      ->setAttribute('placeholder', 'např. 11.00 P1')
      ->setRequired('Zadej název verze');

    $form->addText('datum', 'Datum');

    $form->addTextArea('pozn_verejna', 'Veřejné poznámky')
      ->setAttribute('placeholder', 'Poznámky k verzi zobrazené na začátku změnového protokolu')
      ->addRule(Form::MAX_LENGTH, 'Poznámka musí mít maximálně %d znaků', 1000);

    $form->addTextArea('pozn_skryta', 'Skryté poznámky')
      ->setAttribute('placeholder', 'Neveřejné poznámky k verzi určené pouze pro testery')
      ->addRule(Form::MAX_LENGTH, 'Poznámka musí mít maximálně %d znaků', 1000);

    $form->addSubmit('ok', 'Uložit');

    $form->onSuccess[] = $this->infoFormSuccess;

    $form->setRenderer(new Bs3FormRenderer);

    return $form;
  }


  /**
   * Zpracování formuláře s informacemi o verzi
   * @param Form $form
   */
  public function infoFormSuccess($form)
  {
    $values = $form->getValues();
    $values['datum'] = $this->verze->dateDbFormat($values['datum']);

    $id = $this->getParameter('verzeId');

    if($id) { //editace
      try {
        $this->verze->update($id, $values);
        $this->flashMessage('Uloženo.', 'success');
        $this->redirect('zmeny', $id);
      } catch(\PDOException $e) {
        if($e->getCode() == \App\Model\Repository::PDO_DUPLICATE_ENTRY) {
          $form['nazev']->addError('Verze s tímto názvem už existuje!');
          $this->flashMessage('Neuloženo. Oprav chyby ve formuláři.', 'danger');
        }
        else throw $e;
      } catch (\Nette\InvalidStateException $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
      }
    }
    else { //nový záznam
      try {
        $verze = $this->verze->insert($values);
        $this->lide->odebratProstredi();
        $this->redirect('zmeny', $verze->id);
      } catch(\PDOException $e) {
        if($e->getCode() == \App\Model\Repository::PDO_DUPLICATE_ENTRY) {
          $form['nazev']->addError('Verze s tímto názvem už existuje!');
          $this->flashMessage('Neuloženo. Oprav chyby ve formuláři.', 'danger');
        }
        else throw $e;
      } catch (\Nette\InvalidStateException $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
      }
    }
  }


  /**
   * Formulář pro úpravu informace o testerově prostředí
   * @return Form
   */
  protected function createComponentOsobaForm()
  {
    $form = new Form;

    $form->addText('prostredi', 'Prostředí')
      ->setAttribute('placeholder', 'Verze prohlížečů, na kterých se testuje');

    $form->addSubmit('ok', 'Uložit');

    $form->onSuccess[] = $this->osobaFormSuccess;

    $form->setRenderer(new Bs3FormRenderer);

    return $form;
  }


  /**
   * Zpracování formuláře s informacemi o testerově prostředí
   * @param Form $form
   */
  public function osobaFormSuccess($form)
  {
    $values = $form->getValues();
    $values['je_zadano_prostredi'] = true;

    $id = $this->getParameter('id');

    try {
      $this->lide->update($id, $values);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při ukládání.', 'danger');
      $this->redirect('this');
    }

    $this->flashMessage('Uloženo.', 'success');
    $this->redirect('Verze:zmeny', $this->request->getCookie('verzeId'));
  }


  /**
   * Formulář pro uložení výsledku testování změny
   * @return Form
   */
  protected function createComponentTestForm()
  {
    $form = new Form;
    $form->getElementPrototype()->class('ajax');

    $form->addHidden('je_ok')
      ->setDefaultValue(1);

    $form->addHidden('id')
      ->setAttribute('id', 'frm-testForm-id');

    $form->addText('vysledek_testu', 'Jiný výsledek')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 100);

    $form->addSubmit('ok', 'Uložit');

    $form->onSuccess[] = $this->testFormSuccess;

    $form->setRenderer(new Bs3FormRenderer);

    return $form;
  }

  /**
   * Zpracování formuláře s výsledkem testování
   * @param Form $form
   */
  public function testFormSuccess($form)
  {
    $values = $form->getValues();

    $zmenaId = $values['id'];

    if ($this->isAjax()) {
      $this->payload->akce = 'testFormSuccess';
      $this->payload->zmena = $zmenaId;
    }


    try {
      $this->chyby->nastavVseOpraveno($zmenaId);
      $this->zmeny->update($zmenaId, $values);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při ukládání.', 'danger');
      $this->redirect('this');
    }

    $this->cache->clean([
      Cache::TAGS => array("zmena/$zmenaId"),
    ]);

    if ($this->isAjax()) {
      $this->invalidateControl('zmeny');
      $this->invalidateControl('filtr');
    }
    else {
      $this->redirect('this');
    }
  }
}
