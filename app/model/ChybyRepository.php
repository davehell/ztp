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
   * @return \Nette\Database\Table\Selection
   */
  public function chybyVeZmene($zmena)
  {
    return $this->findAll()->where('zmeny_id', $zmena);
  }

  /**
   * Nastaví chybě příznak, jestli je / není opravena
   */
  public function nastavOk($id, $opraveno)
  {
    $chyba = $this->get($id);
    if(!$chyba) throw new \ZtpException("Neexistující chyba");
    return $this->update($id, array('je_ok' => $opraveno));
  }

  /**
   * Vrací počet neopravených chyb u změny
   * @return int
   */
  public function neopraveneChyby($zmena)
  {
    return $this->chybyVeZmene($zmena)->where('je_ok = ? OR je_ok IS NULL', 0)->count();
  }
}
