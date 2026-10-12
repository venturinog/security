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
            <div class="iss-field">
                <span class="iss-field__label">{$protocol.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$protocol.INPUT}</span>
            </div>
            <div class="iss-field" {$port_style} id="port">
                <span class="iss-field__label">{$port.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">
                {if $MODE eq "new"}
                {$port.INPUT}&nbsp;:&nbsp;{$port2.INPUT}
                {/if}
                {if $MODE eq "edit"}
                {$port.INPUT}&nbsp;:&nbsp;{$port2.INPUT}
                {/if}
                {if $MODE eq "view"}
                {$port.INPUT}&nbsp; {if $HAS eq "yes"}:{/if} &nbsp;{$port2.INPUT}
                {/if}
                </span>
            </div>
            <div class="iss-field" {$type_style} id="type">
                <span class="iss-field__label">{$type.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$type.INPUT}</span>
            </div>
            <div class="iss-field" {$code_style} id="code">
                <span class="iss-field__label">{$code.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$code.INPUT}</span>
            </div>
            <div class="iss-field" {$protocol_style} id="protocol_number">
                <span class="iss-field__label">{$protocol_number.LABEL}{if $MODE ne "view"}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$protocol_number.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$comment.LABEL}</span>
                <span class="iss-field__control">{$comment.INPUT}</span>
            </div>
        </div>
    </section>
    <input type="hidden" name="mode" value="{$MODE}">
    <input type="hidden" name="idtemp" value="{$IDTEMP}">
</div>
