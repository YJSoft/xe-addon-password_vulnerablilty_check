<?php

// *****************************
// * 비밀번호 취약점 체크 애드온
// * 제작자: Waterticket(admin@hoto.dev)
// * 문의: https://shop.hoto.dev
// *****************************


if(!defined('__XE__')) exit();

if($called_position == 'before_display_content' && Context::getResponseMethod() == 'HTML' && (stripos(Context::get('act'), 'signup') !== false || (stripos(Context::get('act'), 'dispMemberSignUpForm') !== false))){
    $member_config = MemberModel::getMemberConfig();
    Context::loadFile(array('./addons/password_vulnerablilty_check/js/4.0.0.crypto-js.min.js', 'body', '', null), true);
    $js_source = "<script>
    var password_strength='".$member_config->password_strength."';
    var password_input=$('input[name=password]');
    var pass_parent=$('input[name=password]').parent();
    var text_line=$('p', pass_parent);

    var original_text = text_line.text();

    password_input.focusout(function(e) {
        var password_enc = CryptoJS.SHA1(e.target.value).toString();
        if(e.target.value == '')
        {
            text_line.text('비밀번호를 입력해주세요. '+original_text);
            return;
        }

        var is_password_ok = false;
        switch(password_strength)
        {
            case 'low':
                is_password_ok = (e.target.value.length >= 4);
                break;
            
            case 'normal':
                var reg_pwd = /^.*(?=.{6,20})(?=.*[0-9])(?=.*[a-zA-Z]).*$/;
                is_password_ok = (e.target.value.length >= 6 && reg_pwd.test(e.target.value));
                break;
            
            case 'high':
                var pw = e.target.value;
                var pattern1 = /[0-9]/;
                var pattern2 = /[a-zA-Z]/;
                var pattern3 = /[~!@#$%^&*()_+|<>?:{}]/;
                is_password_ok = (pw.length >= 8 && pattern1.test(pw) && pattern2.test(pw) && pattern3.test(pw));
                break;
        }

        if(!is_password_ok)
        {
            text_line.text('비밀번호를 확인해주세요. '+original_text);
            return;
        }

        text_line.text('검사중..');
        $.ajax({
            url: 'https://api.pwnedpasswords.com/range/'+password_enc.substring(0, 5),
            method: 'GET',
            timeout: 10000,
            dataType: 'text',
            error: function(xmlhttprequest, textstatus, message) {
                if(textstatus===\"timeout\") {
                    alert('비밀번호 체크에 실패하였습니다. 잠시 후 다시시도 해주세요.');
                }
            }
        })
        .done(function(text) {
            var result = text.match(new RegExp(password_enc.substring(5, password_enc.length) + ':([0-9])','i'));
            if(result == null) {
                text_line.html('&nbsp;<span style=\"color:green;\">이 비밀번호는 안전합니다. ('+e.target.value.length+'자)</span>');
            } else {
                text_line.html('&nbsp;<span style=\"color:red;\">이 비밀번호는 위험합니다! 유출 횟수: '+result[1]+'회</span>');
            }
        })
    });
    </script>";

    Context::addHtmlFooter($js_source);
}
