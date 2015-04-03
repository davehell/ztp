<?php

namespace App\Model;


/**
 * Repozitář pro práci s tabulkou 'verze'.
 */
class VerzeRepository extends Repository
{

  protected $table = 'verze';

  /**
   * Všechny verze - seřazené dle ID
   * @return \Nette\Database\Table\Selection
   */
  public function vsechny()
  {
    return $this->findAll()->order('datum')->order('id');
  }

  /**
   * Zamčení/Odemčení verze pro úpravy
   * @param  $id     ID verze
   * @param  $zamek  true | false
   */
  public function zamknout($id, $zamek)
  {
    return $this->update($id, array('je_zamcena' => $zamek));
  }

}
