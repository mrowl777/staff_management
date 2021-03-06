<?php

include 'db_handler.php';

class MainController extends db_handler {

    function _r_header(){
        $tpl = '../base_page/templates/header.tpl';
        $blocked_menu = true;
        $hash = $this->get_usr_hash( $_COOKIE["user_name"] );

        if ( isset($_COOKIE["staff_management"]) && $_COOKIE["hash"] == $hash ){
            $blocked_menu = false;
        }

        $DATA['menu'] = _MAIN_MENU_;
        $DATA['menu_blocked'] = $blocked_menu;
        $html = websun_parse_template_path($DATA, $tpl); 
        echo $html; 
    }
    
    function _r_main(){

        ini_set('pcre.backtrack_limit', 1024*1024);

        $DATA = [];

        $is_admin = $this->_on_get_rights_usr();
        $title = 'супервайзер';

        if(!$is_admin){
            $obj = $this->get_user_data( $_COOKIE["user_name"] );
            $title = $obj['first_name'] . ' ' . $obj['surname'];
        }

        $DATA['my_name'] = $title;

        $tpl = '../base_page/templates/index.tpl';

        $html = websun_parse_template_path($DATA, $tpl); 

        echo $html; 
    }

    function _r_footer(){
        $tpl = '../base_page/templates/footer.tpl';
        $DATA = [];
        $html = websun_parse_template_path($DATA, $tpl); 
        echo $html; 
    }

    function _on_manage_db( $username = '', $pass = '' ){
        ini_set('pcre.backtrack_limit', 1024*1024);
        header("Content-Type: text/html; charset=utf-8");
        ini_set("default_charset", 'utf-8');
        $DATA = [];

        $DATA['have_access'] = $this->_on_get_rights_usr();

        $users_table = $this->get_users_table();
        $times_table = $this->get_times_table();
    
        $tpl = '../base_page/templates/database.tpl';
        $users = [];
        $time_var = [];
        while ($row = $times_table->fetch_assoc()) {
            $time_var[$row['type']]=$row['time'];
        }
        while ($row = $users_table->fetch_assoc()) {
            $users[] = array(
                'id' => $row["id"], 
                'first_name' => $row["first_name"], 
                'last_name' => $row["last_name"],
                'surname' => $row["surname"],
                'is_active' => $row["is_active"],
                'work_time_type' => $time_var[$row["work_time_type"]],
            );
        }

        if( !empty($username) && $username != '' ){
            $DATA['new_user'][] = [
                'login' => $username,
                'password' => $pass
            ];
        }

        $DATA['staffs'] = $users;
        $DATA['times'] = $time_var;

        $html = websun_parse_template_path($DATA, $tpl); 

        echo $html;
    }

    function _on_delete_panel_usr(){
        $this-> remove_panel_user( $_POST['username'] );
        $this->_on_manage_db();
    }

    function _on_set_worker(){
        $this->set_active( $_POST['username'] );
        $this->_on_manage_db();
    }

    function _on_remove_worker(){
        $this->delete_user( $_POST['username'] );
        $this->_on_manage_db();
    }

    function _on_add_staff(){
        $f_name = $_POST['first_name'];
        $l_name = $_POST['last_name'];
        $s_name = $_POST['surname'];
        $time = $_POST['time'];
        $username = $this->str2url($l_name);
        $pass = $this->generate_password();
        list ($setted_up_u_name, $setted_up_password) = $this-> add_staff( $f_name, $l_name, $s_name, $time, $username, $pass );
        $this->_on_manage_db($setted_up_u_name, $setted_up_password);   
    }

    function _on_get_rights_usr(){
        return $this->get_user_rights( $_COOKIE["user_name"] );
    }

    function _on_update_pass(){
        $pass = md5(md5($_POST['pass']));
        $this->update_password( $_COOKIE["user_name"], $pass );
        $this->_on_open_settings();
    }

