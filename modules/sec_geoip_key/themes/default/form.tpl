{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $MODE eq "edit"}
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        {/if}
        <div class="iss-form__buttons">
        {if $MODE eq "edit"}
            <input class="button" type="submit" name="save_new" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        {else}
            <input class="button" type="submit" name="edit" value="{$EDIT}">
            <input class="button" type="submit" name="cancel" value="{$BACK}">
        {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
        {if $MODE eq "edit"}
            <div class="iss-field">
                <span class="iss-field__label">{$key.LABEL}</span>
                <span class="iss-field__control">{$key.INPUT}</span>
                {if $ERRORS.key}<div class="invalid-feedback d-block">{$ERRORS.key}</div>{/if}
            </div>
        {else}
            <div class="iss-field">
                <span class="iss-field__label">{$key.LABEL}</span>
                <span class="iss-field__control iss-field__value">{if $HAS_KEY eq 'yes'}{$MASKED_KEY}&nbsp;({$KEY_STATUS}){else}{$KEY_STATUS}{/if}</span>
            </div>
        {/if}
        </div>
    </section>
    <div class="iss-form__hint">
        <a href="https://www.maxmind.com/en/geolite2/signup" target="_blank" rel="noopener">{$MAXMIND_SIGNUP}</a>
    </div>
</div>
{if $MODE eq "edit"}
<script src="{$WEBPATH}libs/js/issabel-validate.js"></script>
<script>
{literal}
IssabelValidate.attach(document.querySelector('form'), {
    key: function (v) { return v === '' || /^[A-Za-z0-9_-]{1,256}$/.test(v); }
});
{/literal}
</script>
{/if}
