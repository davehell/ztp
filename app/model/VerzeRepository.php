<?php

namespace App\Model;


/**
 * Repozitář pro práci s tabulkou 'verze'.
 */
class VerzeRepository extends Repository
{

  protected $table = 'verze';

  /**
   * Senam všech verzí s vyplněným datem vydání
   * @return \Nette\Database\Table\Selection
   */
  public function vydane()
  {
    return $this->findAll()->where('datum IS NOT NULL')->order('datum DESC');
  }

  /**
   * Senam všech verzí bez data vydání
   * @return \Nette\Database\Table\Selection
   */
  public function nevydane()
  {
    return $this->findAll()->where('datum IS NULL')->order('id DESC');
  }

  /**
   * Všechny verze - seřazené dle ID
   * @return \Nette\Database\Table\Selection
   */
  public function vsechny()
  {
    return $this->findAll()->order('id DESC');
  }

}
