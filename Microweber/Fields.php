<?php
namespace Microweber;

api_expose('fields/reorder');
api_expose('fields/delete');
api_expose('fields/make');
api_expose('fields/save');
$_mw_made_default_fields_register = array();

class Fields
{

    public $app;
    public $tables = array();
    private $skip_cache = false;

    function __construct($app = null)
    {


        if (!is_object($this->app)) {

            if (is_object($app)) {
                $this->app = $app;
            } else {
                $this->app = Application::getInstance();
            }

        }

        $this->tables = $this->app->content->tables;

    }

    public function get_by_id($field_id)
    {
        if ($field_id != 0) {
            $data = $this->app->db->get_by_id('table_custom_fields', $id = $field_id, $is_this_field = false);
            $data = $this->decode_array_vals($data);
            return $data;
        }
    }

    public function make_default($rel, $rel_id, $fields_csv_str)
    {
        global $_mw_made_default_fields_register;
        if (!defined('SKIP_CF_ADMIN_CHECK')) {
            define('SKIP_CF_ADMIN_CHECK', 1);
        }

        // return false;
        $id = $this->app->user->is_admin();
        if ($id == false) {
            //return false;
        }

        $function_cache_id = false;

        $args = func_get_args();

        foreach ($args as $k => $v) {

            $function_cache_id = $function_cache_id . serialize($k) . serialize($v);
        }

        $function_cache_id = 'fields_' . __FUNCTION__ . crc32($function_cache_id);


        //$is_made = $this->app->option->get($function_cache_id, 'make_default_custom_fields');


        $is_made = $this->get($rel, $rel_id, $return_full = 0, $field_for = false, $debug = 0);

        if (isset($_mw_made_default_fields_register[$function_cache_id])) {
            return;
        }


        if (is_array($is_made) and !empty($is_made)) {
            return;
        }
        $_mw_made_default_fields_register[$function_cache_id] = true;

        $table_custom_field = $this->tables['custom_fields'];

        if (isset($rel)) {
            $rel = $this->app->db->escape_string($rel);

            $rel = $this->app->db->assoc_table_name($rel);
            $rel_id = $this->app->db->escape_string($rel_id);

            if (strstr($fields_csv_str, ',')) {
                $fields_csv_str = explode(',', $fields_csv_str);
                $fields_csv_str = array_trim($fields_csv_str);
            } else {
                $fields_csv_str = array($fields_csv_str);
            }


            $pos = 0;
            if (is_array($fields_csv_str)) {
                foreach ($fields_csv_str as $field_type) {
                    $ex = $this->get($rel, $rel_id, $return_full = 1, $field_for = false, $debug = 0, $field_type);

                    if ($ex == false or is_array($ex) == false) {
                        $make_field = array();
                        $make_field['id'] = 0;
                        $make_field['rel'] = $rel;
                        $make_field['rel_id'] = $rel_id;
                        $make_field['position'] = $pos;
                        $make_field['custom_field_name'] = ucfirst($field_type);
                        $make_field['custom_field_value'] = false;

                        $make_field['custom_field_type'] = $field_type;

                        $this->save($make_field);
                        $pos++;
                    }
                }


                if ($pos > 0) {
                    $this->app->cache->delete('custom_fields/global');

                }


                if ($rel_id != '0') {
                    $option = array();
                    $option['option_value'] = 'yes';
                    $option['option_key'] = $function_cache_id;
                    $option['option_group'] = 'make_default_custom_fields';
                    //   $this->app->option->save($option);
                }

            }

            //

        }


    }

