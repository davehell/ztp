<?php

namespace App\Model;


/**
 * Repozitář pro práci s tabulkou 'chyby'.
 */
class ChybyRepository extends Repository
{

  protected $table = 'chyby';

  /**
   * Všechny chyby u dané změny
   * @param  $zmena  ID změny
   * @return \Nette\Database\Table\Selection
   */
  public function chybyVeZmene($zmena)
  {
    return $this->findAll()->where('zmeny_id', $zmena);
  }

  /**
   * Nastaví chybě příznak, jestli je / není opravena
   * @param  $id        ID chyba
   * @param  $opraveno  true | false
   */
  public function nastavOk($id, $opraveno)
  {
    return $this->update($id, array('je_ok' => $opraveno));
  }

  /**
   * Všem chybám u změny nastaví příznak, že jsou opravené
   * @param  $zmena     ID zmeny
   */
  public function nastavVseOpraveno($zmena)
  {
    return $this->chybyVeZmene($zmena)->update(array('je_ok' => true));
  }
}