    function build_normal_dates( $dates_arr ){
        $n_dates = [];

        foreach( $dates_arr as $each ){
            $n_dates[] =  date( 'd-m-Y', $each );
        }

        return implode(":", $n_dates);
    }

    function dates_builder( $_date ){
        $dates = explode( ":", $_date );
        $cur_time = time();
        
        foreach( $dates as $k => $date ){
            if( $date <= $cur_time ){
                unset($dates[$k]);
            }
        }

        /** ненужные проверки */
        //шутка, нужные, но я не знаю, зачем
        if( count($dates) == 0 ){
            $dates[] = $cur_time;
        }

        if( count($dates) == 1 ){
            $dates[] = (end($dates) + 24 * 60 * 60);
        }

        while(count($dates) < 30){
            $comparing_els = array_slice($dates, -2);
            if($comparing_els[1] - $comparing_els[0] == 24*60*60){
                $dates[] = ( $comparing_els[1]  + 72*60*60 );
            }else{
                $dates[] = ( $comparing_els[1]  + 24*60*60 );
            }
        }

        $str = implode(":", $dates);
        $n_dates = $this->build_normal_dates( $dates );

        return array($str, $n_dates);
    }


    function build_timetable(){
        $staffs_list = [];
        $staffs = $this->get_staffs();
        $tt = $this->get_timetable();

        if( $tt ){
            while ($row = $tt->fetch_assoc()) {
                if( $this->check_active( $row['uid'] ) ){
                    list($dates, $n_dates) = $this->dates_builder($row["dates"]);
                    $this->update_timetable( $row["uid"], $dates, $n_dates );
                }else{
                    $this->reset_nline_timetable( $row['uid'] );
                }
            }

            return;
        }

        while ($row = $staffs->fetch_assoc()) {
            if( $row["is_active"] == '1' ){
                $staffs_list[] = array(
                    'id' => $row["id"], 
                    'start_date' => strtotime( $row["start_date"] )
                );
            }
        }

        foreach( $staffs_list as $k => $staff ){
            $start_date = $staff['start_date'];
            $dates = [];
            for( $i = 0; $i < 30; $i++ ){
                $comparing_els = array_slice($dates, -2);
                if($comparing_els[1] - $comparing_els[0] == 24*60*60){
                    $dates[] = ( $start_date  + 72*60*60 );
                    $start_date = $start_date + 72*60*60;
                }else{
                    $dates[] = ( $start_date  + 24*60*60 );
                    $start_date = $start_date + 24*60*60;
                }
            }
            $normal_dates = $this->build_normal_dates( $dates );
            $result_str = implode(":", $dates);
            $this->put_timeline($staff['id'], $result_str, $normal_dates );
        }
    }

    function make_staffs_array(){
        $staff = $this->get_users_table();
        $staff_list = [];
        while ($row = $staff->fetch_assoc()) {
            $title = $row['last_name'] . " " . $row['first_name'] . " " . $row['surname'];
            $work_time = $row['work_time_type'];
            $staff_list[$row['id']]=[
                'name' => $title,
                'time' => $work_time,
                'login' => $row['login']
            ];
        }
        return $staff_list;
    }
    

    function _on_open_settings(){
        ini_set('pcre.backtrack_limit', 1024*1024);

        $this->build_timetable();

        $DATA = [];
        $timetable = [];
        $time_var = [];
        $dates_list = [];
        
        $graphics = $this->get_times_table();
        $staff_list = $this->make_staffs_array();
        $cur_timestamp = time() + (24 * 60 * 60);
        
        for($i=0;$i<30;$i++) {
            $_date = date('d.m.Y', $cur_timestamp);
            $timetable[] = $this->build_staff( $_date, $staff_list );
            $cur_timestamp = $cur_timestamp + (24 * 60 * 60);
        }

        while ($row = $graphics->fetch_assoc()) {
            $is_req = '';
            if(!$this->_on_get_rights_usr()){
                $type = $this->get_user_time_type( $_COOKIE['user_name'] );
                $is_req = $row['type'] == $type ? '' : 'not_required';
            }
            $time_var[$row['type']]=[
                'time' => $row['time'],
                'req' => $is_req
            ];
        }
        
        $DATA['times'] = $time_var;
        $DATA['is_admin'] = $this->_on_get_rights_usr();
        $DATA['settings'] = $timetable;
        $tpl = '../base_page/templates/settings.tpl';

        $html = websun_parse_template_path($DATA, $tpl); 

        echo $html;
    }

