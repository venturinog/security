{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $MODE eq 'edit'}
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        {/if}
        <div class="iss-form__buttons">
        {if $MODE eq 'edit'}
            <input class="button" type="submit" name="update_advanced_security_settings" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        {else}
            <input class="button" type="submit" name="edit" value="{$EDIT}">
        {/if}
        </div>
    </div>
    {if $MODE eq 'edit'}
    <div class="iss-reveal">
    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-shield"></i> {$subtittle1}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$status_ipbx_frontend.LABEL}</span>
                <span class="iss-field__control"><div class="form-check form-switch"><input type="checkbox" class="form-check-input" role="switch" name="status_ipbx_frontend" id="status_ipbx_frontend" {if $value_ipbx_frontend}checked="checked"{/if} /></div></span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$status_anonymous_sip.LABEL}</span>
                <span class="iss-field__control"><div class="form-check form-switch"><input type="checkbox" class="form-check-input" role="switch" name="status_anonymous_sip" id="status_anonymous_sip" {if $value_anonymous_sip}checked="checked"{/if} /></div></span>
            </div>
        </div>
    </section>
    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-lock"></i> {$subtittle2}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$ipbx_password.LABEL}</span>
                <span class="iss-field__control">{$ipbx_password.INPUT}</span>
                {if $ERRORS.ipbx_password}<div class="invalid-feedback d-block">{$ERRORS.ipbx_password}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ipbx_confirm_passwrod.LABEL}</span>
                <span class="iss-field__control">{$ipbx_confirm_passwrod.INPUT}</span>
                {if $ERRORS.ipbx_confirm_passwrod}<div class="invalid-feedback d-block">{$ERRORS.ipbx_confirm_passwrod}</div>{/if}
            </div>
        </div>
    </section>
    </div>
    {else}
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid iss-formgrid--3col iss-formgrid--readonly">
            <div class="iss-field">
                <span class="iss-field__label">{$status_ipbx_frontend.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$STATUS_IPBX}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$status_anonymous_sip.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$STATUS_SIP}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ipbx_password.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$PASSWORD_MASK}</span>
            </div>
        </div>
    </section>
    {/if}
</div>
{if $MODE eq 'edit'}
<script src="{$WEBPATH}libs/js/issabel-validate.js"></script>
<script>
{literal}
IssabelValidate.attach(document.querySelector('form'), {
    ipbx_password: function (v) {
        if (v === '') return true;
        return /^[a-zA-Z0-9\-@=_!<> .|&]+$/.test(v);
    },
    ipbx_confirm_passwrod: function (v) {
        if (v === '') return true;
        var p = document.querySelector('[name="ipbx_password"]');
        return p && v === p.value;
    }
});
{/literal}
</script>
{/if}
