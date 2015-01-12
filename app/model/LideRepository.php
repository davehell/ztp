<?php

namespace App\Model;


/**
 * Repozitář pro práci s tabulkou 'lide'.
 */
class LideRepository extends Repository
{

  protected $table = 'lide';

  /**
   * Všechny aktivní osoby
   * @return \Nette\Database\Table\Selection
   */
  public function aktivniLide()
  {
    return $this->findAll()->where('je_aktivni', 1)->order('jmeno');
  }

  /**
   * Senam všech aktivních lidí ve firmě.
   * @return array
   */
  public function seznamLidi()
  {
    return $this->aktivniLide()->fetchPairs('id', 'jmeno');
  }

  /**
   * Senam všech aktivních testerů ve firmě.
   * @return array
   */
  public function seznamTesteru()
  {
    return $this->aktivniLide()->where('je_tester', 1)->fetchPairs('id', 'jmeno');
  }
}