    public function save($data)
    {
        if (is_string($data)) {
            $data = parse_params($data);
        }
        if (defined('MW_API_CALL') and !defined('SKIP_CF_ADMIN_CHECK')) {
            $id = user_id();
            if ($id == 0) {
                $this->app->error('Error: not logged in.');
            }
            $id = $this->app->user->is_admin();
            if ($id == false) {
                $this->app->error('Error: not logged in as admin.' . __FILE__ . __LINE__);
            }
        }


        $table_custom_field = $this->tables['custom_fields'];

        if (isset($data['field_type']) and !isset($data['custom_field_type'])) {
            $data['custom_field_type'] = $data['field_type'];
        }


        if (isset($data['field_value']) and !isset($data['custom_field_value'])) {
            $data['custom_field_value'] = $data['field_value'];
        }
        if (isset($data['field_name']) and !isset($data['custom_field_name'])) {
            $data['custom_field_name'] = $data['field_name'];
        }
        if (isset($data['field_type']) and !isset($data['custom_field_type'])) {
            $data['custom_field_type'] = $data['field_type'];
        }

        if (isset($data['custom_field_type']) and !isset($data['custom_field_name'])) {
            $data['custom_field_name'] = $data['custom_field_type'];
        }

        $data_to_save = ($data);
        $data_to_save = $this->unify_params($data_to_save);

        if (isset($data_to_save['for_id'])) {
            $data_to_save['rel_id'] = $data_to_save['for_id'];
        }
        if (isset($data_to_save['cf_id'])) {
            $data_to_save['id'] = intval($data_to_save['cf_id']);
            $table_custom_field = $this->tables['custom_fields'];
            $form_data_from_id = $this->app->db->get_by_id($table_custom_field, $data_to_save['id'], $is_this_field = false);
            if (isset($form_data_from_id['id'])) {
                if (!isset($data_to_save['rel'])) {
                    $data_to_save['rel'] = $form_data_from_id['rel'];
                }
                if (!isset($data_to_save['rel_id'])) {
                    $data_to_save['rel_id'] = $form_data_from_id['rel_id'];
                }
                if (isset($form_data_from_id['custom_field_type']) and $form_data_from_id['custom_field_type'] != '' and (!isset($data_to_save['custom_field_type']) or ($data_to_save['custom_field_type']) == '')) {
                    $data_to_save['custom_field_type'] = $form_data_from_id['custom_field_type'];
                }
                if (isset($form_data_from_id['custom_field_name']) and $form_data_from_id['custom_field_name'] != '' and (!isset($data_to_save['custom_field_name']) or ($data_to_save['custom_field_name']) == '')) {
                    $data_to_save['custom_field_name'] = $form_data_from_id['custom_field_name'];
                }
            }


            if (isset($data_to_save['copy_rel_id'])) {

                $cp = $this->app->db->copy_row_by_id($table_custom_field, $data_to_save['cf_id']);
                $data_to_save['id'] = $cp;
                $data_to_save['rel_id'] = $data_to_save['copy_rel_id'];
                //$data_to_save['id'] = intval($data_to_save['cf_id']);
            }

        }

        if (!isset($data_to_save['rel'])) {
            $data_to_save['rel'] = 'content';
        }
        $data_to_save['rel'] = $this->app->db->assoc_table_name($data_to_save['rel']);
        if (!isset($data_to_save['rel_id'])) {
            $data_to_save['rel_id'] = '0';
        }
        if (isset($data['options'])) {
            $data_to_save['options'] = $this->app->format->array_to_base64($data['options']);
        }

        $data_to_save['session_id'] = session_id();
        if (!isset($data_to_save['custom_field_type']) and isset($data_to_save['type'])) {
            $data_to_save['custom_field_type'] = $data_to_save['type'];
        } else if (isset($data_to_save['custom_field_type'])) {
            $data_to_save['type'] = $data_to_save['custom_field_type'];
        }
        if (!isset($data_to_save['custom_field_name']) and isset($data_to_save['name'])) {
            $data_to_save['custom_field_name'] = $data_to_save['name'];
        } else if (isset($data_to_save['custom_field_name'])) {
            $data_to_save['name'] = $data_to_save['custom_field_name'];
        }
        if (!isset($data_to_save['custom_field_value']) and isset($data_to_save['value'])) {
            $data_to_save['custom_field_value'] = $data_to_save['value'];
        } else if (isset($data_to_save['custom_field_value'])) {
            $data_to_save['value'] = $data_to_save['custom_field_value'];
        }
        if (!isset($data_to_save['custom_field_values']) and isset($data_to_save['values'])) {
            $data_to_save['custom_field_values'] = $data_to_save['values'];

        } else if (isset($data_to_save['custom_field_values'])) {
            $data_to_save['values'] = $data_to_save['custom_field_values'];
        }

        if (!isset($data_to_save['custom_field_type']) or trim($data_to_save['custom_field_type']) == '') {
            return array('error' => 'You must set custom_field_type');
        } else {
            if (!isset($data_to_save['custom_field_name'])) {
                return array('error' => 'You must set custom_field_name');
            }
            $cf_k = $data_to_save['custom_field_name'];
            if ($cf_k != false and !isset($data_to_save['custom_field_name_plain'])) {
                $data_to_save['custom_field_name_plain'] = strtolower($cf_k);
            }
            if (isset($data_to_save['custom_field_value'])) {
                // return array('error' => 'You must set custom_field_value');
                //  $data_to_save['custom_field_value'] = '';
                $cf_v = $data_to_save['custom_field_value'];


                if (is_array($cf_v)) {
                    $single_val = false;
                    if (count($cf_v) == 1) {
                        $single_val = end($cf_v);
                    }

                    $cf_k_plain = $this->app->url->slug($cf_k);
                    $cf_k_plain = $this->app->db->escape_string($cf_k_plain);
                    $cf_k_plain = str_replace('-', '_', $cf_k_plain);
                    $data_to_save['custom_field_values'] = base64_encode(serialize($cf_v));
                    $val1_a = $this->app->format->array_values($cf_v);
                    //   $val1_a = array_pop($val1_a);
                    if (is_array($val1_a)) {
                        $val1_a = implode(', ', $val1_a);
                    }

                    if ($single_val != false) {

                        $data_to_save['custom_field_values_plain'] = $val1_a;
                        $data_to_save['values'] = $val1_a;
                        $val2_a = reset($cf_v);

                        $data_to_save['value'] = $val2_a;
                        $data_to_save['custom_field_value'] = $single_val;
                        $data_to_save['num_value'] = floatval($single_val);

                    } else {

                        if ($val1_a != 'Array') {
                            $data_to_save['custom_field_values_plain'] = $val1_a;
                            $val2_a = reset($cf_v);
                            $data_to_save['values'] = $val1_a;
                            $data_to_save['value'] = $val2_a;
                            if (is_array($cf_k_plain)) {
                                $numv = implode(',' . $cf_k_plain);
                            } else {
                                $numv = $cf_k_plain;
                            }
                            $flval = floatval($numv);
                            if ($flval != 0) {
                                $data_to_save['num_value'] = floatval($flval);

                            }

                            $data_to_save['custom_field_value'] = 'Array';
                        }
                    }


                } else {
                    if (strval($cf_v) != 'Array') {
                        $val1_a = nl2br($cf_v, 1);

                        $data_to_save['custom_field_values_plain'] = ($val1_a);
                        $data_to_save['values'] = $data_to_save['custom_field_value'];

                        $data_to_save['value'] = $val1_a;
                        $flval = floatval($val1_a);
                        if ($flval != 0) {
                            $data_to_save['num_value'] = floatval($flval);

                        }

                    }
                }
            }

            $data_to_save['allow_html'] = true;
            //  $data_to_save['debug'] = true;
            if (!isset($data_to_save['id'])) {
                $data_to_save['id'] = 0;
            }

            if (isset($data_to_save['value']) and is_string($data_to_save['value']) and !isset($data_to_save['num_value'])) {
                $data_to_save['num_value'] = floatval($data_to_save['value']);
            }

            $this->skip_cache = true;
            $save = $this->app->db->save($table_custom_field, $data_to_save);
            $this->app->cache->delete('custom_fields/global');
            $this->app->cache->delete('custom_fields/' . $save);
            $this->app->cache->delete('custom_fields');

            return $save;
        }
    }

