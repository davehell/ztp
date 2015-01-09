<?php

namespace App\Model;


/**
 * Repozitář pro práci s tabulkou 'zmeny'.
 */
class ZmenyRepository extends Repository
{

  protected $table = 'zmeny';

  /**
   * Senam typů změn (přidáno, změněno, oprava)
   * @return array
   */
  public function seznamTypuZmen()
  {
    return $this->database->table('typy_zmen')->fetchPairs('id', 'nazev');
  }

  /**
   * Senam tagů u změn (energis, rwe, ...)
   * @return array
   */
  public function seznamTagu()
  {
    return $this->database->table('typy_tagu')->fetchPairs('nazev', 'nazev');
  }

  /**
   * Všechny změny v dané verzi
   * @return \Nette\Database\Table\Selection
   */
  public function zmenyVeVerzi($verze)
  {
    return $this->findAll()->where('verze_id', $verze);
  }

  /**
   * Přiřadí testera k dané změně
   */
  public function priraditTestera($zmenaId, $testerId)
  {
    $zmena = $this->get($zmenaId);
    if(!$zmena) throw new \ZtpException("Neexistující změna");
    if($zmena->tester == $testerId) $testerId = null; //odebrání testera od změny
    return $this->update($zmenaId, array('tester_id' => $testerId));
  }

  /**
   * Nastaví změně příznak, jestli funguje správně / nefunguje správně
   */
  public function nastavOk($id, $funguje)
  {
    $zmena = $this->get($id);
    if(!$zmena) throw new \ZtpException("Neexistující změna");
    $values = array('je_ok' => $funguje);
    if(!$funguje) $values = array_merge($values, array('vysledek_testu' => ''));
    return $this->update($id, $values);
  }
}
