{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $MODE ne "view"}
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        {/if}
        <div class="iss-form__buttons">
        {if $MODE eq "view"}
            <input class="button" type="submit" name="edit" value="{$EDIT}">
            <input class="button" type="submit" name="cancel" value="{$BACK}">
        {else}
            <input class="button" type="submit" name="{if $MODE eq 'edit'}save_edit{else}save{/if}" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        {/if}
        </div>
    </div>
    {if $MODE eq "view"}
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid iss-formgrid--3col iss-formgrid--readonly">
            <div class="iss-field">
                <span class="iss-field__label">{$ip_address.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $VALUES.ip_address eq ''} iss-field__value--empty{/if}">{if $VALUES.ip_address eq ''}&mdash;{else}{$VALUES.ip_address|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$note.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $VALUES.note eq ''} iss-field__value--empty{/if}">{if $VALUES.note eq ''}&mdash;{else}{$VALUES.note|escape}{/if}</span>
            </div>
        </div>
    </section>
    {else}
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field" id="ip_address">
                <span class="iss-field__label">{$ip_address.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$ip_address.INPUT}</span>
                {if $ERRORS.ip_address}<div class="invalid-feedback d-block">{$ERRORS.ip_address}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$note.LABEL}</span>
                <span class="iss-field__control">{$note.INPUT}</span>
                {if $ERRORS.note}<div class="invalid-feedback d-block">{$ERRORS.note}</div>{/if}
            </div>
        </div>
    </section>
    {/if}
    <input type="hidden" name="mode" value="{$MODE}">
    <input type="hidden" name="id" value="{$VALUES.id|escape}">
</div>
{if $MODE ne "view"}
<script src="{$WEBPATH}libs/js/issabel-validate.js"></script>
<script>
{literal}
IssabelValidate.attach(document.querySelector('form'), {
    ip_address: function (v) {
        if (v === '') return false;
        var m = v.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})(\/(\d{1,2}))?$/);
        if (!m) return false;
        for (var i = 1; i <= 4; i++) if (parseInt(m[i], 10) > 255) return false;
        if (m[6] !== undefined && (parseInt(m[6], 10) < 0 || parseInt(m[6], 10) > 32)) return false;
        return true;
    }
});
{/literal}
</script>
{/if}
