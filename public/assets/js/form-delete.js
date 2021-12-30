'use strict';

let checkForm = () => {
    let formData = new FormData(document.querySelector("form"));
    switch (formData.get("type")) {
        case "link":
            const link = document.getElementById("url_img").value.toLowerCase();
            if (link.match(/^((https?:\/{2})?(\w[\w\-\/+]+).(jpe?g|png))?$/)) {
                return true;
            } else {
                alert("Link must match [http(s)://]nomimage.(jpe?g|png)");
                document.getElementById("url_img").focus();
            }
            break;
        case "upload":
            const name = $("#file_img")[0].files[0] ? $("#file_img")[0].files[0].name.toLowerCase() : "NOLINK";
            if (name.match(/^(\w[\w\-\/+]+).(jpe?g|png)$/)) {
                return true;
            } else {
                alert("Link must match [nomimage.(jpe?g|png)]");
                $("#file_img").click();
            }
            break;
    }
    return false;
}

let hide = () => {
    $('#url_img').removeClass('invisible');
    $('#delete').removeClass('invisible');
    $('#delete_img').removeClass('invisible');
    $('#file_img').addClass('invisible');
}

$('input[type=radio][name=type]').on('change', function () {
    switch ($(this).val()) {
        case 'link':
            hide();
            break;
        case 'upload':
            $('#url_img').addClass('invisible');
            $('#delete').addClass('invisible');
            $('#delete_img').addClass('invisible');
            $('#file_img').click();
            break;
    }
});

$(document).ready(function () {
    $('#link').prop('checked', true);
    hide();
});

$('#delete').click(function () {
    $('#url_img').val('');
});