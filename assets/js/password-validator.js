let small = document.getElementById("small")
let capital = document.getElementById("capital")
let number = document.getElementById("number")
let special = document.getElementById("special")
let length = document.getElementById("length")
let input = document.getElementById("input-new-password")
let inputC = document.getElementById("input-new-password-c")
let block = document.getElementById("message")


let display = () => {
    if (input.value.length > 0)
        block.style.display = "block"
    else
        block.style.display = "none"
}

if (inputC !== null) {
    let matchPwd = () => {
        if (input.value == inputC.value) {
            inputC.classList.remove("invalid");
            return true;
        } else {
            inputC.classList.add("invalid");
            return false;
        }
    }
    inputC.onkeyup = matchPwd;
    inputC.onfocus = matchPwd;

    inputC.onblur = () => {
        if (inputC.value.length === 0) inputC.classList.remove("invalid");
    }
}

input.onfocus = () => { block.style.display = "block" }
input.onblur = display

input.onkeyup = () => {
    if (input.value.match(/[a-z]/g)) {
        small.classList.remove("invalid");
        small.classList.add("valid");
    } else {
        small.classList.remove("valid");
        small.classList.add("invalid");
    }
    if (input.value.match(/[A-Z]/g)) {
        capital.classList.remove("invalid");
        capital.classList.add("valid");
    } else {
        capital.classList.remove("valid");
        capital.classList.add("invalid");
    }
    if (input.value.match(/[0-9]/g)) {
        number.classList.remove("invalid");
        number.classList.add("valid");
    } else {
        number.classList.remove("valid");
        number.classList.add("invalid");
    }
    if (input.value.match(/[~!@#$%^&*()\-_=+[\]{};:,<>\/?|]/g)) {
        special.classList.remove("invalid");
        special.classList.add("valid");
    } else {
        special.classList.remove("valid");
        special.classList.add("invalid");
    }
    if (input.value.length >= 14) {
        length.classList.remove("invalid");
        length.classList.add("valid");
    } else {
        length.classList.remove("valid");
        length.classList.add("invalid");
    }
}