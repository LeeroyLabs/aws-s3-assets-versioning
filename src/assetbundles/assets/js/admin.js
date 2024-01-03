function togglePopup() {
    let popup = document.getElementById("popup--volume");
    popup.classList.toggle("popup--hidden");
}
function toggleIMGPopup($dataAttribute) {
    let popup = document.getElementById($dataAttribute);
    popup.classList.toggle("popup--hidden");
}