    function get_value($content_id, $field_name, $return_full = false, $table = 'content')
    {
        $val = false;
        $data = $this->get($table, $id = $content_id, $return_full, $field_for = false, $debug = false, $field_type = false, $for_session = false);
        foreach ($data as $item) {
            if (isset($item['custom_field_name']) and
                ((strtolower($item['custom_field_name']) == strtolower($field_name))
                    or (strtolower($item['custom_field_type']) == strtolower($item['custom_field_type'])))
            ) {
                $val = $item['custom_field_value'];
            }
        }
        return $val;
    }

    public function get($table, $id = 0, $return_full = false, $field_for = false, $debug = false, $field_type = false, $for_session = false)
    {


        //defined in common.php
        $params = array();
        $no_cache = false;
        $table_assoc_name = false;
        // $id = intval ( $id );
        if (is_string($table)) {
            $params = $params2 = parse_params($table);
            if (!is_array($params2) or (is_array($params2) and count($params2) < 2)) {
                $id = trim($id);
                $table = $this->app->db->escape_string($table);
                if ($table != false) {
                    $table_assoc_name = $this->app->db->get_table_name($table);
                } else {
                    $table_assoc_name = "MW_ANY_TABLE";
                }
                if ($table_assoc_name == false) {
                    $table_assoc_name = $this->app->db->assoc_table_name($table_assoc_name);
                }
            } else {
                $params = $params2;
            }
        } elseif (is_array($table)) {
            $params = $table;
        }

        $params = $this->unify_params($params);

        if (!isset($table_assoc_name)) {
            if (isset($params['for'])) {
                $params['rel'] = $table_assoc_name = $this->app->db->assoc_table_name($params['for']);
            }
        } else {
            $params['rel'] = $table_assoc_name;
        }

        if (isset($params['debug'])) {
            $debug = $params['debug'];
        }
        if (isset($params['for'])) {
            if (!isset($params['rel']) or $params['rel'] == false) {
                $params['for'] = $params['rel'];
            }
        }
        if (isset($params['for_id'])) {
            $params['rel_id'] = $id = $this->app->db->escape_string($params['for_id']);
        }
        if (isset($params['field_type'])) {
            $params['custom_field_type'] = $params['field_type'];
        }


        if (isset($params['no_cache'])) {
            $no_cache = $params['no_cache'];
        }

        if (isset($params['field_type'])) {
            $field_type = $this->app->db->escape_string($params['field_type']);
        } else if (isset($params['custom_field_type'])) {
            $field_type = $this->app->db->escape_string($params['custom_field_type']);
        }
        if (isset($params['return_full'])) {
            $return_full = $params['return_full'];
        }

        if (isset($params['is_active']) and strtolower(trim($params['is_active'])) == 'any') {

        } else if (isset($params['is_active']) and $params['is_active'] == 'n') {
            $custom_field_is_active = 'n';
        } else {
            $custom_field_is_active = 'y';

        }

        $table_custom_field = $this->tables['custom_fields'];
        $params['table'] = $table_custom_field;
        $params['is_active'] = $custom_field_is_active;
        if (strval($table_assoc_name) != '') {
            if ($field_for != false) {
                $field_for = trim($field_for);
                $field_for = $this->app->db->escape_string($field_for);
                $params['custom_field_name'] = $field_for;
            }

            if ($params['rel'] == 'MW_ANY_TABLE') {
                unset($params['rel']);
            }


            $sidq = '';
            if (intval($id) == 0 and $for_session != false) {
                if (is_admin() != false) {
                    $sid = session_id();
                    $params['session_id'] = $sid;
                }
            }

            if ($id != 'all' and $id != 'any') {
                $id = $this->app->db->escape_string($id);

                $params['rel_id'] = $id;
            }


        }
        if (isset($params['content'])) {
            unset($params['content']);
        }
        if (!isset($params['order_by']) and !isset($params['orderby'])) {
            $params['order_by'] = 'position asc';
        }


        if (empty($params)) {
            return false;
        }

        $q = $this->app->db->get($params);

        if (isset($params['fields'])) {
            return $q;
        }
        if (!empty($q)) {
            $the_data_with_custom_field__stuff = array();

            if ($return_full == true) {
                $to_ret = array();
                $i = 1;
                foreach ($q as $it) {
                    $it = $this->decode_array_vals($it);
                    $it['type'] = $it['custom_field_type'];
                    $it['position'] = $i;
                    if (isset($it['options'])) {
                        $it['options'] = $this->app->format->base64_to_array($it['options']);
                    }
                    $it['title'] = $it['custom_field_name'];
                    $it['required'] = $it['custom_field_required'];
                    $to_ret[] = $it;
                    $i++;
                }
                return $to_ret;
            }

            $append_this = array();
            if (is_array($q) and !empty($q)) {
                foreach ($q as $q2) {

                    $i = 0;

                    $the_name = false;

                    $the_val = false;

                    foreach ($q2 as $cfk => $cfv) {


                        if ($cfk == 'custom_field_name') {

                            $the_name = $cfv;
                        }

                        if ($cfk == 'custom_field_value') {

                            if (strtolower($cfv) == 'array') {

                                if (isset($q2['custom_field_values_plain']) and is_string($q2['custom_field_values_plain']) and trim($q2['custom_field_values_plain']) != '') {
                                    $cfv = $q2['custom_field_values_plain'];

                                } else if (isset($q2['custom_field_values']) and is_string($q2['custom_field_values'])) {
                                    $try = base64_decode($q2['custom_field_values']);

                                    if ($try != false and strlen($try) > 3) {
                                        $cfv = unserialize($try);

                                    }
                                }

                            }

                            $the_val = $cfv;
                        }

                        $i++;
                    }


                    if ($the_name != false and $the_val !== null) {

                        if ($return_full == false) {
                            $the_name = strtolower($the_name);

                            $the_data_with_custom_field__stuff[$the_name] = $the_val;

                        } else {

                            $the_data_with_custom_field__stuff[$the_name] = $q2;
                        }
                    }
                }
            }

            $result = $the_data_with_custom_field__stuff;
            //$result = (array_change_key_case($result, CASE_LOWER));
            $result = $this->app->url->replace_site_url_back($result);

            //

            return $result;
        }


        return $q;
    }

