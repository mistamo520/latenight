(function(){
    var oLanguage={
        "oAria": {
            "sSortAscending": ": 升序排列",
            "sSortDescending": ": 降序排列"
        },
        "oPaginate": {
            "sFirst": "首页",
            "sLast": "末頁",
            "sNext": "下頁",
            "sPrevious": "上頁"
        },
        "sEmptyTable": "没有找到相關記錄",
        "sInfo": "第 _START_ 到 _END_ 條記錄，共 _TOTAL_ 條",
        "sInfoEmpty": "第 0 到 0 條記錄，共 0 條",
        "sInfoFiltered": "(从 _MAX_ 條記錄中檢索)",
        "sInfoPostFix": "",
        "sDecimal": "",
        "sThousands": ",",
        "sLengthMenu": "每頁顯示條數: _MENU_",
        "sLoadingRecords": "正在載入...",
        "sProcessing": "正在載入...",
        "sSearch": "搜索:",
        "sSearchPlaceholder": "",
        "sUrl": "",
        "sZeroRecords": "没有相關記錄"
    }
    $.fn.dataTable.defaults.oLanguage=oLanguage;
    //$.extend($.fn.dataTable.defaults.oLanguage,oLanguage)
})();