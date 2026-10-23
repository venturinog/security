{if $NO_HOSTNAME_NOTICE ne ''}
<div class="iss-alert iss-alert--error">
    <div class="iss-alert__title">{$DOMAIN}</div>
    <div class="iss-alert__content">{$NO_HOSTNAME_NOTICE}</div>
</div>
{/if}

<form id="certform" method="POST" action="modules/sec_letsencrypt/installcert.php">
{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
        {if $HAS_CERT}
            <input class="button" type="submit" name="renew" value="{$RENEW}">
        {/if}
        {if !$HAS_CERT}
            <input class="button" type="submit" name="install" value="{$INSTALL}">
        {/if}
        </div>
    </div>

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-lock"></i> {if $HAS_CERT}{$RENEWCERT}{else}{$INSTALLNEW}{/if}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$DOMAIN}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">
                    <input type="text" id="domain" name="domain" class="form-control" value="{$valuedomain|escape}" placeholder="your.domain.com" autocomplete="off">
                    <div class="invalid-feedback d-block" id="error_domain" style="display:none;"></div>
                </span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$EMAIL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">
                    <input type="text" id="email" name="email" class="form-control" value="{$valueemail|escape}" placeholder="you@example.com" autocomplete="off">
                    <div class="invalid-feedback d-block" id="error_email" style="display:none;"></div>
                </span>
            </div>
            {if !$HAS_CERT}
            <div class="iss-field">
                <span class="iss-field__label">{$STAGING}</span>
                <span class="iss-field__control"><input type="checkbox" name="staging" id="staging" value="--test-cert" aria-label="{$STAGING}"></span>
            </div>
            {/if}
        </div>
    </section>

    {if $HAS_CERT}
    <div class="iss-alert iss-alert--success">
        <div class="iss-alert__title">{$HASDATA}</div>
        <div class="iss-alert__content">{$DOMAIN}: {$valuedomain|escape} &mdash; {$EMAIL}: {$valueemail|escape}</div>
    </div>
    {/if}

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-list-check"></i> {$USAGE}</header>
        <div class="iss-card__body">
            <ol class="mb-2">
            {foreach from=$STEPS item=STEP}
                <li>{$STEP}</li>
            {/foreach}
            </ol>
            <p class="iss-field__help">{$STEP_NOTE}</p>
        </div>
    </section>
</div>
</form>

<span id="cert_error" style="display:none;">{$CERT_ERROR}</span>
<div id="cert_progress" class="iss-alert iss-alert--info" style="display:none;">
    <div class="iss-alert__content">{$PROCESSING}&hellip;</div>
</div>
<div id="output" class="iss-alert iss-alert--warning" style="display:none;">
    <div class="iss-alert__title">{$CERT_RESULT_TITLE}</div>
    <div class="iss-alert__content" id="output_content"></div>
</div>

<script>
{literal}
(function () {
    var form = document.getElementById('certform');
    if (!form) return;

    function setError(field, message) {
        var input = document.getElementById(field);
        var box = document.getElementById('error_' + field);
        if (!input || !box) return;
        if (message) {
            input.classList.add('is-invalid');
            box.textContent = message;
            box.style.display = '';
        } else {
            input.classList.remove('is-invalid');
            box.textContent = '';
            box.style.display = 'none';
        }
    }

    function showOutput(html) {
        var box = document.getElementById('output');
        document.getElementById('output_content').innerHTML = html || '';
        box.style.display = '';
    }

    function send(action) {
        var domain = document.getElementById('domain');
        var email = document.getElementById('email');
        var token = form.querySelector('input[name="issabel_csrf_token"]');
        var staging = document.getElementById('staging');
        var data = {
            domain: domain ? domain.value : '',
            email: email ? email.value : ''
        };
        if (action === 'renew') {
            data = { renew: 1 };
        } else if (staging && staging.checked) {
            data.staging = '--test-cert';
        }
        if (token) data.issabel_csrf_token = token.value;

        setError('domain', '');
        setError('email', '');
        document.getElementById('cert_progress').style.display = '';
        document.getElementById('output').style.display = 'none';

        $.ajax({
            type: 'POST',
            url: 'modules/sec_letsencrypt/installcert.php',
            data: data,
            dataType: 'json'
        }).done(function (res) {
            if (res && res.errors) {
                if (res.errors.domain) setError('domain', res.errors.domain);
                if (res.errors.email) setError('email', res.errors.email);
            }
            if (res && res.result) showOutput(res.result);
        }).fail(function () {
            showOutput(document.getElementById('cert_error').textContent);
        }).always(function () {
            document.getElementById('cert_progress').style.display = 'none';
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var submitter = e.submitter;
        send(submitter && submitter.name === 'renew' ? 'renew' : 'install');
    });
})();
{/literal}
</script>
