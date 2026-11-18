{$SECURITY_ALERT}
<h3 class="iss-page-title">{$TITLE}</h3>

{if $WARNINGS}
<div class="iss-alert iss-alert--warning">
    <strong>{$WARNING}:</strong>
    <ul>{foreach from=$WARNINGS item=w}<li>{$w|escape}</li>{/foreach}</ul>
</div>
{/if}

<h4 class="fw-grid-title">{$PROTO} / {$PORT}</h4>
<table class="table table-sm table-striped fw-discovery-table">
    <thead>
        <tr>
            <th>{$PROTO}</th>
            <th>{$TRANSPORT}</th>
            <th>{$PORT}</th>
            <th>{$SOURCE}</th>
            <th>{$CONFIDENCE}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$SERVICES item=s}
        <tr>
            <td>{$s.proto|escape}</td>
            <td>{$s.transport|escape}</td>
            <td>{$s.port|escape}</td>
            <td>{$s.source|escape}</td>
            <td>{$s.confidence|escape}</td>
            <td>
            {if $s.in_catalog}
                <span class="badge bg-green-lt">{$ADD_PORT} ✓</span>
            {else}
                <form method="POST" action="?menu={$MODULE}" class="d-inline">
                    {$CSRF_FIELD}
                    <input type="hidden" name="action" value="addport">
                    <input type="hidden" name="name" value="{$s.catalog_name|escape}">
                    <input type="hidden" name="protocol" value="{$s.catalog_protocol|escape}">
                    <input type="hidden" name="details" value="{$s.port|escape}">
                    <input type="hidden" name="comment" value="discovery">
                    <button type="submit" class="btn btn-sm btn-outline-primary">{$ADD_PORT}</button>
                </form>
            {/if}
            </td>
        </tr>
    {foreachelse}
        <tr><td colspan="6">{$NO_SERVICES}</td></tr>
    {/foreach}
    </tbody>
</table>

<h4 class="fw-grid-title">{$NAME} / {$HOST}</h4>
<table class="table table-sm table-striped fw-discovery-table">
    <thead>
        <tr>
            <th>{$NAME}</th>
            <th>{$TECH}</th>
            <th>{$HOST}</th>
            <th>{$DYNAMIC}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$PEERS item=p}
        <tr>
            <td>{$p.name|escape}</td>
            <td>{$p.tech|escape}</td>
            <td>{$p.host|escape}</td>
            <td>{if $p.dynamic}{$DYNAMIC}{else}&mdash;{/if}</td>
            <td>
            {if !$p.dynamic}
                <form method="POST" action="?menu={$MODULE}" class="d-inline">
                    {$CSRF_FIELD}
                    <input type="hidden" name="action" value="addwhitelist">
                    <input type="hidden" name="ip" value="{$p.host|escape}">
                    <button type="submit" class="btn btn-sm btn-outline-primary">{$ADD_WL}</button>
                </form>
                <form method="POST" action="?menu={$MODULE}" class="d-inline">
                    {$CSRF_FIELD}
                    <input type="hidden" name="action" value="addrule">
                    <input type="hidden" name="ip" value="{$p.host|escape}">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">{$ADD_RULE}</button>
                </form>
            {/if}
            </td>
        </tr>
    {foreachelse}
        <tr><td colspan="5">{$NO_PEERS}</td></tr>
    {/foreach}
    </tbody>
</table>

<p class="text-muted">
    <strong>{$RTP_RANGE}:</strong>
    {if $RTP.start}{$RTP.start} - {$RTP.end}{else}&mdash;{/if}
</p>