    function build_staff( $_date, $staff_list ){
        $is_admin = $this->_on_get_rights_usr();

        $uids = $this->get_staff_by_date( $_date );
        $uids = explode(",",$uids);
        $left_part = '';
        $right_part = '';
        $l_counter = 0;
        $r_counter = 0;
        $is_req = false;
        foreach($uids as $id){
            if(isset($staff_list[$id])){
                $time = $staff_list[$id]['time'];
                $is_mi = '';
                if( !$is_admin ){
                    $is_mi = $staff_list[$id]['login'] == $_COOKIE['user_name'] ? '' : 'not_required';
                    if( $staff_list[$id]['login'] == $_COOKIE['user_name'] ){
                        $is_req = true;
                    }
                }
                if( $time == '1' ){
                    $left_part .= "<div class = ".$is_mi." >" . $staff_list[$id]['name'] . "</div>";
                    $l_counter++;
                    if( $staff_list[$id]['login'] == $_COOKIE['user_name'] ){
                        $hidden_right = 'not_required';
                    }
                }else{
                    $right_part .= "<div class = ".$is_mi." >" . $staff_list[$id]['name'] . "</div>"; 
                    $r_counter++;
                    if( $staff_list[$id]['login'] == $_COOKIE['user_name'] ){
                        $hidden_left = 'not_required';
                    }
                }
            }
        }

        list($l_color, $r_color) = $this->generate_bg( $l_counter, $r_counter );
        
        $data = [
            'left_side' => $left_part,
            'left_side_color' => $l_color,
            'right_side' => $right_part,
            'right_side_color' => $r_color,
            'l_date' => $_date,
            'is_req_date' => $is_req,
            'hide_left' => $hidden_left,
            'hide_right' => $hidden_right
        ];
        return $data;
    }

    function generate_bg( $l_counter, $r_counter ){
        $l_color = '';
        $r_color = '';
        if( $l_counter < 5 ){
            $l_color = 'status_red';
        }
        if( $l_counter > 10 ){
            $l_color = 'status_green';
        }
        if( $r_counter < 5 ){
            $r_color = 'status_red';
        }
        if( $r_counter > 10 ){
            $r_color = 'status_green';
        }
        return array( $l_color, $r_color );
    }

    function _on_setup_param(){
        $this->set_param( $_POST['key'], $_POST['value'] );
        $this->_on_open_settings();
    }

    function _on_rm_param(){
        $this->remove_setting_param( $_POST['key'] );
        $this->_on_open_settings();
    }

    function _on_add_param(){
        $this->add_setting_param( $_POST['key'], $_POST['value'] );
        $this->_on_open_settings();
    }


    /**generating username & password */
    function rus2translit($string) {
        $converter = array(
            'а' => 'a',   'б' => 'b',   'в' => 'v',
            'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
            'и' => 'i',   'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm',   'н' => 'n',
            'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u',
            'ф' => 'f',   'х' => 'h',   'ц' => 'c',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
            'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
            
            'А' => 'A',   'Б' => 'B',   'В' => 'V',
            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
            'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N',
            'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U',
            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
            'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }
    function str2url($str) {
        // переводим в транслит
        $str = $this->rus2translit($str);
        // в нижний регистр
        $str = strtolower($str);
        // заменям все ненужное нам на "-"
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
        // удаляем начальные и конечные '-'
        $str = trim($str, "-");
        return $str;
    }

    function generate_password(){
        return rand(11111111, 99999999);
    }

}

?>