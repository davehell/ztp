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
    return $this->database->table('tagy')->fetchPairs('id', 'nazev');
  }

  /**
   * Všechny změny v dané verzi
   * @param  $verze  ID verze
   * @param  $autor  ID autora. Pouze ty změny, kterých je autorem.
   * @param  $tester ID testera. Pouze ty změny, které testuje.
   * @return \Nette\Database\Table\Selection
   */
  public function zmenyVeVerzi($verze, $autor = null, $tester = null)
  {
    $zmeny = $this->findAll()->where('verze_id', $verze)->order('poradi ASC');

    if($autor) $zmeny = $zmeny->where('autor_id', $autor);
    if($tester) $zmeny = $zmeny->where('tester_id', $tester);
    return $zmeny;
  }

  /**
   * Počet všech změny v dané verzi
   * @param  $verze  ID verze
   * @param  $autor  ID autora. Pouze ty změny, kterých je autorem.
   * @param  $tester ID testera. Pouze ty změny, které testuje.
   * @return int
   */
  public function pocetZmenVeVerzi($verze, $autor = null, $tester = null)
  {
    return $this->zmenyVeVerzi($verze, $autor, $tester)->count('*');
  }

  /**
   * Pouze veřejné změny v dané verzi - využití pro exporty protokolů.
   * @param  $verze  ID verze
   * @param  $tag    ID tagu podniku
   * @return \Nette\Database\Table\Selection
   */
  public function verejneZmenyVeVerzi($verze, $tag = null)
  {
    $zmeny = $this->zmenyVeVerzi($verze)->where('je_verejna', true);
    if($tag === null) { //změny, které nemají žadný tag
      $zmeny = $zmeny->where(':zmeny_tagy.tagy.id ?', null);
    }
    else { //změny, které nemají žadný tag nebo mají zadaný nějaký tag
      $zmeny = $zmeny->where(':zmeny_tagy.tagy.id ? OR :zmeny_tagy.tagy.id ?', null, $tag);
    }
    return $zmeny;
  }

  /**
   * Všechny druhy tagů použitých u změn ve verzi.
   * @param  $verze  ID verze
   * @return \Nette\Database\Table\Selection
   */
  public function tagyVeVerzi($verze)
  {
    return $this->zmenyVeVerzi($verze)->where(':zmeny_tagy.tagy.id IS NOT NULL')->select(':zmeny_tagy.tagy.*')->group(':zmeny_tagy.tagy.id');
  }

  /**
   * Seznam ID změn v pořadí v jakém mají být v protokolu
   * @param  $verze  ID verze
   * @return array
   */
  public function poradiZmenVeVerzi($verze)
  {
    $zmeny = $this->zmenyVeVerzi($verze);
    $poradi = array();
    foreach ($zmeny as $zmena) {
      $poradi[] = $zmena->id;
    }
    return $poradi;
  }

  /**
   * Změnám se nastaví pořadí, v jakém mají být v protokolu
   * @param  array $poradi Seřazené pole s ID změn
   */
  public function aktualizovatPoradiZmen($poradi)
  {
    $index = 0;
    if(count($poradi) == 0) return;
    try {
      $this->beginTransaction();
      foreach ($poradi as $id) {
        $this->update($id, array('poradi' => ++$index));
      }
      $this->commitTransaction();
    }
    catch(\Exception $e) {
      $this->rollbackTransaction();
      throw $e;
    }
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
   * Počet změn ve verzi, které ještě nejsou otestované (buď je v nich chyba, nebo ještě testování nezačalo)
   * @return int
   */
  public function pocetNeotestovanych($verze, $autor = null, $tester = null)
  {
    return $this->neotestovane($verze, $autor, $tester)->count('*');
  }

  /**
   * Nově přidané změny do verze - nemají přiřazeného testera a nejsou ještě otestované
   * @return \Nette\Database\Table\Selection
   */
  public function bezTestera($verze)
  {
    return $this->neotestovane($verze)->where('tester_id IS NULL');
  }

  /**
   * Počet nově přidaných změn do verze - nemají přiřazeného testera a nejsou ještě otestované
   * @return int
   */
  public function pocetBezTestera($verze)
  {
    return $this->bezTestera($verze)->count('*');
  }

  /**
   * Jména testerů, kteří testovali změny v dané verzi
   * @return \Nette\Database\Table\Selection
   */
  public function testeriVeVerzi($verze)
  {
    return $this->findAll()->where('verze_id', $verze)->select('DISTINCT tester_id')->where('tester_id IS NOT NULL')->order('tester.jmeno');
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

  /**
   * Zjištění tagů náležejících k dané změně
   */
  public function tagyProZmenu($id)
  {
    return $this->database->table('zmeny_tagy')->where('zmeny_id', $id)->fetchPairs('tagy_id', 'tagy_id');
  }

  /**
   * Uložení tagů náležejících k dané změně
   */
  public function aktualizaceTagu($id, $tagy)
  {
    try {
      $this->beginTransaction();
      $this->database->table('zmeny_tagy')->where('zmeny_id', $id)->delete();
      if(count($tagy)) $this->database->table('zmeny_tagy')->insert($tagy);
      $this->commitTransaction();
    }
    catch(\Exception $e) {
      $this->rollbackTransaction();
      throw $e;
    }
  }

  /**
   * Změny, které obsahují hledaný výraz
   * @return \Nette\Database\Table\Selection
   */
  public function vyhledavani($text)
  {
    $vyraz = '%' . $text . '%';
    return $this->findAll()
      ->select('zmeny.id AS zmena_id, zmeny.uloha, zmeny.text, zmeny.detail')
      ->select('verze.nazev AS verze, verze.id AS verze_id')
      ->where('uloha LIKE ? OR text LIKE ? OR detail LIKE ?', $vyraz, $vyraz, $vyraz)
      ->order('verze.datum DESC')
      ->limit(100);
  }
}
