<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
        {if $mode eq 'input'}
            <input class="button" type="submit" name="save_new" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        {elseif $mode eq 'view'}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        {elseif $mode eq 'edit'}
            <input class="button" type="submit" name="save_edit" value="{$EDIT}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$key.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$key.INPUT}</span>
            </div>
        </div>
    </section>
    <input class="button" type="hidden" name="id" value="{$ID}" />
    <br>
    <a href="https://www.maxmind.com/en/geolite2/signup" target="_blank">
    {$MAXMIND_SIGNUP}
    </a>
</div>
