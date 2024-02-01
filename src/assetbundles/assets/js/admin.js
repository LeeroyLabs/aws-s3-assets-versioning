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
if ((currentUrl.includes('assets/edit') || currentUrl.includes('admin/version')) && document.getElementById('asset__revisions')) {
    // Append the html to the Craft switch sites dropdown menu
    let versions = document.getElementById('asset__revisions');
    let menuClass = 'menu';
    let key = 1;
    if (currentUrl.includes('assets/edit')) {
        menuClass = 'revision-menu';
        key = 0;
    }
    document.getElementsByClassName(menuClass)[key].append(versions);
}
