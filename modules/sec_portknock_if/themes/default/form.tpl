<form  method='POST' style='margin-bottom:0;' action='?menu={$MODULE_NAME}&amp;action=setport'>
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
            <div class="iss-field" id="name">
                <span class="iss-field__label">{$eth_in.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$eth_in.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$port.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$port.INPUT}</span>
            </div>
        </div>
    </section>
</div>
</form>
