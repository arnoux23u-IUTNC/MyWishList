'use strict';

const upload_btn = document.getElementById('upload_btn');
const form = document.getElementById('upload_form');
const fileselector = document.getElementById('avatar-upload');
let file;

upload_btn.onclick = () => {
    fileselector.click();
}

fileselector.addEventListener("change", function() {
    file = fileselector.files[0];
    if (file)
        assertFile();
})

let assertFile = () => {
    if (file) {
        if (file.size > 15000000) {
            alert(`File size is too big (${parseFloat(file.size/1000000).toFixed(2)}Mo) [MAX: 15Mo]`);
            return false;
        }
        form.submit();
    }
    return false;
}