    public function unify_params($data)
    {

        if (isset($data['rel'])) {
            if ($data['rel'] == 'content' or $data['rel'] == 'page' or $data['rel'] == 'post') {
                $data['rel'] = 'content';
            }
            $data['rel'] = $data['rel'];
        }

        if (isset($params['content_id'])) {
            $params['for'] = 'content';
            $params['for_id'] = $params['content_id'];

        }
        if (isset($data['content_id'])) {
            $data['rel'] = 'content';
            $data['rel_id'] = intval($data['content_id']);
        }

        if (!isset($data['custom_field_type']) and isset($data['field_type']) and $data['field_type'] != '') {
            $data['custom_field_type'] = $data['field_type'];
        }

        if (isset($data['type']) and (!isset($data['custom_field_type']))) {
            $data['custom_field_type'] = $data['type'];
        }
        if (isset($data['name']) and (!isset($data['custom_field_name']))) {
            $data['custom_field_name'] = $data['name'];
        }
        if (isset($data['field_value'])) {
            $data['custom_field_value'] = $data['value'] = $data['field_value'];
        }
        if (isset($data['value']) and (!isset($data['custom_field_value']))) {
            $data['custom_field_value'] = $data['value'];
        }

        if (!isset($data['custom_field_name']) and isset($data['field_name']) and $data['field_type'] != '') {
            $data['custom_field_name'] = $data['field_name'];
        }

        if (isset($data['custom_field_type']) and !isset($data['custom_field_name'])) {
            //    $data['custom_field_name'] = $data['custom_field_type'];
        }


        if (!isset($data['custom_field_value']) and isset($data['field_value']) and $data['field_value'] != '') {
            $data['custom_field_value'] = $data['field_value'];
        }
        if (!isset($data['rel']) and isset($data['for'])) {
            $data['rel'] = $this->app->db->assoc_table_name($data['for']);
        }

        if (!isset($data['cf_id']) and isset($data['id'])) {
            $data['cf_id'] = $data['id'];
        }
        if (!isset($data['rel_id'])) {
            if (isset($data['data-id'])) {
                $data['rel_id'] = $data['data-id'];
            }
        }
        if (!isset($data['custom_field_is_active']) and isset($data['cf_id']) and $data['cf_id'] == 0) {
            $data['custom_field_is_active'] = 'y';

        }

        if (!isset($params['rel']) and isset($params['for'])) {
            $params['rel'] = $params['for'];
        }
        if (isset($params['for_id'])) {
            $params['rel_id'] = $params['for_id'];
        }


        return $data;
    }

