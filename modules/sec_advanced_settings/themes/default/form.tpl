<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        {if $mode eq 'input'}
        <td align="left">
            <input class="button" type="submit" name="update_advanced_security_settings" value="{$SAVE}">&nbsp;&nbsp;
        </td>
        {/if}
    </tr>
</table>
<table class="tabForm" style="font-size: 16px;" width="99%" >
    <tr>
	<td  width="50%" valign='top'>
	    <table>
		<tr class="letra12">
		    <td align="left" colspan=2><legend>{$subtittle1}</legend></td>
		</tr>
		<tr class="letra12">
		    <td align="left" >
                        <b>{$status_ipbx_frontend.LABEL}:</b>
                    </td>
		    <td align="left" ><input type="hidden" name="oldstatus_ipbx_frontend" id="oldstatus_ipbx_frontend" value="{if $value_ipbx_frontend}1{else}0{/if}" /><div class="form-check form-switch"><input type="checkbox" class="form-check-input" role="switch" name="status_ipbx_frontend" id="status_ipbx_frontend" {if $value_ipbx_frontend}checked="checked"{/if} /></div></td>
		</tr>
        <tr class="letra12">
            <td align="left" ><b>{$status_anonymous_sip.LABEL}:</b></td>
            <td align="left" ><input type="hidden" name="oldstatus_anonymous_sip" id="oldstatus_anonymous_sip" value="{if $value_anonymous_sip}1{else}0{/if}" /><div class="form-check form-switch"><input type="checkbox" class="form-check-input" role="switch" name="status_anonymous_sip" id="status_anonymous_sip" {if $value_anonymous_sip}checked="checked"{/if} /></div></td>
        </tr>
	    </table>
	</td>
	<td width="50%" valign='top'>
	    <table>
		<tr class="letra12">
		    <td align="left" colspan=2><legend>{$subtittle2}</legend></td>
		</tr>
		<tr class="letra12">
		    <td align="left" >
                        <b>{$ipbx_password.LABEL}:</b>                        
                    </td>
		    <td align="left" >{$ipbx_password.INPUT}</td>
		</tr>
		<tr class="letra12">
		    <td align="left" ><b>{$ipbx_confirm_passwrod.LABEL}:</b></td>
		    <td align="left" >{$ipbx_confirm_passwrod.INPUT}</td>
		</tr>
	    </table>
	</td>
    </tr>
</table>
