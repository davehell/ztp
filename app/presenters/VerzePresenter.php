<?php

namespace App\Presenters;

use App\Model\VerzeRepository,
    App\Model\ZmenyRepository,
    App\Model\ChybyRepository,
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
   *
   */
  public function beforeRender()
  {
    parent::beforeRender();
  }


  /**
   *
   */
  public function renderSeznam()
  {
    $this->template->seznamVerzi = $this->verze->vsechny();
  }

  /**
   * Změnový protokol
   */
  public function renderZmeny($verzeId)
  {
    $verze = $this->verze->get($verzeId);
    if(!$verze) throw new \Nette\Application\BadRequestException("Neexistující verze");
    $this->template->verze = $verze;
    $this->template->zmeny = $this->zmeny->zmenyVeVerzi($verzeId);
  }

  /**
   * Testovací protokol
   */
  public function renderTesty($verzeId)
  {
    $verze = $this->verze->get($verzeId);
    if(!$verze) throw new \Nette\Application\BadRequestException("Neexistující verze");
    $this->template->verze = $verze;
    $this->template->zmeny = $this->zmeny->zmenyVeVerzi($verzeId);
  }

  /**
   * Editace informací o verzi
   */
  public function renderInfo($verzeId)
  {
    $this->template->verze = null;
    if($verzeId) { //editace
      $verze = $this->verze->get($verzeId);
      if(!$verze) throw new \Nette\Application\BadRequestException("Neexistující verze");
      $this->template->verze = $verze;
      $this['infoForm']->setDefaults($verze);
      if($verze->datum) {
        $this['infoForm']['datum']->setDefaultValue($verze->datum->format('d.m.Y'));
      }
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
   *
   */
  public function handleZmenaFunguje($id)
  {
    try {
      $this->zmeny->nastavOk($id, true);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při úpravě změny.', 'danger');
    }

    if ($this->isAjax()) {
      $this->invalidateControl('zmeny');
    }
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
    }
  }

  /**
   * Továrnička na vytvoření komponenty pro přiřazení testera ke změně
   * @return TesteriControl
   */
  protected function createComponentTesteri()
  {
    $testeri = new \TesteriControl();
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

}
