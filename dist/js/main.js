window.addEventListener('DOMContentLoaded', (event) => {
const content = document.querySelector(".page-container").innerHTML;
const cssURL = document.querySelector("#gplugin_style-css").getAttribute("href");
const popupWin = window.open('', '_blank', 'width=1100,height=600');
popupWin.document.open();
popupWin.document.write(`
<html> 
<head>
    <link rel="stylesheet" id="gplugin_style-css" href="${cssURL}" media="all">
</head>
<body onload="window.print()">
    ${content}
</body>
</html>`);
popupWin.document.close(); 
});
