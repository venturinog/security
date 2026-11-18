{$SECURITY_ALERT}
<h3 class="iss-page-title">{$TITLE}</h3>
<div class="iss-alert iss-alert--info">{$WARNING}</div>

<div class="iss-form__toolbar">
    <span class="badge {if $ENABLED}bg-green-lt{else}bg-secondary-lt{/if}">
        {if $ENABLED}{$ON}{else}{$OFF}{/if}
    </span>
    <span class="text-muted">{$STATUS}: {$SERVICE|escape}</span>
    <div class="iss-form__buttons">
        {if $ENABLED}
        <form method="POST" action="?menu={$MODULE}" class="d-inline">
            {$CSRF_FIELD}
            <input type="hidden" name="action" value="off">
            <button type="submit" class="btn btn-sm btn-outline-danger">{$OFF}</button>
        </form>
        {else}
        <form method="POST" action="?menu={$MODULE}" class="d-inline">
            {$CSRF_FIELD}
            <input type="hidden" name="action" value="on">
            <button type="submit" class="btn btn-sm btn-outline-primary">{$ON}</button>
        </form>
        {/if}
        <form method="POST" action="?menu={$MODULE}" class="d-inline">
            {$CSRF_FIELD}
            <input type="hidden" name="action" value="flush">
            <button type="submit" class="btn btn-sm btn-outline-secondary">{$FLUSH}</button>
        </form>
    </div>
</div>

<form method="POST" action="?menu={$MODULE}">
    {$CSRF_FIELD}
    <input type="hidden" name="action" value="save">
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid iss-formgrid--3col">
            <div class="iss-field">
                <span class="iss-field__label">{$ALLOW_TIMEOUT}</span>
                <input class="form-control" type="number" min="60" name="allow_timeout" value="{$SETTINGS.allow_timeout|escape}">
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$SIP_PORT}</span>
                <input class="form-control" type="number" min="1" max="65535" name="sip_port" value="{$SETTINGS.sip_port|escape}">
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$PROBE_RATE}</span>
                <input class="form-control" type="number" min="1" name="probe_rate" value="{$SETTINGS.probe_rate|escape}">
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$RTP_START}</span>
                <input class="form-control" type="number" min="1" max="65535" name="rtp_start" value="{$SETTINGS.rtp_start|escape}">
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$RTP_END}</span>
                <input class="form-control" type="number" min="1" max="65535" name="rtp_end" value="{$SETTINGS.rtp_end|escape}">
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$PROBE_BURST}</span>
                <input class="form-control" type="number" min="1" name="probe_burst" value="{$SETTINGS.probe_burst|escape}">
            </div>
        </div>
    </section>
    <div class="iss-form__buttons"><button type="submit" class="btn btn-sm btn-primary">{$SAVE}</button></div>
</form>

<h4 class="fw-grid-title">{$ALLOWED_IPS}</h4>
<table class="table table-sm table-striped">
    <thead><tr><th>IP</th><th>Set</th><th>Timeout</th></tr></thead>
    <tbody>
    {foreach from=$ALLOWED item=a}
        <tr><td>{$a.ip|escape}</td><td>{$a.set|escape}</td><td>{$a.timeout|escape}</td></tr>
    {foreachelse}
        <tr><td colspan="3">{$NO_IPS}</td></tr>
    {/foreach}
    </tbody>
</table>
