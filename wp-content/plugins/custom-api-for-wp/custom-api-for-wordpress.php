<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.miniorange.com
 * @since             1.0.0
 * @package           Custom_Api_For_Wordpress
 *
 * @wordpress-plugin
 * Plugin Name:       Custom API for WP
 * Plugin URI:        custom-api-for-wp
 * Description:       This plugin helps in creating custom api end points for extracting customized data from database. The plugin can also be extended to integrate external APIs in WordPress.
 * Version:           2.3.0
 * Author:            miniOrange
 * Author URI:        https://www.miniorange.com
 * License:           MIT/Expat
 * License URI:       https://docs.miniorange.com/mit-license
 */

require 'custom_api_nav.php';
require 'custom_api_wp_customer.php';
require 'feedback-form.php';
// require 'custom-api-handler.php';

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('CUSTOM_API_FOR_WORDPRESS_VERSION', '2.3.0');

update_option('mo_custom_api_wp_version', CUSTOM_API_FOR_WORDPRESS_VERSION);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-custom-api-for-wordpress-activator.php
 */
function activate_custom_api_for_wordpress()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-custom-api-for-wordpress-activator.php';
    Custom_Api_For_Wordpress_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-custom-api-for-wordpress-deactivator.php
 */
function deactivate_custom_api_for_wordpress()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-custom-api-for-wordpress-deactivator.php';
    Custom_Api_For_Wordpress_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_custom_api_for_wordpress');
register_deactivation_hook(__FILE__, 'deactivate_custom_api_for_wordpress');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-custom-api-for-wordpress.php';

