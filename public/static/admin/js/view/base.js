var last_keyword = '';
$(document).ready(function () {
    $('#btn-change-password').click(function () {
        $('#modal-sm').modal({
            backdrop: true,
            keyboard: true,
            remote: change_password_url
        });
    });
    $('#btn-change-avatar').click(function () {
        $('#modal-md').modal({
            backdrop: true,
            keyboard: true,
            remote: change_avatar_url
        });
    });

    $('.input-group.date').datepicker({
        todayBtn: "linked",
        keyboardNavigation: false,
        forceParse: false,

        autoclose: true,
        format: "yyyy-mm-dd",
        language: "zh-CN",
    });

    $("#search-clear").click(function () {
        $("#search-clear").hide();
        $("#search_keyword").val('');
        $("#search_keyword").focus();
    });
    $("#search_keyword").keydown(function (e) {
        if ($("#search_keyword").val() != '') {
            $("#search-clear").show();
        }
        else {
            $("#search-clear").hide();
        }
        var key = e.which;
        if (key == 13) {
            search();
        }
    });
    $("#search-button").click(function () {
        search();
    });
    if (typeof(data_table) == "undefined") {
        //$("#search_keyword").hide();
    }

    function search() {
        data_table.draw();
        if (last_keyword != $("#search_keyword").val()) {
            last_keyword = $("#search_keyword").val();
        }
    }

    $('#modal-md').on('hidden.bs.modal', function () {
        KindEditor.remove('.kindeditor');
        $(this).find(".modal-content").html('');
        $(this).removeData();
    });
    $('#modal-sm').on('hidden.bs.modal', function () {
        KindEditor.remove('.kindeditor');
        $(this).find(".modal-content").html('');
        $(this).removeData();
    });
    $('#modal-lg').on('hidden.bs.modal', function () {
        KindEditor.remove('.kindeditor');
        $(this).find(".modal-content").html('');
        $(this).removeData();
    });
    var location_url = window.location.origin + window.location.pathname;
    $("#side-menu li a").each(function () {
        if ($(this).attr('href') != '' && $(this).attr('href') != '#') {
            if (location_url.toLowerCase().indexOf($(this).attr('href')) >= 0) {
                $(this).parents("li").addClass('active');
                $(this).parents(".nav-second-level").addClass('in');
                return false;
            }
        }
    });
    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 2000
    };

    $('img').on('error', function () {
        $(this).attr('src', "/public/static/admin/img/empty.png");
    });
    $('img[src=""]').each(function () {
        $(this).attr('src', "/public/static/admin/img/empty.png");
    });

    $('#data-table tbody').on('dblclick', 'tr td:not(:last-child)', function () {
        var data = data_table.row(this).data();
        var btn_edit = $(this).parent().find(".btn-edit");
        if (btn_edit) {
            var target = $(btn_edit).data('target');
            var url = $(btn_edit).attr('href');
            $(target).modal({remote: url})
        }
    });
});