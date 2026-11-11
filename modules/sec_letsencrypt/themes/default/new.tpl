{if $NO_HOSTNAME_NOTICE ne ''}
<div class="iss-alert iss-alert--error">
    <div class="iss-alert__title">{$DOMAIN}</div>
    <div class="iss-alert__content">{$NO_HOSTNAME_NOTICE}</div>
</div>
{/if}

<form id="certform" method="POST" action="modules/sec_letsencrypt/installcert.php">
{$ISSABEL_CSRF_FIELD}
<input type="hidden" name="reissue" id="reissue" value="{if $HAS_CERT}1{else}0{/if}">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
        {if $HAS_CERT}
            <input class="button" type="submit" name="renew" value="{$RENEW}">
        {/if}
            <input class="button" type="submit" name="test" value="{$TEST}">
            <input class="button" type="submit" name="install"
                   value="{if $HAS_CERT}{$REISSUE}{else}{$INSTALL}{/if}"
                   {if $HAS_CERT}data-confirm="{$REISSUE_CONFIRM|escape}"{/if}>
        </div>
    </div>

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-lock"></i> {if $HAS_CERT}{$RENEWCERT}{else}{$INSTALLNEW}{/if}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <label class="iss-field__label" for="method">{$VALIDATION_METHOD}</label>
                <span class="iss-field__control">
                    <select class="form-select" name="method" id="method">
                    {foreach from=$METHODS key=mid item=m}
                        <option value="{$mid}" data-group="{$m.GROUP}" data-propagation="{$m.PROPAGATION_DEFAULT}" data-summary="{$m.SUMMARY|escape}" {if $mid eq $CURRENT_METHOD}selected{/if}>{$m.LABEL|escape}</option>
                    {/foreach}
                    </select>
                </span>
                <p class="iss-field__help" id="method_summary">{$CURRENT_SUMMARY|escape}</p>
            </div>
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
            <div class="iss-field" id="propagation_field" {if $CURRENT_GROUP ne 'dns'}style="display:none;"{/if}>
                <label class="iss-field__label" for="propagation">{$PROPAGATION_LABEL}</label>
                <span class="iss-field__control">
                    <input class="form-control" type="number" id="propagation" name="propagation" min="5" max="300" inputmode="numeric"
                           value="{$CURRENT_PROPAGATION}" {if $CURRENT_GROUP ne 'dns'}disabled{/if}>
                    <div class="invalid-feedback d-block" id="error_propagation" style="display:none;"></div>
                </span>
                <p class="iss-field__help">{$PROPAGATION_HELP}</p>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$STAGING}</span>
                <span class="iss-field__control iss-inline-field">
                    <input type="checkbox" name="staging" id="staging" value="--test-cert" aria-label="{$STAGING}">
                </span>
            </div>
        </div>
        <div class="iss-card__body">
            <p class="iss-field__help">{$WILDCARD_HINT}</p>
        </div>
    </section>

    {foreach from=$METHODS key=mid item=m}
    {if $m.GROUP eq 'dns'}
    <section class="iss-card" data-method-fields="{$mid}" {if $m.CREDENTIALS_PROVIDER ne '' && $CREDENTIALS_PROVIDER eq $m.CREDENTIALS_PROVIDER}data-credentials-stored="1"{/if} style="display:none;">
        <header class="iss-card__header"><i class="ti ti-world"></i> {$m.LABEL|escape}</header>
        <div class="iss-card__body">
            {if $m.DOCS_URL ne ''}
            <p class="iss-field__help"><a href="{$m.DOCS_URL|escape}" target="_blank" rel="noopener">{$PROVIDER_DOCS}</a></p>
            {/if}
            {if $m.CREDENTIALS_PROVIDER ne '' && $CREDENTIALS_PROVIDER eq $m.CREDENTIALS_PROVIDER}
            <div class="iss-alert iss-alert--info">
                <div class="iss-alert__content">{$CREDENTIALS_STORED}</div>
            </div>
            {/if}
            <div class="iss-formgrid">
                {foreach from=$m.FIELDS item=f}
                <div class="iss-field">
                    <label class="iss-field__label" for="{$f.NAME}">{$f.LABEL}{if $f.REQUIRED}&nbsp;<span class="iss-required">*</span>{/if}</label>
                    <span class="iss-field__control">
                    {if $f.TYPE eq 'select'}
                        <select class="form-select" name="{$f.NAME}" id="{$f.NAME}">
                        {foreach from=$f.OPTIONS item=opt}
                            <option value="{$opt.VALUE|escape}" {if $opt.VALUE eq $f.DEFAULT}selected{/if}>{$opt.LABEL|escape}</option>
                        {/foreach}
                        </select>
                    {elseif $f.TYPE eq 'checkbox'}
                        <input type="checkbox" name="{$f.NAME}" id="{$f.NAME}" value="1" {if $f.DEFAULT eq '1'}checked{/if}>
                    {elseif $f.TYPE eq 'number'}
                        <input class="form-control" type="number" name="{$f.NAME}" id="{$f.NAME}" value="{$f.DEFAULT|escape}"
                               {if $f.MIN ne ''}min="{$f.MIN}"{/if} {if $f.MAX ne ''}max="{$f.MAX}"{/if} inputmode="numeric">
                    {else}
                        <input class="form-control" type="{if $f.TYPE eq 'password'}password{else}text{/if}" name="{$f.NAME}" id="{$f.NAME}"
                               value="" placeholder="{$f.PLACEHOLDER|escape}" autocomplete="new-password"
                               {if $f.MAXLENGTH ne ''}maxlength="{$f.MAXLENGTH}"{/if} {if $f.REQUIRED}data-required="1"{/if}>
                    {/if}
                    </span>
                    {if $f.HELP ne ''}<p class="iss-field__help">{$f.HELP}</p>{/if}
                    <div class="invalid-feedback d-block" id="error_{$f.NAME}" style="display:none;"></div>
                </div>
                {/foreach}
            </div>
            {if $m.HOOK}
            <p class="iss-field__help">{$HOOK_NOTE}</p>
            {else}
            <p class="iss-field__help">{$CREDENTIALS_NOTE}</p>
            {/if}
        </div>
    </section>
    {/if}
    {/foreach}

    <section class="iss-card" data-method-group="http" {if $CURRENT_GROUP ne 'http'}style="display:none;"{/if}>
        <header class="iss-card__header"><i class="ti ti-list-check"></i> {$USAGE}</header>
        <div class="iss-card__body">
            <ol class="mb-2">
            {foreach from=$STEPS_HTTP item=STEP}
                <li>{$STEP}</li>
            {/foreach}
            </ol>
        </div>
    </section>

    <section class="iss-card" data-method-group="dns" {if $CURRENT_GROUP ne 'dns'}style="display:none;"{/if}>
        <header class="iss-card__header"><i class="ti ti-list-check"></i> {$USAGE}</header>
        <div class="iss-card__body">
            <ol class="mb-2">
            {foreach from=$STEPS_DNS item=STEP}
                <li>{$STEP}</li>
            {/foreach}
            </ol>
        </div>
    </section>

    <div class="iss-card">
        <div class="iss-card__body">
            <p class="iss-field__help">{$STEP_NOTE}</p>
        </div>
    </div>

    {if $HAS_CERT}
    <div class="iss-alert iss-alert--success">
        <div class="iss-alert__title">{$HASDATA}</div>
        <div class="iss-alert__content">{$DOMAIN}: {$valuedomain|escape} &mdash; {$EMAIL}: {$valueemail|escape}</div>
    </div>
    {/if}