/**
 * Begins execution of the plugin.
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_custom_api_for_wordpress()
{

    $plugin = new Custom_Api_For_Wordpress();
    $plugin->run();
}
run_custom_api_for_wordpress();

add_action('rest_api_init', function () {

    $GetVar = get_option('CUSTOM_API_WP_LIST');

    foreach ($GetVar as $ApiName => $value) {

        $namespace = 'mo/v1';
        $route = '';
        if ($value['SelectedCondtion'] == 'no condition') {
            $route = $ApiName;
        } else {

            $route = $ApiName . '/(?P<id>[A-Za-z0-9]+)';
        }

        register_rest_route($namespace, $route, array(
            'methods' => 'GET',
            'callback' => 'custom_api_wp_get_result',
            'permission_callback' => '__return_true',
            'args' => $value,
        ));
    }
});

add_action('rest_api_init', function () {

    $mo_sql_var = get_option('custom_api_wp_sql');

    if (isset($mo_sql_var) && $mo_sql_var != NULL) {
        foreach ($mo_sql_var as $sqlkey => $sqlvalue) {

            $namespace = 'mo/v1';
            $route = $sqlkey;

            register_rest_route($namespace, $route, array(
                'methods'  => $sqlvalue['method'] == 'Delete' ? 'DELETE' : $sqlvalue['method'],
                'callback' => 'custom_api_wp_get_sql_result',
                'permission_callback' => '__return_true',
                'args' => $sqlvalue
            ));
        }
    }

    $GetVar = get_option('CUSTOM_API_WP_LIST');

    if (isset($GetVar) && $GetVar != NULL) {
        foreach ($GetVar as $ApiName => $value) {

            $namespace = 'mo/v1';
            $route = '';

            $param_order = array();
            $order_var = 0;
            if ($value['MethodName'] == 'GET') {


                if ($value['SelectedCondtion'] == 'no condition') {
                    $route = $ApiName;
                } else {
                    $route = $ApiName;
                    $param_order[$value["SelectedParameter"]] = $value['ConditionColumn'];
                    $order_var++;
                    if (array_key_exists('param_if_op', $value))
                        $parameter = $value['param_if_op'];
                    else
                        $parameter = "";
                    if (array_key_exists('column_if_op', $value))
                        $op_column = $value['column_if_op'];
                    else
                        $op_column = "";
                    if (array_key_exists('param_if_op', $value)) {
                        for ($y = 0; $y < $value['condition_count']; $y++) {
                            if (!empty($op_column[$y])) {
                                $param_order[$parameter[$y]] = $op_column[$y];
                                $order_var++;
                            }
                        }
                    }

                    for ($y = 1; $y <= $order_var; $y++) {
                        $route = '/' . $route . '/(?P<' . $param_order[$y] . '>\S+)';
                    }
                }
            }

            if ($value['MethodName'] == 'POST') {

                $route = $ApiName;
            }

            if ($value['MethodName'] == 'PUT') {

                $route = $ApiName;
            }
            if ($value['MethodName'] == 'Delete') {
                $route = $ApiName;
            }

            register_rest_route($namespace, $route, array(
                'methods'  => $value['MethodName'] == 'Delete' ? 'DELETE' : $value['MethodName'],
                'callback' => 'custom_api_wp_get_result',
                'permission_callback' => '__return_true',
                'args' => $value
            ));
        }
    }
});

function custom_api_wp_get_sql_result($request)
{

    global $wpdb;

    $restricted = array();
    if (get_option("mo_custom_api_restricted_method")) {
        $restricted = get_option("mo_custom_api_restricted_method");
    }

    $need = $request->get_attributes();

    $sql_query = $need['args']["sql_query"];

    if ($need['args']['method'] == 'GET') {

        if (!empty($restricted)) {
            if ($restricted["GET"] == "GET") {

                $headers = mo_api_auth_getallheaders();
                if (custom_api_wp_valid_request($headers) == true)
                    echo '';
                else
                    echo '';
            }
        }

        $error_response = array(
            'error' =>  'invalid_format',
            'error_description' => 'Required arguments are missing or does not passed in the correct format.'
        );


        if ($need['args']['query_params'] !== 'on' && $need['args']['query_params'] !== '1') {
            $result = $wpdb->get_results($sql_query);
            return $result;
        }

        $pattern = "/{{[A-Z]*[a-z]*_[A-Z]*[a-z]*[0-9]*}}/";
        $matches = preg_match_all($pattern, $sql_query, $reg_array);

        if ($matches !== 0 && (sizeof($_GET) == sizeof($reg_array[0]))) {
            $i = 0;
            for ($i = 0; $i < sizeof($_GET); $i++) {
                $mo_regex = substr($reg_array[0][$i], 2);
                $mo_regex = substr($mo_regex, 0, -2);

                if (isset($_GET[$mo_regex]) && $_GET[$mo_regex] !== NULL) {
                    $sql_query = str_replace($reg_array[0][$i], $_GET[$mo_regex], $sql_query);
                } else {
                    wp_send_json($error_response, 400);
                }
            }
            $result = $wpdb->get_results($sql_query);
            return $result;
        }

        wp_send_json($error_response, 400);
    } else if ($need['args']['method'] == 'POST') {

        if (!empty($restricted)) {
            if ($restricted["POST"] == "POST") {

                $headers = mo_api_auth_getallheaders();
                if (custom_api_wp_valid_request($headers) == true)
                    echo '';
                else
                    echo '';
            }
        }

        $error_response = array(
            'error' =>  'invalid_format',
            'error_description' => 'Required body parameters are missing or does not passed in the correct format.'
        );

        if ($need['args']['query_params'] != 'on' && $need['args']['query_params'] != '1') {
            $result = $wpdb->query($sql_query);
            return $result;
        }

        $pattern = "/{{[A-Z]*[a-z]*_[A-Z]*[a-z]*[0-9]*}}/";

        $matches = preg_match_all($pattern, $sql_query, $reg_array);

        if ($matches !== 0 && (sizeof($_POST) == sizeof($reg_array[0]))) {
            $i = 0;
            for ($i = 0; $i < sizeof($_POST); $i++) {
                $mo_regex = substr($reg_array[0][$i], 2);
                $mo_regex = substr($mo_regex, 0, -2);
                if (isset($_POST[$mo_regex]) && $_POST[$mo_regex] !== NULL) {
                    $sql_query = str_replace($reg_array[0][$i], $_POST[$mo_regex], $sql_query);
                } else {
                    wp_send_json($error_response, 400);
                }
            }
            $result = $wpdb->query($sql_query);
            return $result;
        }
        wp_send_json($error_response, 400);
    } else {

        if (!empty($restricted)) {
            if ($restricted["PUT"] == "PUT" || $restricted["Delete"] == "Delete") {

                $headers = mo_api_auth_getallheaders();
                if (custom_api_wp_valid_request($headers) == true)
                    echo '';
                else
                    echo '';
            }
        }

        $error_response = array(
            'error' =>  'invalid_format',
            'error_description' => 'Required body parameters are missing or does not passed in the correct format.'
        );

        if ($need['args']['query_params'] != 'on' && $need['args']['query_params'] != '1') {
            $result = $wpdb->query($sql_query);
            return $result;
        }

        $pattern = "/{{[A-Z]*[a-z]*_[A-Z]*[a-z]*[0-9]*}}/";
        $get_params = $request->get_params();
        $matches = preg_match_all($pattern, $sql_query, $reg_array);

        if ($matches !== 0 && (sizeof($get_params) == sizeof($reg_array[0]))) {
            $i = 0;
            for ($i = 0; $i < sizeof($get_params); $i++) {
                $mo_regex = substr($reg_array[0][$i], 2);
                $mo_regex = substr($mo_regex, 0, -2);

                if (isset($get_params[$mo_regex]) && $get_params[$mo_regex] !== NULL) {
                    $sql_query = str_replace($reg_array[0][$i], $get_params[$mo_regex], $sql_query);
                } else {
                    wp_send_json($error_response, 400);
                }
            }

            $result = $wpdb->query($sql_query);
            return $result;
        }
        wp_send_json($error_response, 400);
    }
}

function custom_api_wp_get_result($request)
{

    global $wpdb;

    $need = $request->get_attributes();

    $GetQuery1 = $need['args']['query'];
    $SelectedCondtion = $need['args']['SelectedCondtion'];
    if (($SelectedCondtion == 'no condition')) {

        $myrows = $wpdb->get_results("{$GetQuery1}");
        return $myrows;
    } else {
        $Spliting = explode($SelectedCondtion, $GetQuery1);
        $MainQuery = $Spliting[0];
        $type = gettype($request['id']);
        if ($type == "string" && 'Like' == $SelectedCondtion) {
            $param = '"%' . $request['id'] . '%"';
        } elseif ($type == "string") {
            $param = '"' . $request['id'] . '"';
        }

        if ($type == "integer") {
            $param = $request['id'];
        }

        if ('&amp;gt;' == $SelectedCondtion) {
            $SelectedCondtion = '>';
        }

        if ('less than' == $SelectedCondtion) {
            $SelectedCondtion = '<';
        }

        $SelectedCondtion = $SelectedCondtion . ' ';

        if (isset($param))
            $myrows = $wpdb->get_results("{$MainQuery} {$SelectedCondtion} {$param}");

        if (isset($myrows))
            return $myrows;
    }
}

class Custom_Api_Wp
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'custom_api_wp_menu'));
        add_action('admin_footer', array($this, 'custom_api_client_feedback_request'));
    }

    public function custom_api_wp_menu()
    {
        $slug = 'custom_api_wp_settings';
        add_menu_page('MO API Settings ' . __('Configure Custom API Settings', 'custom_api_wp_settings'), 'Custom API plugin', 'administrator', $slug, array(
            $this,
            'custom_api_wp_widget_options',
        ), plugin_dir_url(__FILE__) . 'images/miniorange-logo.png');
    }

    public function custom_api_wp_widget_options()
    {
        global $wpdb;
        wp_enqueue_script('custom-api-wp-phone', plugins_url('/js/custom-api-wp-phone.js', __FILE__), array(), null);
        wp_enqueue_script('custom-api-wp', plugins_url('/js/custom-api-wp.js', __FILE__), array(), null);
        wp_enqueue_script('custom-wp-popper-min', plugins_url('/js/popper.min.js', __FILE__), array(), null);

        wp_enqueue_script('custom-wp-bootstrap-min', plugins_url('/js/bootstrap-min.js', __FILE__), array(), null);
        wp_enqueue_script('custom-wp-bootstrap-multiselect', plugins_url('/js/bootstrap-multiselect.js', __FILE__), array(), null);
        wp_enqueue_script('custom-wp-bootstrap', plugins_url('/js/bootstrap.js', __FILE__), array(), null);

        wp_enqueue_style('custom-wp-bootstrap-min', plugins_url('/css/bootstrap-min.css', __FILE__), array(), null);

        wp_enqueue_style('custom-wp-bootstrap-multiselect', plugins_url('/css/bootstrap-multiselect.css', __FILE__), array(), null);
        wp_enqueue_style('custom-api-wp-css', plugins_url('/css/custom-api-wp-css.css', __FILE__), array(), null);
        wp_enqueue_style('custom-api-wp-license-css', plugins_url('/css/custom-api-wp-license-css.css', __FILE__), array(), null);
        wp_enqueue_style('custom-api-wp-phone', plugins_url('/css/phone.css', __FILE__), array(), null);
        update_option('host_name', 'https://login.xecurify.com');
        custom_api_wp_main_menu();
    }

    public function custom_api_client_feedback_request()
    {
        custom_api_client_display_feedback_form();
    }
}
new Custom_Api_Wp;

function custom_api_wp_sanitise($var)
{
    $var = trim($var);
    $var = stripslashes($var);
    $var = strip_tags($var);
    $var = htmlentities($var);
    $var = htmlspecialchars($var);

    return $var;
}

add_filter('ExternalApiHook', 'mo_custom_external_api', 10, 4);

function mo_custom_external_api($value1, $value2, $value3, $value4 = false)
{

    $ExternalApiPostField = $value2;
    $ExternalApiArray = get_option("custom_api_save_ExternalApiConfiguration");

    if (isset($ExternalApiArray[$value1]["ExternalEndpoint"])) {
        $params = '';
        $ExternalHeaders = array();
        if (isset($value3) && $value3 != NULL) {
            foreach ($value3 as $key => $value) {
                $hstr = $key . ':' . $value;
                array_push($ExternalHeaders, $hstr);
            }
        } else {
            $ExternalHeaders = $ExternalApiArray[$value1]["ExternalHeaders"];
        }

        $RequestUrl = htmlspecialchars_decode($ExternalApiArray[$value1]["ExternalEndpoint"]);

        if ($value4) {
            $RequestUrl = $value4;
        }

        $RequestUrl = str_replace('&amp;', '&', $RequestUrl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $RequestUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $ExternalApiArray[$value1]["ExternalApiRequestType"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ExternalApiPostField);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $ExternalHeaders);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36');

        $login_response = curl_exec($ch);

        if ($login_response === false) {
            return 'Curl error: ' . curl_error($ch);
        }
        curl_close($ch);

        if (empty($ExternalApiArray[$value1]["ExternalApiResponseDataKey"]) || "custom_api_wp_getall" == $ExternalApiArray[$value1]["ExternalApiResponseDataKey"][0]) {
            return $login_response;
        } else {
            return json_encode(testattrmappingconfig('', json_decode($login_response), true, $ExternalApiArray[$value1]["ExternalApiResponseDataKey"]));
        }
    } else {
        wp_die("Invalid API Name passed in external api connection hook :" . $value1);
    }
}

function sample_admin_notice__success()
{
?>
    <div class="notice notice-success is-dismissible" style="width: 30%">
        <p>Endpoint with custom SQL created successfuly.</p>
    </div>
<?php
}

add_action('admin_init', 'custom_api_wp_functions');

function custom_api_wp_functions()
{
    if (current_user_can('administrator')) {
        if (isset($_POST["SubmitForm1"])) {
            if (isset($_POST['SubmitUser1']) && wp_verify_nonce($_POST['SubmitUser1'], 'CheckNonce1')) {
                $data1 = array(
                    "ApiName" => custom_api_wp_sanitise($_POST['api_name_initial']),
                    "MethodName" => custom_api_wp_sanitise($_POST['method_name_initial']),
                    "TableName" => custom_api_wp_sanitise($_POST['table_name_initial']),
                );
                update_option('mo_custom_api_form1', $data1);
            }
        }
    }

    if (current_user_can('administrator')) {
        if (isset($_POST["SendResult"])) {
            if (isset($_POST['SubmitUser']) && wp_verify_nonce($_POST['SubmitUser'], 'CheckNonce')) {

                $data = array(
                    "status" => "yes",
                    "ApiName" => custom_api_wp_sanitise($_POST["ApiName"]),
                    "TableName" => isset($_POST['select-table']) ? custom_api_wp_sanitise1($_POST['select-table']) : '',
                    "MethodName" => isset($_POST['MethodName']) ? custom_api_wp_sanitise1($_POST['MethodName']) : '',
                    "SelectedColumn" => custom_api_wp_sanitise($_POST["Selectedcolumn11"]),
                    "ConditionColumn" => custom_api_wp_sanitise($_POST["OnColumn"]),
                    "SelectedCondtion" => custom_api_wp_sanitise($_POST["ColumnCondition"]),
                    "SelectedParameter" => custom_api_wp_sanitise($_POST["ColumnParam"]),
                    "query" => custom_api_wp_sanitise($_POST["QueryVal"]),
                );

                update_option('mo_custom_api_form', $data);
            }
        }
    }

    if (isset($_POST['option']) && sanitize_text_field(wp_unslash($_POST['option'])) == "custom_api_wp_sql" && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['custom_api_wp_sql_field'])), 'custom_api_wp_sql')) {
        if (current_user_can('administrator')) {

            $api_name = isset($_POST['SQLApiName']) ? sanitize_text_field(wp_unslash($_POST['SQLApiName'])) : '';

            $current_form = array(
                "method" => isset($_POST['MethodName']) ? sanitize_text_field(wp_unslash($_POST['MethodName'])) : '',
                "sql_query" => isset($_POST['customsql']) ? sanitize_text_field(wp_unslash($_POST['customsql'])) : '',
                "query_params" => isset($_POST['QueryParameter']) ? sanitize_text_field(wp_unslash($_POST['QueryParameter'])) : 0
            );

            $current_apis = get_option('custom_api_wp_sql');

            $temp_array = array();

            if (isset($current_apis) && $current_apis != NULL) {
                foreach ($current_apis as $key => $value) {
                    $temp_array[$key] = $value;
                }
            }
            $temp_array[$api_name] = $current_form;

            update_option('custom_api_wp_sql', $temp_array);

            add_action('admin_notices', 'sample_admin_notice__success');

            $site_url = site_url() . '/wp-admin/admin.php?page=custom_api_wp_settings&action=savedcustomsql';

            wp_redirect($site_url);
            exit();
        }
    }

    function custom_api_wp_submit_contact_us($email, $phone, $query)
    {
        global $current_user;
        $query = '[Custom API WP - ' . CUSTOM_API_FOR_WORDPRESS_VERSION . ' ] ' . $query;
        $fields = array(
            'firstName' => isset($current_user->user_firstname) ? $current_user->user_firstname : '',
            'lastName' => isset($current_user->user_lastname) ? $current_user->user_lastname : '',
            'company' => $_SERVER['SERVER_NAME'],
            'email' => $email,
            'ccEmail' => 'apisupport@xecurify.com',
            'phone' => $phone,
            'query' => $query,
        );
        $field_string = json_encode($fields);
        update_option('custom_api_wp_host_name', 'https://login.xecurify.com');
        $url = get_option('custom_api_wp_host_name') . '/moas/rest/customer/contact-us';

        $headers = array('Content-Type' => 'application/json', 'charset' => 'UTF - 8', 'Authorization' => 'Basic');
        $args = array(
            'method' => 'POST',
            'body' => $field_string,
            'timeout' => '15',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,

        );

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
            exit();
        }
        return wp_remote_retrieve_body($response);
    }

    if (current_user_can('administrator')) {
        if (isset($_POST["ExternalApiConnection"])) {
            if (isset($_POST['SubmitUser']) && wp_verify_nonce($_POST['SubmitUser'], 'CheckNonce')) {

                $ExternalApiName = isset($_POST['ExternalApiName']) ? custom_api_wp_sanitise($_POST['ExternalApiName']) : '';
                $HeaderCount = isset($_POST['ExternalHeaderCount']) ? custom_api_wp_sanitise($_POST['ExternalHeaderCount']) : '';
                $ResponseBodyCount = isset($_POST['ExternalResponseBodyCount']) ? custom_api_wp_sanitise($_POST['ExternalResponseBodyCount']) : '';
                $ExternalHeaders = array();
                $ExternalEndpoint = isset($_POST['ExternalApi']) ? custom_api_wp_sanitise(htmlspecialchars_decode($_POST['ExternalApi'])) : '';

                $ExternalApiRequestType = isset($_POST['MethodName']) ? custom_api_wp_sanitise($_POST['MethodName']) : '';
                $ExternalApiBodyRequestType = isset($_POST['RequestBodyType']) ? custom_api_wp_sanitise($_POST['RequestBodyType']) : '';
                $ExternalApiRequestBody = array();
                $ExternalApiRequestBodyJson = isset($_POST['RequestBodyJson']) ? sanitize_text_field(wp_unslash($_POST['RequestBodyJson'])) : '';
                $ExternalApiPostField = '';

                if (isset($_POST['ExternalHeaderKey']) && isset($_POST['ExternalHeaderValue']) && custom_api_wp_sanitise($_POST['ExternalHeaderKey']) != null && custom_api_wp_sanitise($_POST['ExternalHeaderValue']) != null) {
                    array_push($ExternalHeaders, custom_api_wp_sanitise($_POST['ExternalHeaderKey']) . ':' . custom_api_wp_sanitise($_POST['ExternalHeaderValue']));

                    if ($HeaderCount) {
                        for ($x = 1; $x <= $HeaderCount; $x++) {
                            $HeaderKey = 'ExternalHeaderKey' . $x;
                            $HeaderValue = 'ExternalHeaderValue' . $x;
                            if (isset($_POST[$HeaderKey]))
                                array_push($ExternalHeaders, custom_api_wp_sanitise($_POST[$HeaderKey]) . ':' . custom_api_wp_sanitise($_POST[$HeaderValue]));
                        }
                    }
                }
                if ($ExternalApiBodyRequestType == 'x-www-form-urlencode') {
                    if (isset($_POST['RequestBodyKey']) && isset($_POST['RequestBodyValue']) && custom_api_wp_sanitise($_POST['RequestBodyKey']) != null && custom_api_wp_sanitise($_POST['RequestBodyValue']) != null) {

                        $ExternalApiRequestBody[custom_api_wp_sanitise($_POST['RequestBodyKey'])] = htmlspecialchars_decode(custom_api_wp_sanitise($_POST['RequestBodyValue']));
                        if ($ResponseBodyCount) {
                            for ($x = 1; $x <= $ResponseBodyCount; $x++) {
                                $RequestBodyKey = 'RequestBodyKey' . $x;
                                $RequestBodyValue = 'RequestBodyValue' . $x;
                                if (isset($_POST[$RequestBodyKey]))
                                    $ExternalApiRequestBody[custom_api_wp_sanitise($_POST[$RequestBodyKey])] = custom_api_wp_sanitise($_POST[$RequestBodyValue]);
                            }
                        }
                    }

                    $ExternalApiPostField = http_build_query($ExternalApiRequestBody);
                } else {


                    $ExternalApiPostField = $ExternalApiRequestBodyJson != null ? $ExternalApiRequestBodyJson : '';
                }

                $ExternalApiConfiguration = array(
                    "ExternalApiName" => $ExternalApiName,
                    "ExternalApiRequestType" => $ExternalApiRequestType,
                    "ExternalApiBodyRequestType" => $ExternalApiBodyRequestType,
                    "ExternalApiPostField" => $ExternalApiPostField,
                    "ExternalEndpoint" => $ExternalEndpoint,
                    "ExternalHeaders" => $ExternalHeaders,
                );

                update_option("custom_api_test_ExternalApiConfiguration", $ExternalApiConfiguration);
                echo '<script>  window.open("' . site_url() . '/wp-admin/?customapiexternal=testexecute","Test External API Execution" , "width=600, height=600"); window.location.reload;</script>';
            }
        }
    }

    if (current_user_can('administrator')) {
        if (isset($_POST["ExternalApiConnectionSave"])) {

            if (isset($_POST['SubmitUser']) && wp_verify_nonce($_POST['SubmitUser'], 'CheckNonce')) {
                $ExternalApiName = isset($_POST['ExternalApiName']) ? custom_api_wp_sanitise($_POST['ExternalApiName']) : '';
                $HeaderCount = isset($_POST['ExternalHeaderCount']) ? custom_api_wp_sanitise($_POST['ExternalHeaderCount']) : '';
                $ExternalEndpoint = isset($_POST['ExternalApi']) ? custom_api_wp_sanitise(htmlspecialchars_decode($_POST['ExternalApi'])) : '';
                $ExternalApiRequestType = isset($_POST['MethodName']) ? custom_api_wp_sanitise($_POST['MethodName']) : '';
                $ExternalApiBodyRequestType = isset($_POST['RequestBodyType']) ? custom_api_wp_sanitise($_POST['RequestBodyType']) : '';
                $ExternalApiResponseDataKey = explode(",", $_POST['selected_column_all']);
                $ExternalHeaders = array();

                $ResponseBodyCount = isset($_POST['ExternalResponseBodyCount']) ? custom_api_wp_sanitise($_POST['ExternalResponseBodyCount']) : '';
                $ExternalEndpoint = isset($_POST['ExternalApi']) ? custom_api_wp_sanitise(htmlspecialchars_decode($_POST['ExternalApi'])) : '';
                $ExternalApiRequestBody = array();
                $ExternalApiRequestBodyJson = isset($_POST['RequestBodyJson']) ? sanitize_text_field(wp_unslash($_POST['RequestBodyJson'])) : '';
                $ExternalApiPostField = '';

                if (isset($_POST['ExternalHeaderKey']) && isset($_POST['ExternalHeaderValue']) && custom_api_wp_sanitise($_POST['ExternalHeaderKey']) != null && custom_api_wp_sanitise($_POST['ExternalHeaderValue']) != null) {
                    array_push($ExternalHeaders, custom_api_wp_sanitise($_POST['ExternalHeaderKey']) . ':' . custom_api_wp_sanitise($_POST['ExternalHeaderValue']));

                    if ($HeaderCount) {
                        for ($x = 1; $x <= $HeaderCount; $x++) {
                            $HeaderKey = 'ExternalHeaderKey' . $x;
                            $HeaderValue = 'ExternalHeaderValue' . $x;
                            if (isset($_POST[$HeaderKey])) {
                                array_push($ExternalHeaders, custom_api_wp_sanitise($_POST[$HeaderKey]) . ':' . custom_api_wp_sanitise($_POST[$HeaderValue]));
                            }
                        }
                    }
                }

                if ($ExternalApiBodyRequestType == 'x-www-form-urlencode') {
                    if (isset($_POST['RequestBodyKey']) && isset($_POST['RequestBodyValue']) && custom_api_wp_sanitise($_POST['RequestBodyKey']) != null && custom_api_wp_sanitise($_POST['RequestBodyValue']) != null) {
                        $ExternalApiRequestBody[custom_api_wp_sanitise($_POST['RequestBodyKey'])] = htmlspecialchars_decode(custom_api_wp_sanitise($_POST['RequestBodyValue']));
                        if ($ResponseBodyCount) {
                            for ($x = 1; $x <= $ResponseBodyCount; $x++) {
                                $RequestBodyKey = 'RequestBodyKey' . $x;
                                $RequestBodyValue = 'RequestBodyValue' . $x;
                                if (isset($_POST[$RequestBodyKey])) {
                                    $ExternalApiRequestBody[custom_api_wp_sanitise($_POST[$RequestBodyKey])] = custom_api_wp_sanitise($_POST[$RequestBodyValue]);
                                }
                            }
                        }
                    }

                    $ExternalApiPostField = http_build_query($ExternalApiRequestBody);
                } else {
                    $ExternalApiPostField = $ExternalApiRequestBodyJson != null ? $ExternalApiRequestBodyJson : '';
                }

                $ExternalApiConfiguration = array(
                    "ExternalApiName" => $ExternalApiName,
                    "ExternalApiRequestType" => $ExternalApiRequestType,
                    "ExternalApiBodyRequestType" => $ExternalApiBodyRequestType,
                    "ExternalApiResponseDataKey" => $ExternalApiResponseDataKey,
                    "ExternalEndpoint" => $ExternalEndpoint,
                    "ExternalHeaders" => $ExternalHeaders,
                    "ExternalApiPostField" => $ExternalApiPostField,
                );

                $ExistingExternalApiConfiguration = get_option("custom_api_save_ExternalApiConfiguration");
                $ExistingExternalApiConfiguration[$ExternalApiName] = $ExternalApiConfiguration;
                update_option("custom_api_save_ExternalApiConfiguration", $ExistingExternalApiConfiguration);
                header('Location: ' . '?page=custom_api_wp_settings&action=savedexternalapi');
                die();
            }
        }
    }

    if (isset($_REQUEST["customapiexternal"]) && "testexecute" == sanitize_text_field($_REQUEST["customapiexternal"])) {
        if (current_user_can('administrator')) {

            $ExternalApiConfiguration = get_option("custom_api_test_ExternalApiConfiguration");
            $body_params = array();
            $body_params = $ExternalApiConfiguration["ExternalApiPostField"];
            $url = htmlspecialchars_decode($ExternalApiConfiguration["ExternalEndpoint"]);
            $url = str_replace('&amp;', '&', $url);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $ExternalApiConfiguration["ExternalApiRequestType"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body_params);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $ExternalApiConfiguration["ExternalHeaders"]);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36');

            $login_response = curl_exec($ch);

            if ($login_response === false) {
                echo 'Curl error: ' . curl_error($ch);
            }

            curl_close($ch);

            render_test_config_output($login_response, false);
            exit;
        }
    }

    if (isset($_POST['option'])) {
        if (custom_api_wp_sanitise($_POST['option']) == "custom_api_wp_contact_us_query_option") {

            if (wp_verify_nonce($_POST['mo_custom_api_submit_contact_us_field'], 'mo_custom_api_submit_contact_us')) {
                $email = isset($_POST['custom_api_wp_contact_us_email']) ? custom_api_wp_sanitise($_POST['custom_api_wp_contact_us_email']) : '';
                $phone = isset($_POST['custom_api_wp_contact_us_phone']) ? custom_api_wp_sanitise($_POST['custom_api_wp_contact_us_phone']) : '';
                $query = isset($_POST['custom_api_wp_contact_us_query']) ? custom_api_wp_sanitise($_POST['custom_api_wp_contact_us_query']) : '';

                if (custom_api_wp_empty_or_null($email) || custom_api_wp_empty_or_null($query)) {
                    update_option('custom_api_wp_message', 'Please fill up Email and Query fields to submit your query.');
                    custom_api_wp_show_error_message();
                } else {
                    $submited = custom_api_wp_submit_contact_us($email, $phone, $query);
                    if ($submited != 'Query submitted.') {
                        update_option('custom_api_wp_message', 'Your query could not be submitted. Please try again.');
                        custom_api_wp_show_error_message();
                    } else {
                        update_option('custom_api_wp_message', 'Thanks for getting in touch! We shall get back to you shortly.');
                        custom_api_wp_show_success_message();
                    }
                }
            }
        }
    }

    if (isset($_POST['option'])) {
        if (custom_api_wp_sanitise($_POST['option']) == "custom_api_authentication_verify_customer") {
            if (wp_verify_nonce($_POST['mo_cusotm_api_verify_customer_field'], 'mo_cusotm_api_verify_customer')) {
                $email = '';
                $password = '';
                if (custom_api_authentication_check_empty_or_null($_POST['email']) || custom_api_authentication_check_empty_or_null($_POST['password'])) {
                    update_option('custom_api_wp_message', 'All the fields are required. Please enter valid entries.');
                    custom_api_wp_show_error_message();
                    return;
                } else {
                    $email = sanitize_email($_POST['email']);
                    $password = stripslashes(custom_api_wp_sanitise($_POST['password']));
                }

                update_option('custom_api_authentication_admin_email', $email);
                update_option('password', $password);

                $content = custom_api_auth_get_customer_key();
                $customerKey = json_decode($content, true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    update_option('custom_api_authentication_admin_customer_key', $customerKey['id']);
                    update_option('custom_api_authentication_admin_api_key', $customerKey['apiKey']);
                    update_option('custom_api_authentication_customer_token', $customerKey['token']);
                    if (isset($customerKey['phone'])) {
                        update_option('custom_api_authentication_admin_phone', $customerKey['phone']);
                    }

                    delete_option('password');
                    update_option('custom_api_wp_message', 'Customer retrieved successfully');
                    delete_option('custom_api_authentication_verify_customer');
                    custom_api_wp_show_success_message();
                } else {
                    update_option('custom_api_wp_message', 'Invalid username or password. Please try again.');
                    custom_api_wp_show_error_message();
                }
            }
        }
    }


    if (isset($_POST['option'])) {
        if (custom_api_wp_sanitise($_POST['option']) == "custom_api_authentication_register_customer") {
            if (wp_verify_nonce($_POST['mo_custom_api_register_customer_field'], 'mo_custom_api_register_customer')) {
                $email = '';
                $phone = '';
                $password = '';
                $confirmPassword = '';
                $fname = '';
                $lname = '';
                $company = '';
                if (custom_api_authentication_check_empty_or_null($_POST['email']) || custom_api_authentication_check_empty_or_null($_POST['password']) || custom_api_authentication_check_empty_or_null($_POST['confirmPassword'])) {
                    update_option('custom_api_wp_message', 'All the fields are required. Please enter valid entries.');
                    custom_api_wp_show_error_message();
                    return;
                } elseif (strlen(sanitize_text_field($_POST['password'])) < 8 || strlen(sanitize_text_field($_POST['confirmPassword'])) < 8) {
                    update_option('custom_api_wp_message', 'Choose a password with minimum length 8.');
                    custom_api_wp_show_error_message();
                    return;
                } else {
                    $email = sanitize_email($_POST['email']);
                    $phone = stripslashes(sanitize_text_field($_POST['phone']));
                    $password = stripslashes(sanitize_text_field($_POST['password']));
                    $confirmPassword = stripslashes(sanitize_text_field($_POST['confirmPassword']));
                    $fname = stripslashes(sanitize_text_field($_POST['fname']));
                    $lname = stripslashes(sanitize_text_field($_POST['lname']));
                    $company = stripslashes(sanitize_text_field($_POST['company']));
                }

                update_option('custom_api_authentication_admin_email', $email);
                update_option('custom_api_authentication_admin_phone', $phone);
                update_option('custom_api_authentication_admin_fname', $fname);
                update_option('custom_api_authentication_admin_lname', $lname);
                update_option('custom_api_authentication_admin_company', $company);

                if (strcmp($password, $confirmPassword) == 0) {
                    update_option('password', $password);

                    $email = get_option('custom_api_authentication_admin_email');
                    $content = json_decode(custom_api_check_customer(), true);

                    if (strcasecmp($content['status'], 'CUSTOMER_NOT_FOUND') == 0) {
                        $response = json_decode(custom_api_create_customer(), true);
                        if (strcasecmp($response['status'], 'SUCCESS') != 0) {
                            update_option('custom_api_wp_message', 'Failed to create customer. Try again.');
                            custom_api_wp_show_error_message();
                        } else {
                            update_option('custom_api_wp_message', 'You are successfully registered with miniOrange');
                            custom_api_wp_show_success_message();
                        }
                    } elseif (strcasecmp($content['status'], 'SUCCESS') == 0) {
                        update_option('custom_api_wp_message', 'Account already exist. Please Login.');
                        custom_api_wp_show_error_message();
                    }
                } else {
                    update_option('custom_api_wp_message', 'Passwords do not match.');
                    custom_api_wp_show_error_message();
                }
            }
        }
    }

    if (isset($_POST['option2'])) {
        if (sanitize_text_field($_POST['option2']) == "custom_api_authentication_goto_login1") {
            if (wp_verify_nonce($_POST['mo_custom_api_goto_login_form1_field'], 'mo_custom_api_goto_login_form1')) {
                update_option('custom_api_authentication_verify_customer', 'yes');
                delete_option('custom_api_authentication_new_customer');
            }
        }
    }

    if (isset($_POST['option2'])) {
        if (sanitize_text_field($_POST['option2']) == "custom_api_authentication_goto_register") {
            if (wp_verify_nonce($_POST['mo_custom_api_goto_register_form_field'], 'mo_custom_api_goto_register_form')) {
                update_option('custom_api_authentication_new_customer', 'yes');
                delete_option('custom_api_authentication_verify_customer');
            }
        }
    }

    if (isset($_POST['option'])) {
        if (sanitize_text_field($_POST['option']) == "change_miniorange") {
            if (wp_verify_nonce($_POST['mo_custom_api_goto_login_form_field'], 'mo_custom_api_goto_login_form')) {
                update_option('custom_api_authentication_verify_customer', 'yes');
            }
        }
    }

    if (current_user_can('manage_options')) {

        if (isset($_POST['option']) and sanitize_text_field($_POST['option']) == "mo_custom_api_trial_request_form" && isset($_REQUEST['mo_custom_api_trial_request_field']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['mo_custom_api_trial_request_field'])), 'mo_custom_api_trial_request')) {
            $email = sanitize_email($_POST['mo_custom_api_trial_email']);
            $trial_plan = sanitize_text_field($_POST['mo_custom_api_trial_plan']);
            $query = sanitize_text_field($_POST['mo_custom_api_trial_usecase']);
            $subject = "WP Custom API Trial Request" . " " . "-" . " " . $email;
            $response = mo_custom_api_send_trial_alert($email, $trial_plan, $query, $subject);

            if ($response == false) {
                update_option('custom_api_wp_message', 'Your query could not be submitted. Please try again.');
                custom_api_wp_show_error_message();
            } else {
                update_option('custom_api_wp_message', 'Thanks for getting in touch! We shall get back to you shortly.');
                custom_api_wp_show_success_message();
            }
        }

        if (isset($_POST['option']) and sanitize_text_field($_POST['option']) == 'mo_oauth_client_skip_feedback') {
            deactivate_plugins(__FILE__);
            update_option('custom_api_wp_message', 'Plugin deactivated successfully');
            custom_api_wp_show_success_message();
        } else if (isset($_POST['custom_api_client_feedback']) and sanitize_text_field($_POST['custom_api_client_feedback']) == 'true') {
            $user = wp_get_current_user();
            $message = 'Plugin Deactivated:';
            $deactivate_reason = array_key_exists('deactivate_reason_radio', $_POST) ? sanitize_text_field($_POST['deactivate_reason_radio']) : false;
            $deactivate_reason_message = array_key_exists('query_feedback', $_POST) ? sanitize_text_field($_POST['query_feedback']) : false;
            if ($deactivate_reason) {
                $message .= $deactivate_reason;
                if (isset($deactivate_reason_message)) {
                    $message .= ':' . $deactivate_reason_message;
                }
                // $email = get_option( "custom_api_authentication_admin_email" );
                // if ( $email == '' ) {
                $email = $user->user_email;
                // }
                // $phone = get_option( 'custom_api_authentication_admin_phone' );
                $phone = '';
                //only reason

                $submited = json_decode(custom_api_send_email_alert($email, $phone, $message), true);
                deactivate_plugins(__FILE__);
                update_option('custom_api_wp_message', 'Thank you for the feedback.');
                custom_api_wp_show_success_message();
            } else {
                update_option('custom_api_wp_message', 'Please Select one of the reasons ,if your reason is not mentioned please select Other Reasons');
                custom_api_wp_show_error_message();
            }
        }
    }
}


function render_test_config_output($resource_owner, $group = false)
{
    if (is_array(json_decode($resource_owner, true)) && (json_last_error() == JSON_ERROR_NONE)) {
        echo '<div style="font-family:Calibri;padding:0 3%;">';
        echo '<style>table{border-collapse:collapse;}th {background-color: #eee; text-align: center; padding: 8px; border-width:1px; border-style:solid; border-color:#212121;}tr:nth-child(odd) {background-color: #f2f2f2;} td{padding:8px;border-width:1px; border-style:solid; border-color:#212121;}</style>';
        echo '<h2>';
        echo ($group) ? 'Group Info' : 'Test Configuration';
        echo '</h2><table><tr><th>Attribute Name</th><th>Attribute Value</th></tr>';
        testattrmappingconfig('', json_decode($resource_owner));
        echo '</table>';
        if (!$group) {
            echo '<div style="padding: 10px;"></div><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="opener.location.reload();self.close();"></div>';
        }
    } else {
        echo '<div style="font-family:Calibri;padding:0 3%;">';
        echo $resource_owner;
        echo '<div style="padding: 10px;"></div><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="opener.location.reload();self.close();"></div>';
        update_option("ExternalApiResponseKey", "false");
    }
}

$ApiResponseKey = array();

function testattrmappingconfig($nestedprefix, $resource_owner_details, $real_environment = false, $required_keys = null)
{
    global $ApiResponseKey;

    if (!$real_environment) {
        foreach ($resource_owner_details as $key => $resource) {

            if (is_array($resource) || is_object($resource)) {
                if (!empty($nestedprefix)) {
                    $nestedprefix .= '.';
                }
                testattrmappingconfig($nestedprefix . $key, $resource);
                $nestedprefix = rtrim($nestedprefix, ".");
            } else {
                $completekey = "";
                echo '<tr><td>';
                if (!empty($nestedprefix)) {
                    echo $nestedprefix . '.'; // phpcs:ignore
                    $completekey = $nestedprefix . '.';
                }
                echo $key . '</td><td>' . $resource . '</td></tr>'; // phpcs:ignore
                $completekey = $completekey . $key;

                array_push($ApiResponseKey, $completekey);
            }
        }

        // exit;
        update_option("ExternalApiResponseKey", $ApiResponseKey);
    } else {
        foreach ($resource_owner_details as $key => $resource) {

            if (is_array($resource) || is_object($resource)) {
                if (!empty($nestedprefix)) {
                    $nestedprefix .= '.';
                }
                testattrmappingconfig($nestedprefix . $key, $resource, true, $required_keys);
                $nestedprefix = rtrim($nestedprefix, ".");
            } else {
                $completekey = "";

                if (!empty($nestedprefix)) {

                    $completekey = $nestedprefix . '.';
                }

                $completekey = $completekey . $key;
                if (in_array($completekey, $required_keys))
                    $ApiResponseKey[$completekey] = $resource;
            }
        }

        return $ApiResponseKey;
    }
}
