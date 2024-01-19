function togglePopup() {
    let popup = document.getElementById("popup--volume");
    popup.classList.toggle("popup--hidden");
}

function toggleIMGPopup($dataAttribute) {
    let popup = document.getElementById($dataAttribute);
    popup.classList.toggle("popup--hidden");
}

let currentUrl = window.location.href;

if (currentUrl.includes('assets/edit') && document.getElementById('asset__revisions').length > 0) {
    let versions = document.getElementById('asset__revisions');
    document.getElementsByClassName('revision-menu')[0].append(versions);
}
