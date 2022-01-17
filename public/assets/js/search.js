const searchBox = document.querySelector(".search-box");
const searchBtn = document.querySelector(".search-icon");
const cancelBtn = document.querySelector(".cancel-icon");
const searchInput = document.querySelector("input");
const enable = document.querySelector("#enable");
const exp = document.querySelector("#expiration");
searchBtn.onclick = () => {
    enable.classList.remove("flex-row");
    searchBox.classList.add("active");
    searchBtn.classList.add("active");
    searchInput.classList.add("active");
    searchInput.focus();
    if (cancelBtn.classList.contains("active") && searchInput.value != "") {
        window.location.href = "?search=" + searchInput.value + "&exp=" + exp.value;
    }
    cancelBtn.classList.add("active");
}
cancelBtn.onclick = () => {
    searchBox.classList.remove("active");
    enable.classList.add("flex-row");
    searchBtn.classList.remove("active");
    searchInput.classList.remove("active");
    cancelBtn.classList.remove("active");
    searchInput.value = "";
    window.location.href = "?exp=" + exp.value;
}

exp.onchange = () => {
    window.location.href = "?search=" + searchInput.value + "&exp=" + exp.value;
}