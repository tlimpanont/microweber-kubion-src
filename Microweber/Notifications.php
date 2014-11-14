<?php
namespace Microweber;

//event_bind('mw_db_init_default', '\Microweber\Notifications\db_init');


api_expose('Notifications/delete');
//api_expose('Notifications/save');
api_expose('Notifications/reset');
api_expose('Notifications/read');

api_expose('Notifications/mark_all_as_read');

//event_bind('mw_db_init', mw('notifications')->db_init());


class Notifications
{
    public $app;

    function __construct($app = null)
    {

        if (defined("INI_SYSTEM_CHECK_DISABLED") == false) {
            define("INI_SYSTEM_CHECK_DISABLED", ini_get('disable_functions'));
        }

        if (!defined("MW_DB_TABLE_NOTIFICATIONS")) {
            define('MW_DB_TABLE_NOTIFICATIONS', MW_TABLE_PREFIX . 'notifications');
        }


        if (!is_object($this->app)) {

            if (is_object($app)) {
                $this->app = $app;
            } else {
                $this->app = Application::getInstance();
            }

        }


        if (!defined("MW_DB_TABLE_NOTIFICATIONS_DB_INIT")) {
            define('MW_DB_TABLE_NOTIFICATIONS_DB_INIT', true);
            $this->db_init();
        }

    }

