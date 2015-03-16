<?php

namespace App\Presenters;

use App\Model\ZmenyRepository,
    App\Model\LideRepository,
    App\Model\ChybyRepository,
    Nette\Application\UI\Form,
    Nextras\Forms\Rendering\Bs3FormRenderer,
    ZTPException;


/**
 * Mrazak presenter.
 */
final class ChybyPresenter extends BasePresenter
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
  public function renderChyba($id, $zmenaId)
  {
    $chyba = null;

    if($id) { //editace chyby
      $chyba = $this->chyby->get($id);
      if(!$chyba) throw new \Nette\Application\BadRequestException("Neexistující chyba");
      $this['chybaForm']->setDefaults($chyba);
      $zmena = $chyba->zmeny;
    }
    else { //nová chyba
      $zmena = $this->zmeny->get($zmenaId);
      if(!$zmena) throw new \Nette\Application\BadRequestException("Neexistující změna");
      $this['chybaForm']['zmeny_id']->setDefaultValue($zmenaId);
    }

    $this->template->chyba = $chyba;
    $this->template->zmena = $zmena;
    $this->template->verze = $this->vybratVerzi($zmena->verze_id);
  }


  /**
   *
   */
  public function actionSmazat($id)
  {
    $chyba = $this->chyby->get($id);
    if(!$chyba) throw new \Nette\Application\BadRequestException("Neexistující chyba");

    try {
      $this->chyby->delete($id);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při mazání.', 'danger');
      $this->redirect('this');
    }

    $this->redirect('Verze:zmeny#z' . $chyba->zmeny_id, $chyba->zmeny->verze_id);
  }

 /**
   * Formulář pro vytvoření nové chyby nebo editaci stávající chyby
   * @return Form
   */
  protected function createComponentChybaForm()
  {
    $form = new Form;

    $form->addHidden('zmeny_id');

    $form->addTextArea('text', 'Popis chyby')
      ->setRequired('Zadej popis chyby')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 1000);


    $form->addSubmit('ok', 'Uložit');

    $form->onSuccess[] = $this->chybaFormSuccess;

    $form->setRenderer(new Bs3FormRenderer);

    return $form;
  }

  /**
   * Zpracování formuláře s textem chyby
   * @param Form $form
   */
  public function chybaFormSuccess($form)
  {
    $values = $form->getValues();

    $id = $this->getParameter('id');
    $zmenaId = $values['zmeny_id'];
    $zmena = $this->zmeny->get($zmenaId);
    if(!$zmena) throw new \Nette\Application\BadRequestException("Neexistující změna");
    $verzeId = $zmena->verze_id;

    if($id) { //editace
      try {
        $this->chyby->update($id, $values);
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }

      $this->redirect('Verze:zmeny#z' . $zmenaId, $verzeId);
    }
    else { //nový záznam
      try {
        $this->chyby->insert($values);
        $this->zmeny->nastavOk($zmenaId, false);
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }
      $this->redirect('Verze:zmeny#z' . $zmenaId, $verzeId);
    }
  }
}
