$(document).ready(function(){
    $('input[name=chkoldavailability]').on('change', function (){
            $("#availability").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");
});