    public function decode_array_vals($it)
    {
        if (isset($it['custom_field_value'])) {
            $it['value'] = $it['custom_field_value'];
            if (isset($it['custom_field_value']) and strtolower($it['custom_field_value']) == 'array') {
                if (isset($it['custom_field_values']) and is_string($it['custom_field_values'])) {
                    $try = base64_decode($it['custom_field_values']);
                    if ($try != false and strlen($try) > 5) {
                        $it['custom_field_values'] = unserialize($try);
                    }
                    if (isset($it['custom_field_values']['value'])) {
                        $temp = $it['custom_field_values']['value'];
                        if (is_array($it['custom_field_values']['value'])) {
                            $temp = array();
                            foreach ($it['custom_field_values']['value'] as $item1) {
                                if ($item1 != false) {
                                    $item1 = explode(',', $item1);
                                    $temp = array_merge($temp, $item1);
                                }
                            }
                        }
                        $it['custom_field_values'] = $temp;
                    }
                }
            }
        }
        if (isset($it['options'])) {
            $it['options'] = $this->app->format->base64_to_array($it['options']);
        }
        return $it;
    }

    public function reorder($data)
    {

        $adm = $this->app->user->is_admin();
        if ($adm == false) {
            $this->app->error('Error: not logged in as admin.' . __FILE__ . __LINE__);
        }

        $table = $this->tables['custom_fields'];

        foreach ($data as $value) {
            if (is_array($value)) {
                $indx = array();
                $i = 0;
                foreach ($value as $value2) {
                    $indx[$i] = $value2;
                    $i++;
                }

                $this->app->db->update_position_field($table, $indx);
                return true;
            }
        }
    }

