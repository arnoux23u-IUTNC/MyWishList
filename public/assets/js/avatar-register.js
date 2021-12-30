'use strict';

const inputFile = document.getElementById('file_img');
const form = document.getElementById('form');

let assertFile = () => {
    let file = inputFile.files[0];
    if (file) {
        if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) {
            alert('File type is not supported');
            return false;
        }
        if (file.size > 15000000) {
            alert(`File size is too big (${parseFloat(file.size / 1000000).toFixed(2)}Mo) [MAX: 15Mo]`);
            return false;
        }
        return true;
    }
    return true;
}