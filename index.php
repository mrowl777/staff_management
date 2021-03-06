<?php

include 'components/MainController.php';
include 'components/Auth.php';
include 'components/websun.php'; 
include 'ini.php'; 


$auth = new Auth();
$call = new MainController();


if( isset($_POST) ){
    switch( $_POST['action'] ){
        case 'login':
            $auth->_on_login();
        break;

        case 'logout':
            $auth->_on_kill_session();
        break;

        case 'register':
            $auth->_on_new_user();
        break;

        case 'manage_db':
            if( $auth->check_session() ){
                $call->_on_manage_db();
            }
            die();
        break;

        case 'delete_panel_usr':
            $call->_on_delete_panel_usr();
            die();
        break;

        case 'set_worker':
            $call->_on_set_worker();
            die();
        break;

        case 'remove_worker':
            $call->_on_remove_worker();
            die();
        break;

        case 'add_staff':
            $call->_on_add_staff();
            die();
        break;

        case 'toggle_rights_usr':
            $call->_on_toggle_rights_usr();
            die();
        break;

        case 'open_settings':
            if( $auth->check_session() ){
                $call->_on_open_settings();
            }
            die();
        break;

        case 'setup_param':
            $call->_on_setup_param();
            die();
        break;

        case 'rm_param':
            $call->_on_rm_param();
            die();
        break;

        case 'add_param':
            $call->_on_add_param();
            die();
        break;
        
        case 'set_newpass':
            $call->_on_update_pass();
            die();
        break;
        
    }
}


$call->_r_header();


if( $auth->check_session() ){
    $call->_r_main();
}


$call->_r_footer();




?>