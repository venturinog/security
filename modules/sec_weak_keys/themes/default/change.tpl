{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="save" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$Extension.LABEL}</span>
                <span class="iss-field__control">{$Extension.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$New_Secret.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$New_Secret.INPUT}</span>
                {if $ERRORS.New_Secret}<div class="invalid-feedback d-block">{$ERRORS.New_Secret}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$Confirm_New_Secret.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$Confirm_New_Secret.INPUT}</span>
                {if $ERRORS.Confirm_New_Secret}<div class="invalid-feedback d-block">{$ERRORS.Confirm_New_Secret}</div>{/if}
            </div>
        </div>
    </section>
</div>
<script src="{$WEBPATH}libs/js/issabel-validate.js"></script>
<script>
{literal}
IssabelValidate.attach(document.querySelector('form'), {
    New_Secret: function (v) { return /^[A-Za-z0-9]{5,}$/.test(v); },
    Confirm_New_Secret: function (v) {
        var n = document.querySelector('[name="New_Secret"]');
        return n && v !== '' && v === n.value;
    }
});
{/literal}
</script>