    public function delete($id)
    {
        $uid = user_id();
        if (defined('MW_API_CALL') and $uid == 0) {
            $this->app->error('Error: not logged in.');
        }
        $uid = $this->app->user->is_admin();
        if (defined('MW_API_CALL') and $uid == false) {
            exit('Error: not logged in as admin.' . __FILE__ . __LINE__);
        }
        if (is_array($id)) {
            extract($id);
        }
        $id = intval($id);
        if (isset($cf_id)) {
            $id = intval($cf_id);
        }

        if ($id == 0) {
            return false;
        }

        $custom_field_table = $this->tables['custom_fields'];
        $q = "DELETE FROM $custom_field_table WHERE id='$id'";
        $this->app->db->q($q);
        $this->app->cache->delete('custom_fields');
        return $id;
    }


    /*document_ready('test_document_ready_api');

    function test_document_ready_api($layout) {

    //   $layout = modify_html($layout, $selector = '.editor_wrapper', 'append', 'ivan');
    //$layout = modify_html2($layout, $selector = '<div class="editor_wrapper">', '');
    return $layout;
    }*/

    public function make_field($field_id = 0, $field_type = 'text', $settings = false)
    {

        $data = false;
        $form_data = array();
        if (is_array($field_id)) {

            if (!empty($field_id)) {
                $data = $field_id;
                return $this->make($field_id, false, 'y');
            }
        } else {
            if ($field_id != 0) {

                return $this->make($field_id);

                //
                // $this->app->error('no permission to get data');
                //  $form_data = $this->app->db->get_by_id('table_custom_fields', $id = $field_id, $is_this_field = false);
            }
        }
    }

