<?php

namespace App\Presenters;

use App\Model\ZmenyRepository,
    App\Model\ChybyRepository,
    Nette\Application\UI\Form,
    Nextras\Forms\Rendering\Bs3FormRenderer,
    ZTPException;


/**
 * Zmeny presenter.
 */
final class ZmenyPresenter extends BasePresenter
{

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

  public function beforeRender()
  {
    parent::beforeRender();
  }


  /**
   *
   */
  public function renderZmena($id)
  {
    $verze = $this->verze->get($this->verzeId);
    if(!$verze) throw new \Nette\Application\BadRequestException("Neexistující verze");
    $this->template->verze = $verze;
    $this->template->zmena = null;
    $this['zmenaForm']['verze_id']->setDefaultValue($this->verzeId);
    if($this->uzivId) $this['zmenaForm']['autor_id']->setDefaultValue($this->uzivId);

    if($id) { //editace změny
      $zmena = $this->zmeny->get($id);
      if(!$zmena) throw new \Nette\Application\BadRequestException("Neexistující změna");
      $this['zmenaForm']->setDefaults($zmena);
      $this->template->zmena = $zmena;
    }
  }

 /**
   * Zapsání výsledku testování ke změně
   */
  public function renderTest($id)
  {
    $zmena = $this->zmeny->get($id);
    if(!$zmena) throw new \Nette\Application\BadRequestException("Neexistující změna");
    $this->template->zmena = $zmena;
    $this->template->verze = $zmena->verze;
    if($zmena->vysledek_testu) $this['testForm']['vysledek_testu']->setDefaultValue($zmena->vysledek_testu);
    $this->template->neopraveneChyby = $this->chyby->neopraveneChyby($id);
  }

  /**
   *
   */
  public function actionSmazat($id)
  {
    $zmena = $this->zmeny->get($id);
    if(!$zmena) throw new \Nette\Application\BadRequestException("Neexistující změna");

    try {
      $this->zmeny->delete($id);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při mazání.', 'danger');
      $this->redirect('Verze:zmeny', $this->verzeId);
    }

    $this->flashMessage('Smazáno.' , 'success');
    $this->redirect('Verze:zmeny', $this->verzeId);
  }

  /**
   * Formulář pro uložení výsledku testování změny
   * @return Form
   */
  protected function createComponentTestForm()
  {
    $form = new Form;

    $form->addHidden('je_ok')
      ->setDefaultValue(1);

    $form->addText('vysledek_testu', 'Výsledek testu')
      ->setDefaultValue('bez připomínek')
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

    $zmenaId = $this->getParameter('id');

    try {
      $this->zmeny->update($zmenaId, $values);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při ukládání.', 'danger');
      $this->redirect('this');
    }

    $this->redirect('Verze:testy#z' . $zmenaId, $this->verzeId);
  }

  /**
   * Formulář pro vytvoření nové změny nebo editaci stávající změny
   * @return Form
   */
  protected function createComponentZmenaForm()
  {
    $form = new Form;

    $form->addHidden('verze_id');

    $form->addSelect('autor_id', 'Autor', $this->lide->seznamLidi())
      ->setPrompt('Vyber autora změny')
      ->setRequired('Vyber autora změny');

    $form->addRadioList('typy_zmen_id', 'Typ', $this->zmeny->seznamTypuZmen())
      ->setDefaultValue(1);

    $form->addCheckbox('je_verejna', 'Veřejná změna')
      ->setDefaultValue(true);

    $form->addTextArea('text', 'Popis změny')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 1000)
      ->setRequired('Zadej popis změny');

    $form->addTextArea('detail', 'Skryté info')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 1000);

    $form->addTextArea('uloha', 'Úloha')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 1000);

    $form->addText('task', 'Číslo tasku')
      ->addRule(Form::MAX_LENGTH, 'Task musí mít maximálně %d znaků', 50);

    $form->addMultiSelect('tagy', 'Štítky', $this->zmeny->seznamTagu())
      ->setDefaultValue("energis");

    $form->addSubmit('ok', 'Uložit');

    $form->onSuccess[] = $this->zmenaFormSuccess;

    $form->setRenderer(new Bs3FormRenderer);

    return $form;
  }


  /**
   * Zpracování formuláře s informacemi o změně
   * @param Form $form
   */
  public function zmenaFormSuccess($form)
  {
    $values = $form->getValues();
    unset($values['tagy']);

    $zmenaId = $this->getParameter('id');

    if($zmenaId) { //editace
      try {
        $this->zmeny->update($zmenaId, $values);
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }

      $this->redirect('Verze:zmeny#z' . $zmenaId, $this->verzeId);
    }
    else { //nový záznam
      try {
        $z = $this->zmeny->insert($values);
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }

      $this->redirect('Verze:zmeny#z' . $z->id, $this->verzeId);
    }
  }

}
