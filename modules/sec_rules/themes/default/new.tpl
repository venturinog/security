{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="save" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </div>
    </div>

    <div class="iss-reveal">
    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-arrows-exchange"></i> {$traffic_label}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$traffic_label}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$traffic_html}</span>
                {if $ERRORS.id_traffic}<div class="invalid-feedback d-block">{$ERRORS.id_traffic}</div>{/if}
            </div>
            <div class="iss-field" id="id_interface_in">
                <span class="iss-field__label">{$interface_in.LABEL}</span>
                <span class="iss-field__control">{$interface_in.INPUT}</span>
                {if $ERRORS.interface_in}<div class="invalid-feedback d-block">{$ERRORS.interface_in}</div>{/if}
            </div>
            <div class="iss-field" id="id_interface_out">
                <span class="iss-field__label">{$interface_out.LABEL}</span>
                <span class="iss-field__control">{$interface_out.INPUT}</span>
                {if $ERRORS.interface_out}<div class="invalid-feedback d-block">{$ERRORS.interface_out}</div>{/if}
            </div>
        </div>
    </section>

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-network"></i> {$ip_detail}</header>
        <div class="iss-card__body iss-formgrid iss-formgrid--3col">
            <div class="iss-field" id="id_source">
                <span class="iss-field__label">{$ip_source.LABEL}</span>
                <span class="iss-field__control">{$ip_source.INPUT}</span>
                {if $ERRORS.ip_source}<div class="invalid-feedback d-block">{$ERRORS.ip_source}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$mask_source.LABEL}</span>
                <span class="iss-field__control">{$mask_source.INPUT}</span>
                {if $ERRORS.mask_source}<div class="invalid-feedback d-block">{$ERRORS.mask_source}</div>{/if}
            </div>
            <div class="iss-field" id="id_destin">
                <span class="iss-field__label">{$ip_destin.LABEL}</span>
                <span class="iss-field__control">{$ip_destin.INPUT}</span>
                {if $ERRORS.ip_destin}<div class="invalid-feedback d-block">{$ERRORS.ip_destin}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$mask_destin.LABEL}</span>
                <span class="iss-field__control">{$mask_destin.INPUT}</span>
                {if $ERRORS.mask_destin}<div class="invalid-feedback d-block">{$ERRORS.mask_destin}</div>{/if}
            </div>
        </div>
    </section>

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-protocol"></i> {$protocol_detail}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$protocol_label}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$protocol_html}</span>
                {if $ERRORS.id_protocol}<div class="invalid-feedback d-block">{$ERRORS.id_protocol}</div>{/if}
            </div>
            <div class="iss-field" id="id_port_in">
                <span class="iss-field__label">{$port_in.LABEL}</span>
                <span class="iss-field__control">{$port_in.INPUT}</span>
                {if $ERRORS.port_in}<div class="invalid-feedback d-block">{$ERRORS.port_in}</div>{/if}
            </div>
            <div class="iss-field" id="id_port_out">
                <span class="iss-field__label">{$port_out.LABEL}</span>
                <span class="iss-field__control">{$port_out.INPUT}</span>
                {if $ERRORS.port_out}<div class="invalid-feedback d-block">{$ERRORS.port_out}</div>{/if}
            </div>
            <div class="iss-field" id="id_type_icmp">
                <span class="iss-field__label">{$type_icmp.LABEL}</span>
                <span class="iss-field__control">{$type_icmp.INPUT}</span>
                {if $ERRORS.type_icmp}<div class="invalid-feedback d-block">{$ERRORS.type_icmp}</div>{/if}
            </div>
            <div class="iss-field" id="id_id_ip">
                <span class="iss-field__label">{$id_ip.LABEL}</span>
                <span class="iss-field__control">{$id_ip.INPUT}</span>
                {if $ERRORS.id_ip}<div class="invalid-feedback d-block">{$ERRORS.id_ip}</div>{/if}
            </div>
            <div class="iss-field" id="id_established">
                <span class="iss-field__label">{$established.LABEL}</span>
                <span class="iss-field__control">{$established.INPUT}</span>
            </div>
            <div class="iss-field" id="id_related">
                <span class="iss-field__label">{$related.LABEL}</span>
                <span class="iss-field__control">{$related.INPUT}</span>
                {if $ERRORS.state}<div class="invalid-feedback d-block">{$ERRORS.state}</div>{/if}
            </div>
            <div class="iss-field" id="id_geoipcontinents">
                <span class="iss-field__label">{$geoipcontinents.LABEL}</span>
                <span class="iss-field__control">{$geoipcontinents.INPUT}</span>
            </div>
            <div class="iss-field" id="id_geoipcountries">
                <span class="iss-field__label">{$geoipcountries.LABEL}</span>
                <span class="iss-field__control">{$geoipcountries.INPUT}</span>
                {if $ERRORS.geoipcountries}<div class="invalid-feedback d-block">{$ERRORS.geoipcountries}</div>{/if}
            </div>
        </div>
    </section>

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-gavel"></i> {$action_detail}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field" id="id_target">
                <span class="iss-field__label">{$target.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$target.INPUT}</span>
                {if $ERRORS.target}<div class="invalid-feedback d-block">{$ERRORS.target}</div>{/if}
            </div>
            <div class="iss-field" id="id_orden">
                <span class="iss-field__label">{$orden.LABEL}</span>
                <span class="iss-field__control">{$orden.INPUT}</span>
            </div>
        </div>
    </section>
    </div>

    {$id.INPUT}
    {$state.INPUT}
</div>
<script src="{$WEBPATH}libs/js/issabel-validate.js"></script>
<script>
{literal}
(function () {
    var traffic = document.querySelector('[name="id_traffic"]');
    var protocol = document.querySelector('[name="id_protocol"]');
    if (traffic) showElementByTraffic();
    if (protocol) showElementByProtocol();

    function ip(v) {
        if (v === '') return true;
        var m = v.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/);
        if (!m) return false;
        for (var i = 1; i <= 4; i++) if (parseInt(m[i], 10) > 255) return false;
        return true;
    }
    IssabelValidate.attach(document.querySelector('form'), {
        ip_source: ip,
        ip_destin: ip,
        mask_source: function (v) { return v === '' || (/^[0-9]{1,2}$/.test(v) && parseInt(v, 10) <= 32); },
        mask_destin: function (v) { return v === '' || (/^[0-9]{1,2}$/.test(v) && parseInt(v, 10) <= 32); }
    });
})();
{/literal}
</script>
