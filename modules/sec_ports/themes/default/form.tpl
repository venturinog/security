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
                <span class="iss-field__control iss-field__value">{if $VALUES.name eq ''}&mdash;{else}{$VALUES.name|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$protocol.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$PROTOCOL|escape}</span>
            </div>
            {if $PROTOCOL eq "TCP" || $PROTOCOL eq "UDP"}
            <div class="iss-field">
                <span class="iss-field__label">{$port.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$VALUES.port|escape}{if $VALUES.port2 ne ''}&nbsp;:&nbsp;{$VALUES.port2|escape}{/if}</span>
            </div>
            {elseif $PROTOCOL eq "ICMP"}
            <div class="iss-field">
                <span class="iss-field__label">{$type.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$VALUES.type|escape}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$code.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$VALUES.code|escape}</span>
            </div>
            {else}
            <div class="iss-field">
                <span class="iss-field__label">{$protocol_number.LABEL}</span>
                <span class="iss-field__control iss-field__value">{$VALUES.protocol_number|escape}</span>
            </div>
            {/if}
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$comment.LABEL}</span>
                <span class="iss-field__control iss-field__value">{if $VALUES.comment eq ''}&mdash;{else}{$VALUES.comment|escape}{/if}</span>
            </div>
        </div>
    </section>
    {else}
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field" id="name">
                <span class="iss-field__label">{$name.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$name.INPUT}</span>
                {if $ERRORS.name}<div class="invalid-feedback d-block">{$ERRORS.name}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$protocol.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$protocol.INPUT}</span>
            </div>
            <div class="iss-field" id="port">
                <span class="iss-field__label">{$port.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$port.INPUT}&nbsp;:&nbsp;{$port2.INPUT}</span>
                {if $ERRORS.port}<div class="invalid-feedback d-block">{$ERRORS.port}</div>{/if}
                {if $ERRORS.port2}<div class="invalid-feedback d-block">{$ERRORS.port2}</div>{/if}
            </div>
            <div class="iss-field" id="type">
                <span class="iss-field__label">{$type.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$type.INPUT}</span>
                {if $ERRORS.type}<div class="invalid-feedback d-block">{$ERRORS.type}</div>{/if}
            </div>
            <div class="iss-field" id="code">
                <span class="iss-field__label">{$code.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$code.INPUT}</span>
                {if $ERRORS.code}<div class="invalid-feedback d-block">{$ERRORS.code}</div>{/if}
            </div>
            <div class="iss-field" id="protocol_number">
                <span class="iss-field__label">{$protocol_number.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$protocol_number.INPUT}</span>
                {if $ERRORS.protocol_number}<div class="invalid-feedback d-block">{$ERRORS.protocol_number}</div>{/if}
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$comment.LABEL}</span>
                <span class="iss-field__control">{$comment.INPUT}</span>
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
(function () {
    var protocol = document.querySelector('[name="protocol"]');
    if (protocol) hideField(protocol.value);

    function num(v) { return /^[0-9]+$/.test(v); }
    IssabelValidate.attach(document.querySelector('form'), {
        name: function (v) { return v !== ''; },
        port: function (v) { return num(v) && parseInt(v, 10) >= 1 && parseInt(v, 10) <= 65535; },
        port2: function (v) { return v === '' || (num(v) && parseInt(v, 10) >= 1 && parseInt(v, 10) <= 65535); },
        type: function (v) { return v === '' || (num(v) && parseInt(v, 10) <= 255); },
        code: function (v) { return v === '' || (num(v) && parseInt(v, 10) <= 255); },
        protocol_number: function (v) { return v === '' || (num(v) && parseInt(v, 10) <= 255); }
    });
})();
{/literal}
</script>
{/if}
