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
  public function renderHledat($text = null)
  {
    $this->template->vysledky = $text ? $this->zmeny->vyhledavani($text) : array();
    $this->template->text = $text;
  }

  /**
   *
   */
  public function renderZmena($id = null, $verzeId = null, $predchudce = null)
  {

    if($verzeId) { //nová změna - zadáno jen ID verze, do které má změna patřit
      $this['zmenaForm']['verze_id']->setDefaultValue($verzeId);
      $this->template->zmena = null;
      $this->template->verze = $this->vybratVerzi($verzeId);
      if($this->uzivId) $this['zmenaForm']['autor_id']->setDefaultValue($this->uzivId);
    }
    else if($id) { //editace změny - zadáno přímo ID změny
      $zmena = $this->zmeny->get($id);
      if(!$zmena) throw new \Nette\Application\BadRequestException("Neexistující změna");
      $this['zmenaForm']->setDefaults($zmena);
      $this->template->zmena = $zmena;
      $this->template->verze = $this->vybratVerzi($zmena->verze_id);
    }
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
      $this->redirect('Verze:zmeny', $zmena->verze_id);
    }

    $this->flashMessage('Smazáno.' , 'success');
    $this->redirect('Verze:zmeny', $zmena->verze_id);
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

    $form->addCheckbox('je_verejna', 'Veřejná změna')
      ->setDefaultValue(true);

    $form->addText('task', 'Čísla tasku')
      ->addRule(Form::MAX_LENGTH, 'Task musí mít maximálně %d znaků', 50)
      ->addRule(Form::PATTERN, 'Čísla tasku mohou obsahovat pouze číslice', '[0-9, ]*'); //jen číslice, čárky a mezery

    $form->addTextArea('uloha', 'Úloha')
      ->setAttribute('placeholder', 'Název úlohy, které se změna týká')
      ->addRule(Form::MAX_LENGTH, 'Úloha musí mít maximálně %d znaků', 1000)
      ->setRequired('Zadej, které úlohy se změna týká');

    $form->addRadioList('typy_zmen_id', 'Typ', $this->zmeny->seznamTypuZmen())
      ->setRequired('Vyber typ změny');

    $form->addTextArea('text', 'Popis změny')
      ->setAttribute('placeholder', 'Veřejný popis zobrazený ve změnovém protokolu')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 1000)
      ->setRequired('Zadej popis změny');

    $form->addTextArea('detail', 'Skryté info')
      ->setAttribute('placeholder', 'Neveřejné informace určené pouze pro testery')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 1000);

    // $form->addMultiSelect('tagy', 'Štítky', $this->zmeny->seznamTagu())
    //   ->setDefaultValue("energis");

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
    //unset($values['tagy']);

    $values['task'] = str_replace(' ', '', $values['task']); //odstranění mezer
    $values['task'] = str_replace(',', ', ', $values['task']); //doplnění mezer za čárky

    $zmenaId = $this->getParameter('id');
    $verzeId = $values['verze_id'];
    $predchudce = $this->getParameter('predchudce');

    if($zmenaId) { //editace
      try {
        $this->zmeny->update($zmenaId, $values);
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }

      $this->redirect('Verze:zmeny#z' . $zmenaId, $verzeId);
    }
    else { //nový záznam
      //pokud se nová změna má umístit za nějakou existující, potřebujeme znát pořadí změn ve verzi
      if($predchudce) {
        $poradi = $this->zmeny->poradiZmenVeVerzi($verzeId);
      }

      try {
        $z = $this->zmeny->insert($values);
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }

      if($predchudce) {
        //umístěni nové změny za jejího předchůdce
        array_splice($poradi, array_search($predchudce, $poradi) + 1, 0, $z->id);
        try {
          $this->zmeny->aktualizovatPoradiZmen($poradi);
        } catch (\Exception $e) {
          $this->flashMessage('Chyba při nastavení pořadí změn v protokolu.', 'danger');
          $this->redirect('this');
        }
      }

      $this->redirect('Verze:zmeny#z' . $z->id, $verzeId);
    }
  }

}
