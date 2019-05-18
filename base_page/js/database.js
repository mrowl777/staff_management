init_events();

function init_events() {
    $('.delete_panel_user').on('click', delete_panel_usr);
    $('.set_worker').on('click', set_worker);
    $('.add_staff').on('click', show_form);
    $('.load').on('click', add_staff);
    $('.change_pass').on('click', change_pass);
    $('.save_pass_btn').on('click', save_pass_btn);
}

function delete_panel_usr(e) {
    var username = $(this).parent().attr('id');
    if(confirm("Вы уверены?")){
        $.post(
            "index.php",
            {
                action: "delete_panel_usr",
                username: username
            },
            on_base_answer
        );
    }
    
}

function change_pass(e) {
    var btn = $(this);
    var input = '<input name="new_pass" class="key" placeholder="Введите новый пароль"> </input>';
    var save_btn = '<i name="save_pass_btn" class="icon-ok icon-large"></i>';
    btn.replaceWith( input + save_btn);
    init_events();
}

function save_pass_btn(e) {
    var _pass = $('input[name=new_pass]');
    if(confirm("Вы  записали пароль?")){
        $.post(
            "index.php",
            {
                action: "set_newpass",
                pass: _pass
            },
            on_pass_changed
        );
    }
}

function on_pass_changed(){
    if(confirm("Пароль изменен")){}
    $('.content_box').replaceWith(data);
}

function set_worker(e) {
    var username = $(this).parent().attr('id');
    if(confirm("Вы уверены?")){
        $.post(
            "index.php",
            {
                action: "set_worker",
                username: username
            },
            on_base_answer
        );
    }
    
}

function add_staff(e) {
    var name = $('.name').val();
    var _surname = $('.surname').val();
    var _last_name = $('.last_name').val();
    var _time = $( "#usr_time option:selected" ).val();
        $.post(
            "index.php",
            {
                action: "add_staff",
                first_name: name,
                last_name: _last_name,
                surname: _surname,
                time: _time,
            },
            on_base_answer
        );
    
}

function on_base_answer(data) {
    $('.content_box').replaceWith(data);
}

function show_form(){
    $(this).hide();
    $('.new_staff').show();
}
