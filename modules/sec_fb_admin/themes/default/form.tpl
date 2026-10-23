
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
            <input class="button" type="submit" name="save" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        {/if}
        </div>
    </div>
    {if $MODE eq "view"}
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid iss-formgrid--3col iss-formgrid--readonly">
            <div class="iss-field">
                <span class="iss-field__label">{$name.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$VALUES.name|escape}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$maxretry.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$VALUES.maxretry|escape}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$bantime.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$VALUES.bantime|escape}</span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$ignoreip.LABEL}</span>
                <span class="iss-field__control iss-field__value">{if $VALUES.ignoreip eq ''}&mdash;{else}{$VALUES.ignoreip|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$enabled.LABEL}</span>
                <span class="iss-field__control iss-field__value">{if $VALUES.enabled eq 'on'}{$YES}{else}{$NO}{/if}</span>
            </div>
        </div>
    </section>
    {else}
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field" id="name">
                <span class="iss-field__label">{$name.LABEL}</span>
                <span class="iss-field__control">{$name.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$maxretry.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$maxretry.INPUT}</span>
                {if $ERRORS.maxretry}<div class="invalid-feedback d-block">{$ERRORS.maxretry}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$bantime.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$bantime.INPUT}</span>
                {if $ERRORS.bantime}<div class="invalid-feedback d-block">{$ERRORS.bantime}</div>{/if}
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$ignoreip.LABEL}</span>
                <span class="iss-field__control">{$ignoreip.INPUT}</span>
                {if $ERRORS.ignoreip}<div class="invalid-feedback d-block">{$ERRORS.ignoreip}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$enabled.LABEL}</span>
                <span class="iss-field__control">{$enabled.INPUT}</span>
            </div>
        </div>
    </section>
    {/if}
    <input type="hidden" name="mode" value="{$MODE}">
    <input type="hidden" name="idtemp" value="{$VALUES.id|escape}">
</div>
{if $MODE ne "view"}
<script src="{$WEBPATH}libs/js/issabel-validate.js"></script>
<script>
{literal}
IssabelValidate.attach(document.querySelector('form'), {
    maxretry: function (v) { return /^[0-9]+$/.test(v) && parseInt(v, 10) >= 1; },
    bantime: function (v) { return /^[0-9]+$/.test(v) && parseInt(v, 10) >= 1; },
    ignoreip: function (v) {
        v = (v || '').trim();
        if (v === '') return true;
        return v.split(/[\s,]+/).every(function (item) {
            if (item === '') return true;
            var parts = item.split('/');
            var mask = parts.length > 1 ? parseInt(parts[1], 10) : null;
            if (parts[0].indexOf(':') !== -1) {
                return /^[0-9a-fA-F:]+$/.test(parts[0])
                    && (mask === null || (mask >= 0 && mask <= 128));
            }
            var ipOk = /^(\d{1,3}\.){3}\d{1,3}$/.test(parts[0]);
            if (!ipOk) return false;
            var oct = parts[0].split('.');
            for (var i = 0; i < 4; i++) if (parseInt(oct[i], 10) > 255) return false;
            return mask === null || (mask >= 0 && mask <= 32);
        });
    }
});
{/literal}
</script>
{/if}
