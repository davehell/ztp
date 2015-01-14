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
  public function zmenyVeVerzi($verze, $autor = null, $tester = null)
  {
    $zmeny = $this->findAll()->where('verze_id', $verze);
    if($autor) $zmeny = $zmeny->where('autor_id', $autor);
    if($tester) $zmeny = $zmeny->where('tester_id', $tester);
    return $zmeny;
  }

  /**
   * Změny ve verzi, které ještě nejsou otestované (buď je v nich chyba, nebo ještě testování nezačalo)
   * @return \Nette\Database\Table\Selection
   */
  public function neotestovane($verze, $autor = null, $tester = null)
  {
    return $this->zmenyVeVerzi($verze, $autor, $tester)->where('je_ok = 0 OR je_ok IS NULL');
  }

  /**
   * Počet změn ve verzi, které nemají přiřazeného testera
   * @return \Nette\Database\Table\Selection
   */
  public function bezTestera($verze)
  {
    return $this->zmenyVeVerzi($verze)->where('tester_id IS NULL');
  }

  /**
   * Jména testerů, kteří testovali změny v dané verzi
   * @return \Nette\Database\Table\Selection
   */
  public function testeriVeVerzi($verze)
  {
    return $this->zmenyVeVerzi($verze)->select('DISTINCT tester_id')->where('tester_id IS NOT NULL')->order('tester.jmeno');
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
