
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
            <div class="iss-field iss-field--full" id="name">
                <span class="iss-field__label">Protocol{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">
                    <label class="iss-inline-field"><input type="checkbox" name="protocol" id="pro_sip" value="pro_sip" {$check_pro_sip} disabled="disabled"/> SIP</label>
                    <label class="iss-inline-field"><input type="checkbox" name="protocol" id="pro_iax2" value="pro_iax2" {$check_pro_iax2}  checked="checked" disabled="disabled"/> IAX2</label>
                    <label class="iss-inline-field"><input type="checkbox" name="protocol" id="pro_https" value="pro_https" {$check_pro_https} disabled="disabled"/> HTTPS</label>
                    <label class="iss-inline-field"><input type="checkbox" name="protocol" id="pro_http" value="pro_http" {$check_pro_http}  checked="checked" disabled="disabled" /> HTTP</label>
                </span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ip.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$ip.INPUT}</span>
            </div>
            <div class="iss-field" {$port_style} id="port">
                <span class="iss-field__label">{$netmask.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$netmask.INPUT}</span>
            </div>
            <div class="iss-field" {$type_style} id="type">
                <span class="iss-field__label">{$availability.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$availability.INPUT}</span>
            </div>
        </div>
    </section>
    <input type="hidden" name="mode" value="{$MODE}">
    <input type="hidden" name="idtemp" value="{$IDTEMP}">
</div>
