<?php
namespace CnTech\JsonSql;

use Doctrine\DBAL\Connection;

class SubRecords {
  
  protected $db;
  
  public function __construct(Connection $db) {
    $this->db = $db;
  }

  public function includeSubRecords(
      &$parent_records, $parent_id_field,
      $child_table, $parent_ids) {
    
    // query sub-records
    $qb = $this->db->createQueryBuilder();
    $qb->select('*')->from($child_table);
    $parent_id_placeholders = array_map(function($item) use ($qb) {
      return $qb->createPositionalParameter($item);
    }, $parent_ids);
    $joined_parent_id_placeholders = join(', ', $parent_id_placeholders);
    $qb->where('"'.$parent_id_field.'" IN ('.$joined_parent_id_placeholders.')');
    $query = $qb->execute();
    $child_records = $query->fetchAll();
    
    // attach sub-records to parent records
    $keyed_parent_records = array();
    foreach($parent_records as &$parent_record) {
      $parent_record[$child_table] = array();
      $keyed_parent_records[$parent_record['id']] = &$parent_record;
    }
    foreach($child_records as $child_record) {
      array_push(
        $keyed_parent_records[$child_record[$parent_id_field]][$child_table],
        $child_record
      );
    }
    
  }
  
}

