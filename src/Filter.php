<?php
namespace CnTech\JsonSql;

use Doctrine\DBAL\Query\QueryBuilder;

class Filter {
  
  protected $qb;
  protected $param_qb;
  protected $columns = array();
  
  public function __construct(QueryBuilder $queryBuilder, QueryBuilder $parameterQueryBuilder = null) {
    $this->qb = $queryBuilder;
    $this->param_qb = is_null($parameterQueryBuilder) ? $queryBuilder : $parameterQueryBuilder;
  }
  
  public function setAllowedColumns($columns) {
    $this->columns = $columns;
  }
  
  protected function applyOnField($field, $filter) {
  
    $qb = $this->qb;
    $param_qb = $this->param_qb;
    $columns = $this->columns;
    
    $secfield = SecureField::secureField($columns, $field);
    if($secfield !== FALSE) {
      if(is_array($filter)) {
        if(array_key_exists('$in', $filter)) {
          $in_query = $secfield.' IN (';
          $in_query.= join(', ', array_map(function($item) use ($param_qb) {
            return $param_qb->createNamedParameter($item);
          }, $filter['$in']));
          $in_query.= ')';
          return $in_query;
        }
        if(array_key_exists('$like', $filter)) {
          $secvalue = $param_qb->createNamedParameter($filter['$like']);
          return $qb->expr()->like($secfield, $secvalue);
        }
        if(array_key_exists('$not', $filter)) {
          $secvalue = $param_qb->createNamedParameter($filter['$not']);
          return $qb->expr()->neq($secfield, $secvalue);
        }
        if(array_key_exists('$lt', $filter)) {
          $secvalue = $param_qb->createNamedParameter($filter['$lt']);
          return $qb->expr()->lt($secfield, $secvalue);
        }
        if(array_key_exists('$lte', $filter)) {
          $secvalue = $param_qb->createNamedParameter($filter['$lte']);
          return $qb->expr()->lte($secfield, $secvalue);
        }
        if(array_key_exists('$gt', $filter)) {
          $secvalue = $param_qb->createNamedParameter($filter['$gt']);
          return $qb->expr()->gt($secfield, $secvalue);
        }
        if(array_key_exists('$gte', $filter)) {
          $secvalue = $param_qb->createNamedParameter($filter['$gte']);
          return $qb->expr()->gte($secfield, $secvalue);
        }
      } else {
        $secvalue = $param_qb->createNamedParameter($filter);
        return $qb->expr()->eq($secfield, $secvalue);
      }
    }
    return '(0=1)';
  }

  public function apply($filter) {
    
    $qb = $this->qb;
    
    $result = array();
    foreach($filter as $key => $value) {
      if($key[0] == '$') {
        $sub_result = $this->apply($value);
        if($filter['$and']) {
          array_push($result, call_user_func_array(array($qb->expr(), 'andX'), $sub_result));
        }
        if($filter['$or']) {
          array_push($result, call_user_func_array(array($qb->expr(), 'orX'), $sub_result));
        }
      } else {
        array_push($result, $this->applyOnField($key, $value));
      }
    }
    if(empty($result)) {
      return '(1=1)';
    }
    return $result;
  }
  
}

