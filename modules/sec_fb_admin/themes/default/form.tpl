
<div class="iss-form">
    <div class="iss-form__toolbar">
    {if $MODE ne "view"}
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
    {/if}
        <div class="iss-form__buttons">
        {if $MODE eq "new"}
            <input class="button" type="submit" name="save" value="{$SAVE}">
        {/if}
        {if $MODE eq "edit"}
            <input class="button" type="submit" name="save" value="{$SAVE}">
        {/if}
        {if $MODE eq "view"}
            <input class="button" type="submit" name="edit" value="{$EDIT}">
        {/if}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field" id="name">
                <span class="iss-field__label">{$name.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$name.INPUT}</span>
            </div>
            <div class="iss-field" {$type_style}>
                <span class="iss-field__label">{$maxretry.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$maxretry.INPUT}</span>
            </div>
            <div class="iss-field" {$type_style}>
                <span class="iss-field__label">{$bantime.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$bantime.INPUT}</span>
            </div>
            <div class="iss-field" {$type_style}>
                <span class="iss-field__label">{$ignoreip.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$ignoreip.INPUT}</span>
            </div>
            <div class="iss-field" {$type_style}>
                <span class="iss-field__label">{$enabled.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$enabled.INPUT}</span>
            </div>
        </div>
    </section>
    <input type="hidden" name="mode" value="{$MODE}">
    <input type="hidden" name="idtemp" value="{$IDTEMP}">
</div>
