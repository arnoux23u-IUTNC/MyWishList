'use strict';

const upload_btn = document.getElementById('upload_btn');
const form = document.getElementById('upload_form');
const fileselector = document.getElementById('avatar-upload');

upload_btn.onclick = () => {
    fileselector.click();
}

fileselector.addEventListener("change", function() {
    const files = fileselector.files[0];
    if (files)
        form.submit();
})