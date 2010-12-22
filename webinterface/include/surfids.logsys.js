$(document).ready(function(){

    $("#flexi").flexigrid
            (
            {
            url: 'xml_logsys_flexi.php',
            dataType: 'xml',
            colModel : [
                {display: 'Level', name : 'level', width : 40, sortable : true, align: 'left'},
                {display: 'Timestamp', name : 'ts', width : 120, sortable : true, align: 'left'},
                {display: 'Source', name : 'source', width : 80, sortable : true, align: 'left'},
                {display: 'PID', name : 'pid', width : 40, sortable : true, align: 'left'},
                {display: 'Class', name : 'error', width : 100, sortable : true, align: 'left'},
                {display: 'Message', name : 'args', width : 345, sortable : true, align: 'left'},
                {display: 'Sensor', name : 'sensor', width : 80, sortable : true, align: 'left'},
                {display: 'Device', name : 'dev', width : 40, sortable : true, align: 'left'}
                ],
            buttons : [
                ],
            searchitems : [
                {display: 'Level', name : 'level'},
                {display: 'Source', name : 'source'},
                {display: 'PID', name : 'pid'},
                {display: 'Class', name : 'error'},
                {display: 'Message', name : 'args', isdefault: true},
                {display: 'Sensor', name : 'sensor'},
                {display: 'Device', name : 'dev'}
                ],
            sortname: "ts",
            sortorder: "asc",
            usepager: true,
            title: 'Syslog',
//            useRp: true,
            rp: 20,
            showTableToggleBtn: true,
            width: 970,
            height: 380
            }
            );
});