    /**
     * make_field
     *
     * @desc make_field
     * @access      public
     * @category    forms
     * @author      Microweber
     * @link        http://microweber.com
     * @param string $field_type
     * @param string $field_id
     * @param array $settings
     */
    public function make($field_id = 0, $field_type = 'text', $settings = false)
    {

        if (is_array($field_id)) {
            if (!empty($field_id)) {
                $data = $field_id;
            }
        } else {
            if ($field_id != 0) {

                $data = $this->app->db->get_by_id('table_custom_fields', $id = $field_id, $is_this_field = false);
            }
        }
        if (isset($data['settings']) or (isset($_REQUEST['settings']) and trim($_REQUEST['settings']) == 'y')) {

            $settings = true;
        }

        if (isset($data['copy_from'])) {
            $copy_from = intval($data['copy_from']);
            if (is_admin() == true) {

                $table_custom_field = $this->tables['custom_fields'];
                $form_data = $this->app->db->get_by_id($table_custom_field, $id = $copy_from, $is_this_field = false);
                if (is_array($form_data)) {

                    $field_type = $form_data['custom_field_type'];
                    $data['id'] = 0;
                    if (isset($data['save_on_copy'])) {

                        $cp = $form_data;
                        $cp['id'] = 0;
                        $cp['copy_of_field'] = $copy_from;
                        if (isset($data['rel'])) {
                            $cp['rel'] = ($data['rel']);
                        }
                        if (isset($data['rel_id'])) {
                            $cp['rel_id'] = ($data['rel_id']);
                        }
                        $this->save($cp);
                        $data = $cp;
                    } else {
                        $data = $form_data;
                    }

                }

            }
        } else if (isset($data['field_id'])) {
            $data = $this->app->db->get_by_id('table_custom_fields', $id = $data['field_id'], $is_this_field = false);
        }

        if (isset($data['custom_field_type'])) {
            $field_type = $data['custom_field_type'];
        }

        if (!isset($data['custom_field_required'])) {
            $data['custom_field_required'] = 'n';
        }

        if (isset($data['type'])) {
            $field_type = $data['type'];
        }

        if (isset($data['field_type'])) {
            $field_type = $data['field_type'];
        }

        if (isset($data['field_values']) and !isset($data['custom_field_value'])) {
            $data['custom_field_values'] = $data['field_values'];
        }

        $data['custom_field_type'] = $field_type;

        // if (isset($data['custom_field_value']) and strtolower($data['custom_field_value']) == 'array') {
        if (isset($data['custom_field_values']) and is_string($data['custom_field_values'])) {

            $try = base64_decode($data['custom_field_values']);

            if ($try != false and strlen($try) > 5) {
                $data['custom_field_values'] = unserialize($try);
            }
        }
        //}
        if (isset($data['options']) and is_string($data['options'])) {
            $data['options'] = $this->app->format->base64_to_array($data['options']);
        }

        $data = $this->app->url->replace_site_url_back($data);


        $dir = MW_INCLUDES_DIR;
        $dir = $dir . DS . 'custom_fields' . DS;
        $field_type = str_replace('..', '', $field_type);
        $load_from_theme = false;
        if (defined("ACTIVE_TEMPLATE_DIR")) {
            $custom_fields_from_theme = ACTIVE_TEMPLATE_DIR . 'modules' . DS . 'custom_fields' . DS;
            if (is_dir($custom_fields_from_theme)) {
                if ($settings == true or isset($data['settings'])) {
                    $file = $custom_fields_from_theme . $field_type . '_settings.php';
                } else {
                    $file = $custom_fields_from_theme . $field_type . '.php';
                }
                if (is_file($file)) {
                    $load_from_theme = true;
                }
            }
        }

        if ($load_from_theme == false) {
            if ($settings == true or isset($data['settings'])) {
                $file = $dir . $field_type . '_settings.php';
            } else {
                $file = $dir . $field_type . '.php';
            }
        }
        if (!is_file($file)) {
            $field_type = 'text';
            if ($settings == true or isset($data['settings'])) {
                $file = $dir . $field_type . '_settings.php';
            } else {
                $file = $dir . $field_type . '.php';
            }
        }
        $file = normalize_path($file, false);

        if (is_file($file)) {

            $l = new \Microweber\View($file);

            //

            $l->assign('settings', $settings);
            if (isset($data['params'])) {
                $l->assign('params', $data['params']);
            } else {
                $l->assign('params', false);

            }
            //  $l->settings = $settings;

            if (isset($data) and !empty($data)) {
                $l->data = $data;
            } else {
                $l->data = array();
            }

            $l->assign('data', $data);



            $layout = $l->__toString();

            return $layout;
        }
    }

    /**
     * names_for_table
     *
     * @desc names_for_table
     * @access      public
     * @category    forms
     * @author      Microweber
     * @link        http://microweber.com
     * @param string $table
     */


    public function names_for_table($table)
    {
        $table = $this->app->db->escape_string($table);
        $table1 = $this->app->db->assoc_table_name($table);

        $table = $this->tables['custom_fields'];
        $q = false;
        $results = false;

        $q = "SELECT *, count(id) AS qty FROM $table WHERE   custom_field_type IS NOT NULL AND rel='{$table1}' AND custom_field_name!='' GROUP BY custom_field_name, custom_field_type ORDER BY qty DESC LIMIT 100";
        $crc = (crc32($q));

        $cache_id = __FUNCTION__ . '_' . $crc;

        $results = $this->app->db->query($q, $cache_id, 'custom_fields/global');

        if (is_array($results)) {
            return $results;
        }

    }
}