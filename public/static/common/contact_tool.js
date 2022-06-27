$(document).ready(function () {

alert(window.location.host);
    $.ajax({
        type: 'POST',
        url: "http://www.ideasinsoft.com/admin/test/test",
        data: {},
        success: function (data) {
            alert(data);
        },
        error: function () {
        }
    });
});