// 对Date的扩展，将 Date 转化为指定格式的String
// 月(M)、日(d)、小时(h)、分(m)、秒(s)、季度(q) 可以用 1-2 个占位符， 
// 年(y)可以用 1-4 个占位符，毫秒(S)只能用 1 个占位符(是 1-3 位的数字) 
// 例子： 
// (new Date()).Format("yyyy-MM-dd hh:mm:ss.S") ==> 2006-07-02 08:09:04.423 
// (new Date()).Format("yyyy-M-d h:m:s.S")      ==> 2006-7-2 8:9:4.18 
Date.prototype.Format = function (fmt) { //author: meizz 
    var o = {
        "M+": this.getMonth() + 1, //月份 
        "d+": this.getDate(), //日 
        "h+": this.getHours(), //小时 
        "m+": this.getMinutes(), //分 
        "s+": this.getSeconds(), //秒 
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度 
        "S": this.getMilliseconds() //毫秒 
    };
    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
}

function str2date(str) {
    try {
        var fullDate = str.substr(0, 10).split("-");
        return new Date(fullDate[0], fullDate[1] - 1, fullDate[2], 0, 0, 0);
    }
    catch (e) {
        return new Date()
    }
}

function getPreMonth(date) {
    var arr = date.split('-');
    var year = arr[0]; //获取当前日期的年份
    var month = arr[1]; //获取当前日期的月份
    var day = arr[2]; //获取当前日期的日
    var days = new Date(year, month, 0);
    days = days.getDate(); //获取当前日期中月的天数
    var year2 = year;
    var month2 = parseInt(month) - 1;
    if (month2 == 0) {
        year2 = parseInt(year2) - 1;
        month2 = 12;
    }
    var day2 = day;
    var days2 = new Date(year2, month2, 0);
    days2 = days2.getDate();
    if (day2 > days2) {
        day2 = days2;
    }
    if (month2 < 10) {
        month2 = '0' + month2;
    }
    var t2 = year2 + '-' + month2 + '-' + day2;
    return t2;
}

function getNextMonth(date) {
    var arr = date.split('-');
    var year = arr[0]; //获取当前日期的年份
    var month = arr[1]; //获取当前日期的月份
    var day = arr[2]; //获取当前日期的日
    var days = new Date(year, month, 0);
    days = days.getDate(); //获取当前日期中的月的天数
    var year2 = year;
    var month2 = parseInt(month) + 1;
    if (month2 == 13) {
        year2 = parseInt(year2) + 1;
        month2 = 1;
    }
    var day2 = day;
    var days2 = new Date(year2, month2, 0);
    days2 = days2.getDate();
    if (day2 > days2) {
        day2 = days2;
    }
    if (month2 < 10) {
        month2 = '0' + month2;
    }

    var t2 = year2 + '-' + month2 + '-' + day2;
    return t2;
}

var monthFullName = ['', 'January ', 'February', 'March', 'April', 'May ', 'June ', 'July', 'August', 'September ', 'October ', 'November', 'December'];

function getDateNumber(number) {
    return number > 10 ? number : '0' + number;
}

function getMonth() {

}

function getToday() {
    var now = new Date();
    return now.getFullYear() + "" + getDateNumber((Number(now.getMonth()) + 1)) + "" + getDateNumber(now.getDate());
}


function getWeekStart() {
    var now = new Date();
    var firstDay = new Date(now - (now.getDay() - 1) * 86400000);
    var month = Number(firstDay.getMonth()) + 1
    return firstDay.getFullYear() + "" + month + "" + firstDay.getDate();
}

function getWeekEnd() {
    var now = new Date();
    var firstDay = new Date(now - (now.getDay() - 1) * 86400000);
    var lastDay = new Date((firstDay / 1000 + 6 * 86400) * 1000);
    var month = Number(lastDay.getMonth()) + 1
    return lastDay.getFullYear() + "" + month + "" + lastDay.getDate();
}

function getMonthStart() {
    var now = new Date();
    var month = Number(now.getMonth()) + 1
    return now.getFullYear() + "" + getDateNumber(month) + "" + '01';
}

function getMonthEnd() {
    var now = new Date();
    var nextFirstDay = new Date(now.getFullYear(), now.getMonth() + 1, 1);
    var lastDay = new Date(nextFirstDay - 86400000);
    var month = Number(lastDay.getMonth()) + 1
    return lastDay.getFullYear() + "" + getDateNumber(month) + "" + lastDay.getDate();
}

function getLastMonthStart() {
    var now = new Date();
    var firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);

    var month = Number(firstDay.getMonth()) + 1
    return firstDay.getFullYear() + "" + getDateNumber(month) + "" + '01';
}

function getLastMonthEnd() {
    var now = new Date();
    var nextFirstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    var lastDay = new Date(nextFirstDay - 86400000);
    var month = Number(lastDay.getMonth()) + 1
    return lastDay.getFullYear() + "" + getDateNumber(month) + "" + lastDay.getDate();
}

function getYearStart() {
    var now = new Date();
    var year = now.getFullYear()
    return year + "0101";
}

function getYearEnd() {
    var now = new Date();
    var year = now.getFullYear()
    return year + "1231";
}