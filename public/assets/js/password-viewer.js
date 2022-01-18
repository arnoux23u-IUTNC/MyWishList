'use strict';

$(document).ready(function () {
    $('.pwdicon').each(function () {
        $(this).click(function (event) {
            pwd($(this).attr('data-associated'), event);
        });
    });
});

let pwd = (id, element) => {
    let icon = element.target;
    const field = $(`#${id}`);
    const type = field.attr('type');
    if (type === 'password') {
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        field.attr('type', 'text');
    } else {
        icon.classList.add('fa-eye');
        icon.classList.remove('fa-eye-slash');
        field.attr('type', 'password');
    }
}