<?php

namespace App\Presenters;

use App\Model\VerzeRepository,
    App\Model\ZmenyRepository,
    App\Model\ChybyRepository,
    App\Model\LideRepository,
    Nette\Application\UI\Form,
    Nextras\Forms\Rendering\Bs3FormRenderer,
    ZTPException;


/**
 * Mrazak presenter.
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
    $this->template->seznamVerzi = $this->verze->vsechny();
  }


  /**
   *
   */
  public function renderExport($verzeId)
  {
    if(!$verzeId) $this->redirect('Verze:seznam', array('protokol' => $this->getParameter('protokol'), 'export' => true));
    $this->template->verze = $this->vybratVerzi($verzeId);

    $this->template->testovaci = ($this->getParameter('protokol') == 'testy');
    $this->template->zmeny = $this->zmeny->verejneZmenyVeVerzi($verzeId);
    $this->template->typyZmen = $this->zmeny->seznamTypuZmen();
    $this->template->testeriVeVerzi = $this->zmeny->testeriVeVerzi($verzeId);
  }

  /**
   * Změnový protokol
   */
  public function renderZmeny($verzeId)
  {
    if(!$verzeId) $this->redirect('Verze:seznam');
    $this->template->verze = $this->vybratVerzi($verzeId);

    $autor = $this->filtr == 'autor' ? $this->uzivId : null;
    if(!$autor) $this->template->filtr = '';
    $this->template->zmeny = $this->zmeny->zmenyVeVerzi($verzeId, $autor);
  }

  /**
   * Testovací protokol
   */
  public function renderTesty($verzeId)
  {
    if(!$verzeId) $this->redirect('Verze:seznam', array('protokol' => 'testy'));
    $this->template->verze = $this->vybratVerzi($verzeId);

    $autor  = $this->filtr == 'autor'  ? $this->uzivId : null;
    $tester = $this->filtr == 'tester' ? $this->uzivId : null;

    if($this->filtr == 'autor-chyby') {
      $this->template->zmeny = $this->zmeny->neotestovane($verzeId, $this->uzivId);
    }
    else if($this->filtr == 'tester-chyby') {
      $this->template->zmeny = $this->zmeny->neotestovane($verzeId, null, $this->uzivId);
    }
    else if($this->filtr == 'boss-chyby') {
      $this->template->zmeny = $this->zmeny->neotestovane($verzeId);
    }
    else if($this->filtr == 'bez-testera') {
      $this->template->zmeny = $this->zmeny->bezTestera($verzeId);
    }
    else {
      $this->template->zmeny = $this->zmeny->zmenyVeVerzi($verzeId, $autor, $tester);
      if(!$autor && !$tester) $this->template->filtr = '';
    }

    $this->template->testeriVeVerzi = $this->zmeny->testeriVeVerzi($verzeId);
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
    $osoba = $this->lide->get($id);
    if(!$osoba) throw new \Nette\Application\BadRequestException("Neexistující osoba");
    $this->template->osoba = $osoba;
    $this['osobaForm']->setDefaults($osoba);

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

    try {
      $this->chyby->nastavOk($id, $opraveno);
    } catch (\Exception $e) {
      $this->flashMessage('Problém při úpravě chyby.', 'danger');
    }

    //u změny existuje neopravená chyba - takže i celá změna musí být označena jako nefunkční
    if(!$opraveno) {
      $chyba = $this->chyby->get($id);
      if(!$chyba) throw new \Nette\Application\BadRequestException("Neexistující chyba");
      try {
        $this->zmeny->nastavOk($chyba->zmeny_id, false);
      } catch (\Exception $e) {
        $this->flashMessage('Problém při úpravě změny.', 'danger');
      }
    }

    if ($this->isAjax()) {
      $this->invalidateControl('zmeny');
      $this->invalidateControl('menu');
    }
  }

  /**
   * Továrnička na vytvoření komponenty pro přiřazení testera ke změně
   * @return TesteriControl
   */
  protected function createComponentTesteri()
  {
    $lide = $this->lide->aktivniLide();

    $testeri = new \TesteriControl($lide);
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

    if ($this->isAjax()) {
      $this->invalidateControl('zmeny');
      $this->invalidateControl('menu');
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
      ->setAttribute('placeholder', '11.00 P1')
      ->setRequired('Zadej název verze');

    $form->addText('datum', 'Datum');

    $form->addTextArea('pozn_verejna', 'Veřejné poznámky')
      ->addRule(Form::MAX_LENGTH, 'Poznámka musí mít maximálně %d znaků', 1000);

    $form->addTextArea('pozn_skryta', 'Skryté poznámky')
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
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }

      $this->flashMessage('Uloženo.', 'success');
      $this->redirect('zmeny', $id);
    }
    else { //nový záznam
      try {
        $verze = $this->verze->insert($values);
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }

      $this->redirect('zmeny', $verze->id);
    }
  }


  /**
   * Formulář pro úpravu informace o testerově prostředí
   * @return Form
   */
  protected function createComponentOsobaForm()
  {
    $form = new Form;

    $form->addText('prostredi', 'Prostředí');
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

    $id = $this->getParameter('id');

    try {
      $this->lide->update($id, $values);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při ukládání.', 'danger');
      $this->redirect('this');
    }

    $this->flashMessage('Uloženo.', 'success');
    $this->redirect('Verze:testy', $this->request->getCookie('verzeId'));
  }
}
