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

}
