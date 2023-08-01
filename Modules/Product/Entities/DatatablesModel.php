<?php

namespace Modules\Product\Entities;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatatablesModel extends Model
{

    public static function getRes($table, $column, $where = '')
    {
        DB::enableQueryLog();
        return DB::select("SELECT $column FROM $table $where");
    }

    public static function getRow($table, $column, $where = '')
    {
        DB::enableQueryLog();
        $s = DB::select("SELECT $column FROM $table $where");
        if (empty($s)) {
            return '';
        } else {
            return $s[0];
        }
    }

    public static function getQuery($q)
    {
        DB::enableQueryLog();
        $s = DB::select($q);
        return $s;
    }

    public static function setInsert($table, $data)
    {
        DB::enableQueryLog();
        return DB::table($table)->insert($data);
    }

    public static function setUpdate($table, $data, $where)
    {
        DB::enableQueryLog();
        return DB::table($table)->where($where)->update($data);
    }

    public static function setDelete($table, $where)
    {
        DB::enableQueryLog();
        return DB::table($table)->where($where)->delete();
    }

    public static function lastQuery()
    {
        return DB::getQueryLog();
    }

    public static function getDatatable($request_post, $table, $column_order, $column_search, $order, $where = '', $join = '', $groupby = '')
    {
        DB::enableQueryLog();
        $builder = DB::table($table);
        $i = 0;
        $whereRaw = '';
        foreach ($column_search as $item) {
            if ($request_post['search']['value']) {
                if ($i == 0) {
                    $whereRaw = "( " . $item . " LIKE '%" . $request_post['search']['value'] . "%'";
                    if(count($column_search) == 1){
                        $whereRaw .= ')';
                    }
                } else {
                    if (count($column_search) == ($i + 1)) {
                        $whereRaw .= " OR $item LIKE '%" . $request_post['search']['value'] . "%')";
                    } else {
                        $whereRaw .= " OR $item LIKE '%" . $request_post['search']['value'] . "%'";
                    }
                }
            }
            $i++;
        }
        if ($whereRaw) {
            $builder->whereRaw($whereRaw);
        }
        if (isset($request_post['order'])) {
            $builder->orderBy($column_order[$request_post['order']['0']['column']], $request_post['order']['0']['dir']);
        } else if (!empty($order)) {
            $order = $order;
            $builder->orderBy(key($order), $order[key($order)]);
        }
        if ($request_post['length'] != -1)
            $builder->offset($request_post['start'])->limit($request_post['length']);

        if ($groupby) {
            $builder->groupBy($groupby);
        }

        if ($where) {
            for ($i = 0; $i < count($where); $i++) {
                if(empty($where[$i][3])){
                    $builder->where($where[$i][0], $where[$i][1], $where[$i][2]);
                } else {
                    if ($where[$i][3] == 'NULL') {
                        $builder->whereNull($where[$i][0]);
                    } else if ($where[$i][3] == 'DATE') {
                        $builder->whereDate($where[$i][0], $where[$i][1], $where[$i][2]);
                    } else if ($where[$i][3] == 'NOTNULL') {
                        $builder->whereNotNull($where[$i][0]);
                    } else if ($where[$i][3] == 'IN') {
                        $builder->whereIn($where[$i][0], $where[$i][1]);
                    } 
                }
            }
        }
        if (!empty($join)) {
            for ($i = 0; $i < count($join); $i++) {
                $builder->join($join[$i][0], $join[$i][1], '=', $join[$i][2]);
            }
        }
        $query = $builder->get();
        return $query;
    }

    public static function countFiltered($request_post, $table, $column_order, $column_search, $order, $where = '', $join = '', $groupby = '')
    {
        DB::enableQueryLog();
        $builder = DB::table($table);
        $i = 0;
        $whereRaw = '';
        foreach ($column_search as $item) {
            if ($request_post['search']['value']) {
                if ($i == 0) {
                    $whereRaw = "( " . $item . " LIKE '%" . $request_post['search']['value'] . "%'";
                    if(count($column_search) == 1){
                        $whereRaw .= ')';
                    }
                } else {
                    if (count($column_search) == ($i + 1)) {
                        $whereRaw .= " OR $item LIKE '%" . $request_post['search']['value'] . "%')";
                    } else {
                        $whereRaw .= " OR $item LIKE '%" . $request_post['search']['value'] . "%'";
                    }
                }
            }
            $i++;
        }
        if ($whereRaw) {
            $builder->whereRaw($whereRaw);
        }
        if (isset($request_post['order'])) {
            $builder->orderBy($column_order[$request_post['order']['0']['column']], $request_post['order']['0']['dir']);
        } else if (!empty($order)) {
            $order = $order;
            $builder->orderBy(key($order), $order[key($order)]);
        }

        if (!empty($join)) {
            for ($i = 0; $i < count($join); $i++) {
                $builder->join($join[$i][0], $join[$i][1], '=', $join[$i][2]);
            }
        }
        if ($where) {
            for ($i = 0; $i < count($where); $i++) {
                if ($where[$i][3] == 'NULL') {
                    $builder->whereNull($where[$i][0]);
                } else if ($where[$i][3] == 'DATE') {
                    $builder->whereDate($where[$i][0], $where[$i][1], $where[$i][2]);
                } else if ($where[$i][3] == 'NOTNULL') {
                    $builder->whereNotNull($where[$i][0]);
                } else if ($where[$i][3] == 'IN') {
                    $builder->whereIn($where[$i][0], $where[$i][1]);
                } else {
                    $builder->where($where[$i][0], $where[$i][1], $where[$i][2]);
                }
            }
        }
        if ($groupby) {
            $builder->groupBy($groupby);
        }
        return $builder->get()->count();
    }

    public static function countAll($request_post, $table, $where = NULL, $join = NULL, $groupby = NULL)
    {
        DB::enableQueryLog();
        $builder = DB::table($table);
        if (!empty($join)) {
            for ($i = 0; $i < count($join); $i++) {
                $builder->join($join[$i][0], $join[$i][1], '=', $join[$i][2]);
            }
        }
        if ($groupby) {
            $builder->groupBy($groupby);
        }
        if ($where) {
            for ($i = 0; $i < count($where); $i++) {
                if ($where[$i][3] == 'NULL') {
                    $builder->whereNull($where[$i][0]);
                } else if ($where[$i][3] == 'DATE') {
                    $builder->whereDate($where[$i][0], $where[$i][1], $where[$i][2]);
                } else if ($where[$i][3] == 'NOTNULL') {
                    $builder->whereNotNull($where[$i][0]);
                } else if ($where[$i][3] == 'IN') {
                    $builder->whereIn($where[$i][0], $where[$i][1]);
                } else {
                    $builder->where($where[$i][0], $where[$i][1], $where[$i][2]);
                }
            }
        }
        return $builder->get()->count();
    }

    public static function getId()
    {
        return DB::getPdo()->lastInsertId();
    }
}