<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
        {if $mode eq 'input'}
            <input class="button" type="submit" name="update_advanced_security_settings" value="{$SAVE}">
        {/if}
        </div>
    </div>
    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-shield"></i> {$subtittle1}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$status_ipbx_frontend.LABEL}</span>
                <span class="iss-field__control"><input type="hidden" name="oldstatus_ipbx_frontend" id="oldstatus_ipbx_frontend" value="{if $value_ipbx_frontend}1{else}0{/if}" /><div class="form-check form-switch"><input type="checkbox" class="form-check-input" role="switch" name="status_ipbx_frontend" id="status_ipbx_frontend" {if $value_ipbx_frontend}checked="checked"{/if} /></div></span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$status_anonymous_sip.LABEL}</span>
                <span class="iss-field__control"><input type="hidden" name="oldstatus_anonymous_sip" id="oldstatus_anonymous_sip" value="{if $value_anonymous_sip}1{else}0{/if}" /><div class="form-check form-switch"><input type="checkbox" class="form-check-input" role="switch" name="status_anonymous_sip" id="status_anonymous_sip" {if $value_anonymous_sip}checked="checked"{/if} /></div></span>
            </div>
        </div>
    </section>
    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-lock"></i> {$subtittle2}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$ipbx_password.LABEL}</span>
                <span class="iss-field__control">{$ipbx_password.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ipbx_confirm_passwrod.LABEL}</span>
                <span class="iss-field__control">{$ipbx_confirm_passwrod.INPUT}</span>
            </div>
        </div>
    </section>
</div>
