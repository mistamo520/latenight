function check_status(result) {
    if (typeof result == 'string') {
        result = JSON.parse(result);
    }
    if (result.status == 1) {
        toastr["success"](result.message);
        return true;
    } else {
        toastr["error"](result.message);
        return false;
    }

}

function delete_item(id) {
    swal({
        title: "",
        text: "确定要删除?",
        showCancelButton: true,
        confirmButtonColor: "#18a689",
        confirmButtonText: "确定",
        cancelButtonColor: "#e6e6e6",
        cancelButtonText: "取消"
    }, function () {
        $.ajax({
            type: 'POST',
            url: "delete",
            dataType: 'json',
            async: false,
            data: {
                id: id,
            },
            success: function (data) {
                if (check_status(data)) {
                    data_table.draw();
                }
            }
        });
    });
}

function load_list(query, columns, callback) {
    data_table = $('#data-table').DataTable({
        pageLength: page_length,
        serverSide: true,
        ajax: {
            url: 'get_list',
            type: 'POST',
            data: query
        },
        "drawCallback": callback,
        "columns": columns,
        "ordering": false,
        "searching": false,
        "dom": "rt<'row'<'col-sm-12'<'col-sm-6'i><'col-sm-6'p>>><'clear'>",
    });
}

function get_table_action(id, modal_class, addition_action) {
    modal_class = "modal-" + modal_class;
    return '<a class="btn-edit" data-toggle="modal" data-target="#' + modal_class + '" title="修改" href="_item_maintain/id/' + id + '.html"><i class="fa fa-pencil"></i></a>'
        + (addition_action ? addition_action : '')
        + '<a title="删除" href="javascript:void(0);" onclick="delete_item(' + id + ');"><i class="fa fa-trash"></i></a>';
}

function get_table_edit_action(id, modal_class) {
    modal_class = "modal-" + modal_class;
    return '<a class="btn-edit" data-toggle="modal" data-target="#' + modal_class + '" title="修改" href="_item_maintain/id/' + id + '.html"><i class="fa fa-pencil"></i></a>';
}

function get_table_delete_action(id) {
    return '<a title="删除" href="javascript:void(0);" onclick="delete_item(' + id + ');"><i class="fa fa-trash"></i></a>';
}

function get_base64(img) {
    function getBase64Image(img, width, height) {
        var canvas = document.createElement("canvas");
        canvas.width = width ? width : img.width;
        canvas.height = height ? height : img.height;
        var ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        var dataURL = canvas.toDataURL();
        return dataURL;
    }

    var image = new Image();
    image.src = img;
    var deferred = $.Deferred();
    if (img) {
        image.onload = function () {
            deferred.resolve(getBase64Image(image));
        }
        return deferred.promise();
    }
}