</div>
</form>

<span id="cert_error" style="display:none;">{$CERT_ERROR}</span>
<span id="cert_required" style="display:none;">{$REQUIRED_FIELD}</span>
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

    var method = document.getElementById('method');
    var summary = document.getElementById('method_summary');
    var propagation = document.getElementById('propagation');
    var propagationField = document.getElementById('propagation_field');
    var propagationTouched = false;

    function activeOption() {
        return method.options[method.selectedIndex];
    }

    function updateMethod() {
        var option = activeOption();
        var group = option.getAttribute('data-group') || 'http';
        var selected = method.value;

        if (summary) summary.textContent = option.getAttribute('data-summary') || '';

        var cards = form.querySelectorAll('[data-method-fields]');
        for (var i = 0; i < cards.length; i++) {
            var show = cards[i].getAttribute('data-method-fields') === selected;
            cards[i].style.display = show ? '' : 'none';
            var inputs = cards[i].querySelectorAll('input, select');
            for (var j = 0; j < inputs.length; j++) {
                inputs[j].disabled = !show;
            }
        }

        var groups = form.querySelectorAll('[data-method-group]');
        for (var k = 0; k < groups.length; k++) {
            groups[k].style.display = (groups[k].getAttribute('data-method-group') === group) ? '' : 'none';
        }

        if (propagation && propagationField) {
            if (group === 'dns') {
                propagationField.style.display = '';
                propagation.disabled = false;
                if (!propagationTouched) {
                    propagation.value = option.getAttribute('data-propagation') || propagation.value;
                }
            } else {
                propagationField.style.display = 'none';
                propagation.disabled = true;
            }
        }
    }

    method.addEventListener('change', function () {
        propagationTouched = false;
        updateMethod();
    });
    if (propagation) {
        propagation.addEventListener('input', function () { propagationTouched = true; });
    }

    function setError(field, message) {
        var input = document.getElementById(field);
        var box = document.getElementById('error_' + field);
        if (!box) return;
        if (message) {
            if (input) input.classList.add('is-invalid');
            box.textContent = message;
            box.style.display = '';
        } else {
            if (input) input.classList.remove('is-invalid');
            box.textContent = '';
            box.style.display = 'none';
        }
    }

    function clearErrors() {
        var boxes = form.querySelectorAll('.invalid-feedback');
        for (var i = 0; i < boxes.length; i++) {
            boxes[i].style.display = 'none';
            boxes[i].textContent = '';
        }
        var invalid = form.querySelectorAll('.is-invalid');
        for (var j = 0; j < invalid.length; j++) invalid[j].classList.remove('is-invalid');
    }

    function showOutput(html) {
        var box = document.getElementById('output');
        document.getElementById('output_content').innerHTML = html || '';
        box.style.display = '';
    }

    function validate() {
        var ok = true;
        var required = document.getElementById('cert_required').textContent;
        var domain = document.getElementById('domain');
        var email = document.getElementById('email');

        if (!domain.value.trim()) { setError('domain', required); ok = false; }

        if (!email.value.trim()) { setError('email', required); ok = false; }

        var active = form.querySelector('[data-method-fields="' + method.value + '"]');
        if (active) {
            var fields = active.querySelectorAll('[data-required="1"]');
            var anyValue = false;
            for (var i = 0; i < fields.length; i++) {
                if (fields[i].value.trim()) anyValue = true;
            }
            // With stored credentials an empty form means "keep them".
            var stored = active.getAttribute('data-credentials-stored') === '1';
            if (!stored || anyValue) {
                for (var j = 0; j < fields.length; j++) {
                    if (!fields[j].value.trim()) {
                        setError(fields[j].name, required);
                        ok = false;
                    }
                }
            }
        }
        return ok;
    }

    function send(action) {
        var token = form.querySelector('input[name="issabel_csrf_token"]');
        var data;

        if (action === 'renew') {
            data = { renew: 1 };
        } else {
            if (!validate()) return;
            data = {
                method: method.value,
                domain: document.getElementById('domain').value,
                email: document.getElementById('email').value,
                reissue: document.getElementById('reissue').value
            };
            var staging = document.getElementById('staging');
            if (staging && staging.checked) data.staging = '--test-cert';
            if (propagation && !propagation.disabled) data.propagation = propagation.value;
            if (action === 'test') data.test = 1;

            var active = form.querySelector('[data-method-fields="' + method.value + '"]');
            if (active) {
                var inputs = active.querySelectorAll('input, select');
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    if (input.disabled || !input.name) continue;
                    if (input.type === 'checkbox') {
                        data[input.name] = input.checked ? '1' : '';
                    } else {
                        data[input.name] = input.value;
                    }
                }
            }
        }

        if (token) data.issabel_csrf_token = token.value;

        clearErrors();
        document.getElementById('cert_progress').style.display = '';
        document.getElementById('output').style.display = 'none';

        $.ajax({
            type: 'POST',
            url: 'modules/sec_letsencrypt/installcert.php',
            data: data,
            dataType: 'json'
        }).done(function (res) {
            if (res && res.errors) {
                for (var field in res.errors) {
                    if (Object.prototype.hasOwnProperty.call(res.errors, field)) {
                        setError(field, res.errors[field]);
                    }
                }
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
        if (!submitter) return;
        if (submitter.getAttribute('data-confirm') && !confirm(submitter.getAttribute('data-confirm'))) {
            return false;
        }
        if (submitter.name === 'renew') {
            send('renew');
        } else if (submitter.name === 'test') {
            send('test');
        } else {
            send('install');
        }
    });

    updateMethod();
})();
{/literal}
</script>
