'use strict';

let checkForm = () => {
    let formData = new FormData(document.querySelector("form"));
    switch (formData.get("type")) {
        case "link":
            const link = document.getElementById("url_img").value.toLowerCase();
            if (link.match(/^((https?:\/{2})?(\w[\w\-\/\.]+).(jpe?g|png))?$/)) {
                return true;
            } else {
                alert("Lien doit matcher [http(s)://]nomimage.(jpe?g|png)");
                document.getElementById("url_img").focus();
            }
            break;
        case "upload":
            const name = $("#file_img")[0].files[0] ? $("#file_img")[0].files[0].name.toLowerCase() : "";
            if (name.match(/^(\w[\w\-\/\.]+).(jpe?g|png)$/)) {
                return true;
            } else {
                alert("Lien doit matcher [nomimage.(jpe?g|png)]");
                document.getElementById("file_img").focus();
            }
            break;
    };
    return false;
}

$('input[type=radio][name=type]').on('change', function() {
    switch ($(this).val()) {
        case 'link':
            $('#url_img').removeClass('invisible');
            $('#file_img').addClass('invisible');
            break;
        case 'upload':
            $('#url_img').addClass('invisible');
            $('#file_img').removeClass('invisible');
            break;
    }
});

$(document).ready(function() {
    switch ($('input[type=radio][name=type]').val()) {
        case 'link':
            $('#url_img').removeClass('invisible');
            $('#file_img').addClass('invisible');
            break;
        case 'upload':
            $('#url_img').addClass('invisible');
            $('#file_img').removeClass('invisible');
            break;
    }
});