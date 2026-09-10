<?php
interface db_interface {
	public function get($key);
	public function multi_get($keys);
	public function set($key, $data);
	public function update($key, $data);
	public function delete($key);
	public function maxid($key, $val = FALSE);
	public function count($key, $val = FALSE);
	public function truncate($table);
	public function version();

	public function find_fetch($table, $pri, $where = array(), $order = array(), $start = 0, $limit = 0);
	public function find_fetch_key($table, $pri, $where = array(), $order = array(), $start = 0, $limit = 0);
	public function find_update($table, $where, $data, $order = array(), $limit = 0, $lowprority = FALSE);
	public function find_delete($table, $where, $order = array(), $limit = 0, $lowprority = FALSE);
	public function find_maxid($key);
	public function find_count($table, $where = array());

	//创建和删除索引
	public function index_create($table, $index);
	public function index_drop($table, $index);

	//获取表字段、判断表是否存在
    public function get_field($table);
    public function exist_table($table);

    //删除表、创建表、删除数据库
    public function table_drop($table);
    public function table_create($table, $cols);
    public function delete_db();
}
