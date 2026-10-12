<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="save" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field" id="ip_address">
                <span class="iss-field__label">{$ip_address.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$ip_address.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$note.LABEL}</span>
                <span class="iss-field__control">{$note.INPUT}</span>
            </div>
        </div>
    </section>
    <input type="hidden" name="mode" value="{$MODE}">
    <input type="hidden" name="idtemp" value="{$IDTEMP}">
</div>
