<?php

namespace App\Presenters;

use App\Model\ZmenyRepository,
    App\Model\ChybyRepository,
    Nette\Application\UI\Form,
    Nextras\Forms\Rendering\Bs3FormRenderer,
    Nette\Caching\Cache,
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
      $this['zmenaForm']->setDefaults(array('tagy' => $this->zmeny->tagyProZmenu($id)));
      $this['zmenaTesterForm']->setDefaults($zmena); //osekaný změnový formulář pro testera
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

    $this->cache->clean([
      Cache::TAGS => array("zmena/$id"),
    ]);

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

    $form->addText('task', 'Čísla tasků')
      ->addRule(Form::MAX_LENGTH, 'Task musí mít maximálně %d znaků', 50)
      ->addRule(Form::PATTERN, 'Čísla tasku mohou obsahovat pouze číslice', '[0-9, ]*'); //jen číslice, čárky a mezery

    $form->addText('uloha', 'Úloha')
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

    $form->addCheckboxList('tagy', 'Jen pro podnik', $this->zmeny->seznamTagu());

    $form->addSubmit('ok', 'Uložit');

    $form->onSuccess[] = $this->zmenaFormSuccess;

    $form->setRenderer(new Bs3FormRenderer);

    return $form;
  }


  /**
   * Formulář pro editaci stávající změny testerem.
   * Tester může u změny upravit jen skryté info.
   * @return Form
   */
  protected function createComponentZmenaTesterForm()
  {
    $form = new Form;

    $form->addHidden('verze_id');

    $form->addTextArea('text', 'Popis změny')
      ->setAttribute('placeholder', 'Veřejný popis zobrazený ve změnovém protokolu')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 1000)
      ->setDisabled(true);

    $form->addTextArea('detail', 'Skryté info')
      ->setAttribute('placeholder', 'Neveřejné informace určené pouze pro testery')
      ->addRule(Form::MAX_LENGTH, 'Text musí mít maximálně %d znaků', 1000);

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

    //pěkné naformátování seznamu s čísly tasků
    if(isset($values['task'])) {
      $values['task'] = str_replace(' ', '', $values['task']); //odstranění mezer
      $values['task'] = str_replace(',', ', ', $values['task']); //doplnění mezer za čárky
    }

    $zmenaId = $this->getParameter('id');
    $verzeId = $values['verze_id'];
    $predchudce = $this->getParameter('predchudce');

    $tagy = array();
    if(isset($values['task'])) {
      $tagy = $values['tagy'];
      unset($values['tagy']);
    }

    if($zmenaId) { //editace
      try {
        $this->zmeny->update($zmenaId, $values);
      } catch (\Exception $e) {
        $this->flashMessage('Chyba při ukládání.', 'danger');
        $this->redirect('this');
      }

      $this->cache->clean([
        Cache::TAGS => array("zmena/$zmenaId"),
      ]);
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

      $zmenaId = $z->id;

      if($predchudce) {
        //umístěni nové změny za jejího předchůdce
        array_splice($poradi, array_search($predchudce, $poradi) + 1, 0, $zmenaId);
        try {
          $this->zmeny->aktualizovatPoradiZmen($poradi);
        } catch (\Exception $e) {
          $this->flashMessage('Chyba při nastavení pořadí změn v protokolu.', 'danger');
          $this->redirect('this');
        }
      }
    }

    //uložení tagů ke změně
    //převedení multiselectu se štítky do podoby vzhodné pro uložení do db
		$arr = array();
		foreach($tagy as $key => $idTagu) {
			$arr[$key]['zmeny_id'] = $zmenaId;
      $arr[$key]['tagy_id'] = $idTagu;
		}
    try {
      $this->zmeny->aktualizaceTagu($zmenaId, $arr);
    } catch (\Exception $e) {
      $this->flashMessage('Chyba při ukládání štítků.', 'danger');
      $this->redirect('this');
    }

    $this->redirect('Verze:zmeny#z' . $zmenaId, $verzeId);
  }

}
