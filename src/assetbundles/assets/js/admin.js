// function togglePopup() {
//     let popup = document.getElementById("popup--volume");
//     popup.classList.toggle("popup--hidden");
// }
//
// function toggleIMGPopup($dataAttribute) {
//     let popup = document.getElementById($dataAttribute);
//     popup.classList.toggle("popup--hidden");
// }

// Get current url for validation
let currentUrl = window.location.href;

// Validating if we are in an asset and if there are versions available
if (currentUrl.includes('assets/edit') && document.getElementById('asset__revisions')) {
    // Append the html to the Craft switch sites dropdown menu
    let versions = document.getElementById('asset__revisions');
    document.getElementsByClassName('revision-menu')[0].append(versions);
}
