$(document).ready(function(){

    setSelectedDomain();
    changeActivateDefault();
    $('#SMTP_Server').change(function(){
        var domain = $('#SMTP_Server option:selected').val();
        if(domain == "custom"){
            $('input[name=relayhost]').val("");
            $('input[name=port]').val("");
        }else{
            $('input[name=relayhost]').val(domain);
            $('input[name=port]').val("587");
        }
    });
    $('input[name=chkoldhanguponpolarityswitch]').on('change', function (){
            $("#hanguponpolarityswitch").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldenddialkey]').on('change', function (){
            $("#enddialkey").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");


    $('input[name=chkoldsendpolarityrev]').on('change', function (){
            $("#sendpolarityrev").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");


    $('input[name=chkoldchecksipbinded]').on('change', function (){
            $("#checksipbinded").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldflashwink]').on('change', function (){
            $("#flashwink").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldciddisplay]').on('change', function (){
            $("#ciddisplay").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldusecallerid]').on('change', function (){
            $("#usecallerid").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldhidecallerid]').on('change', function (){
            $("#hidecallerid").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldbusydetect]').on('change', function (){
            $("#busydetect").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldsilencedetect]').on('change', function (){
            $("#silencedetect").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldfaxecm]').on('change', function (){
            $("#faxecm").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");

    $('input[name=chkoldecho]').on('change', function (){
            $("#echo").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change");
    $('input[name=chkoldhandleirregularcid]').on('change', function (){                                         
            $("#handleirregularcid").val($(this).is(":checked") ? "on" : "off");
    }).trigger("change"); 

//	changeActivateDefault();
//	setSelectedDomain();

    $('#SMTP_Server').change(function(){
        var server = $('#SMTP_Server option:selected').text();
        var example = "";

        if(server=="GMAIL" || server=="HOTMAIL"){
            $('input[name=chkoldautentification]').prop("checked", true);
            $('#autentification').val("on");
        }else{
            $('input[name=chkoldautentification]').prop("checked", false);
            $('#autentification').val("off");
        }
        if(server=="GMAIL" || server=="HOTMAIL" || server=="YAHOO"){
            example = "example@"+server.toLowerCase()+".com";
	    $('.validpass').show();
	}else{
            example = "example@domain.com";
	    $('.validpass').hide();
	}
        $('#example').text(example);
    });
});


function setSelectedDomain(){

    $('#SMTP_Server option').each(function(){
        var dominio = $('input[name=relayhost]').val();
        var relay   = $(this).text();
        var server  = "";
        if(/smtp\.gmail\.com/.test(dominio))
            server = "GMAIL";
        if(/smtp\.mail\.yahoo\.com/.test(dominio))
            server = "YAHOO";
        if(/smtp\.live\.com/.test(dominio))
            server = "HOTMAIL";
        if(relay==server){
            var example = "example@"+server.toLowerCase()+".com";
            $(this).attr("selected", "selected");
            $('#example').text(example);
        }
    });

    var server = $('#SMTP_Server option:selected').val();
	if(server=="custom")
		$('.validpass').hide();
    else
		$('.validpass').show();

}

// cambia el estado del hidden "status" de on a off
function changeActivateDefault()
{
    var flash_wink = $('#flashwink').val();
    if(flash_wink=="on"){
        $("input[name=chkoldflashwink]").prop("checked", true);
        $("#flashwink").val("on");
    }else{
        $("input[name=chkoldflash_wink]").prop("checked", false);
        $("#flashwink").val("off");
    }

    var pound_end = $('#pound_end').val();
    if(pound_end=="on"){
        $("input[name=chkoldpound_end]").prop("checked", true);
        $("#pound_end").val("on");
    }else{
        $("input[name=chkoldpound_end]").prop("checked", false);
        $("#pound_end").val("off");
    }



}