    public function read($id)
    {
        if (defined('MW_API_CALL')) {
            $is_admin = $this->app->user->is_admin();
            if ($is_admin == false) {
                return array('error' => "You must be logged in as admin to perform: " . __CLASS__ . '->' . __FUNCTION__);
            }
        }



        if (is_array($id)) {
            $id = array_pop($id);
        }

        $params = array();
        $params['id'] = trim($id);
        $params['one'] = true;

        $get = $this->get($params);

        if ($get != false and isset($get['is_read']) and $get['is_read'] == 'n') {
            $save = array();
            $save['id'] = $get['id'];
            $save['is_read'] = 'y';
            $table = MW_DB_TABLE_NOTIFICATIONS;
            mw_var('FORCE_SAVE', $table);
            $data = $this->app->db->save($table, $save);
            $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . $data);
            $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . 'global');

        }

        return $get;
    }


    public function mark_as_read($module)
    {

        if (($module) != false and $module != '') {

            $table = MW_DB_TABLE_NOTIFICATIONS;

            mw_var('FORCE_SAVE', $table);

            $get_params = array();
            $get_params['table'] = $table;
            $get_params['is_read'] = 'n';
            $get_params['fields'] = 'id';
           // $get_params['debug'] = 'id';
            if ($module != 'all') {

                $get_params['module'] = $this->app->db->escape_string($module);
            }
            $data = $this->get($get_params);


            if (is_array($data)) {
                foreach ($data as $value) {
                    $save['is_read'] = 'y';
                    $save['id'] = $value['id'];
                    $save['table'] = 'table_notifications';
                    $this->app->db->save('table_notifications', $save);
                }
            }

            $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . 'global');
            $this->app->cache->delete('notifications');

            return $data;
        }
    }


    public function mark_all_as_read()
    {

        $is_admin = $this->app->user->is_admin();
        if (defined('MW_API_CALL') and $is_admin == false) {
            return array('error' => "You must be logged in as admin to perform: " . __CLASS__ . '->' . __FUNCTION__);
        }

        $table = MW_DB_TABLE_NOTIFICATIONS;

        $q = "UPDATE $table SET is_read='y' WHERE is_read='n' ";

        $this->app->db->q($q);
        $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . 'global');

        return true;

    }


    public function reset()
    {

        $is_admin = $this->app->user->is_admin();
        if (defined('MW_API_CALL') and $is_admin == false) {
            return array('error' => "You must be logged in as admin to perform: " . __CLASS__ . '->' . __FUNCTION__);
        }

        $table = MW_DB_TABLE_NOTIFICATIONS;

        $q = "UPDATE $table SET is_read='n'";
        $this->app->db->q($q);
        $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . 'global');

        return true;

    }

    public function delete($id)
    {

        $is_admin = $this->app->user->is_admin();
        if (defined('MW_API_CALL') and $is_admin == false) {
            return array('error' => "You must be logged in as admin to perform: " . __CLASS__ . '->' . __FUNCTION__);
        }
        if (is_array($id)) {
            $id = array_pop($id);
        }

        $table = MW_DB_TABLE_NOTIFICATIONS;

        if($id == 'all'){

            $q = "DELETE FROM $table where id is not NULL  ";

            $this->app->db->q($q);

        } else {
            $this->app->db->delete_by_id($table, intval($id), $field_name = 'id');
        }


        $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . intval($id));

        $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . 'global');

        return true;

    }

    public function delete_for_module($module)
    {

        if (($module) != false and $module != '') {

            $table = MW_DB_TABLE_NOTIFICATIONS;

            mw_var('FORCE_SAVE', $table);

            $get_params = array();
            $get_params['table'] = 'table_notifications';
            $get_params['fields'] = 'id';
            $get_params['module'] = $this->app->db->escape_string($module);

            $data = $this->get($get_params);
            if (is_array($data)) {
                $ids = $this->app->format->array_values($data);
                $idsi = implode(',', $ids);
                $cleanup = "DELETE FROM $table WHERE id IN ({$idsi})";
                $this->app->db->q($cleanup);
            }

            $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . 'global');
            return true;
        }
    }

    public function db_init()
    {

        $function_cache_id = false;
        $args = func_get_args();
        foreach ($args as $k => $v) {

            $function_cache_id = $function_cache_id . serialize($k) . serialize($v);
        }
        $function_cache_id = 'notifications_' . __FUNCTION__ . crc32($function_cache_id);
        $cache_content = $this->app->cache->get($function_cache_id, 'db');
        if (($cache_content) != false) {

            return $cache_content;
        }
        $table_name = MW_DB_TABLE_NOTIFICATIONS;
        $fields_to_add = array();
        $fields_to_add[] = array('updated_on', 'datetime default NULL');
        $fields_to_add[] = array('created_on', 'datetime default NULL');
        $fields_to_add[] = array('created_by', 'int(11) default NULL');
        $fields_to_add[] = array('edited_by', 'int(11) default NULL');
        $fields_to_add[] = array('data_type', 'TEXT default NULL');
        $fields_to_add[] = array('title', 'longtext default NULL');
        $fields_to_add[] = array('description', 'TEXT default NULL');
        $fields_to_add[] = array('content', 'TEXT default NULL');
        $fields_to_add[] = array('module', 'TEXT default NULL');

        $fields_to_add[] = array('rel', 'TEXT default NULL');
        $fields_to_add[] = array('rel_id', 'TEXT default NULL');
        $fields_to_add[] = array('notif_count', 'int(11) default 1');

        $fields_to_add[] = array('is_read', "char(1) default 'n'");

        $this->app->db->build_table($table_name, $fields_to_add);

        $this->app->db->add_table_index('rel', $table_name, array('rel(55)'));
        $this->app->db->add_table_index('rel_id', $table_name, array('rel_id(55)'));

        $this->app->cache->save($fields_to_add, $function_cache_id, $cache_group = 'db');
        return true;

    }

    public function save($params)
    {

        $params = parse_params($params);

        // if (!isset($params['rel']) and isset($params['module']) and trim($params['module']) != '') {
        // $params['rel'] = 'modules';
        // $params['rel_id'] = $params['module'];
        // }

        //$adm = $this->app->user->is_admin();

        $table = MW_DB_TABLE_NOTIFICATIONS;
        mw_var('FORCE_SAVE', $table);

        if (!isset($params['rel']) or !isset($params['rel_id'])) {
            return ('Error: invalid data you must send rel and rel_id as params for $this->save function');
        }
        $old = date("Y-m-d H:i:s", strtotime('-30 days'));
        $cleanup = "DELETE FROM $table WHERE created_on < '{$old}'";
        $this->app->db->q($cleanup);

        if (isset($params['replace'])) {
            if (isset($params['module']) and isset($params['rel']) and isset($params['rel_id'])) {
                unset($params['replace']);
                $rel1 = $this->app->db->escape_string($params['rel']);
                $module1 = $this->app->db->escape_string($params['module']);
                $rel_id1 = $this->app->db->escape_string($params['rel_id']);
                $cleanup = "DELETE FROM $table WHERE rel='{$rel1}' AND module='{$module1}' AND rel_id='{$rel_id1}'";
                $this->app->db->q($cleanup);


            }

        }


        $this->app->cache->delete('notifications' . DIRECTORY_SEPARATOR . 'global');

        $data = $this->app->db->save($table, $params);
        return $data;
    }

    function get_by_id($id)
    {
        $params = array();

        if ($id != false) {
            if (substr(strtolower($id), 0, 4) == 'log_') {

            }

            $params['id'] = $this->app->db->escape_string($id);
            $params['one'] = true;

            $get = $this->get($params);
            return $get;

        }
    }
    public function get_admin($params = false)
    {

    }

    public function get($params = false)
    {
        $params = parse_params($params);

        // if (!isset($params['rel']) and isset($params['module']) and trim($params['module']) != '') {
        // $params['rel'] = 'modules';
        // $params['rel_id'] = $params['module'];
        // }
        //
        $return = array();
        $is_sys_log = false;
        if (isset($params['id'])) {
            $is_log = substr(strtolower($params['id']), 0, 4);
            if ($is_log == 'log_') {
                $is_sys_log = 1;
                $is_log_id = str_ireplace('log_', '', $params['id']);
                $log_entr = $this->app->log->get_entry_by_id($is_log_id);
                if ($log_entr != false and isset($params['one'])) {
                    return $log_entr;

                } else if ($log_entr != false) {
                    $return[] = $log_entr;
                }
            }

        }
        if ($is_sys_log == false) {
            $table = MW_DB_TABLE_NOTIFICATIONS;
            $params['table'] = $table;
            $params['order_by'] = 'id desc';
            $return = $this->app->db->get($params);
        }
        return $return;
    }

}