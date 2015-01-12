<?php

namespace App\Model;

use Nette\Object,
  Nette\Database\Context as NDatabase;


/**
 * Abstraktní repozitář.
 */
abstract class Repository extends Object
{

  /** @var NDatabase */
  protected $database;

  /** @var string */
  protected $table;


  public function __construct(NDatabase $database)
  {
    $this->database = $database;
  }

  /**
   * Převod data do formátu, který je vyžadován databází
   * @param  String $date datum ve formátu dd.mm.rrrr
   * @return String       formát rrrr-mm-dd
   */
  public function dateDbFormat($date)
  {
    $arr = explode(".", $date);
    if(count($arr) != 3) return null;
    return $arr[2] . '-' . $arr[1] . '-' . $arr[0];
  }

  public function beginTransaction()
  {
    $this->database->beginTransaction();
  }
  public function rollbackTransaction()
  {
    $this->database->rollback();
  }
  public function commitTransaction()
  {
    $this->database->commit();
  }

  /**
   * Vrátí všechny platné záznamy
   *
   * @return \Nette\Database\Table\Selection
   */
  public function findAll()
  {
    return $this->database->table($this->table);
  }


  /**
   * Vrátí kolekci záznamů podle podmínky
   *
   * @param array
   * @return \Nette\Database\Table\Selection
   */
  public function findBy($where)
  {
    return $this->findAll()->where($where);
  }


  /**
   * Vrátí záznam podle primárního klíče
   *
   * @param int
   * @return \Nette\Database\Table\ActiveRow|FALSE
   */
  public function get($id)
  {
    return $this->findAll()->get($id);
  }


  /**
   * Vrátí jeden záznam podle podmínky
   *
   * @param array
   * @return \Nette\Database\Table\ActiveRow|FALSE
   */
  public function getBy($where)
  {
    return $this->findAll()->where($where)->fetch();
  }


  /**
   * Vloží nový záznam do tabulky
   *
   * @param array
   * @return \Nette\Database\Table\IRow|int
   */
  public function insert($data)
  {
    return $this->database->table($this->table)->insert($data);
  }


  public function update($id, $data)
  {
    return $this->database->table($this->table)->wherePrimary($id)->update($data);
  }

  public function delete($id)
  {
    return $this->database->table($this->table)->wherePrimary($id)->delete();
  }
}
