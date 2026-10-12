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
            <div class="iss-field" {$DISPLAY}>
                <span class="iss-field__label">{$Current_Secret.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$Current_Secret.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$New_Secret.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$New_Secret.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$Confirm_New_Secret.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$Confirm_New_Secret.INPUT}</span>
            </div>
        </div>
    </section>
</div>
