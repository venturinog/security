<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint">{$MAP_HELP}</span>
        <div class="iss-form__buttons">
            <button class="button" type="button" id="map_refresh">{$REFRESH}</button>
        </div>
    </div>

    {if $GEOIP_AVAILABLE ne 'yes'}
    <div class="iss-alert iss-alert--error">
        <div class="iss-alert__title">{$GEOIP_UNAVAILABLE_TITLE}</div>
        <div class="iss-alert__content">{$GEOIP_ERROR|escape|nl2br}</div>
    </div>
    {/if}

    <div id="map_message" class="iss-alert" style="display:none;" role="status">
        <div class="iss-alert__content" id="map_message_text"></div>
    </div>

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-world"></i> {$MAP_TITLE}
            <span class="ms-2 small">
                <span class="iss-legend iss-legend--blocked">{$LEGEND_BLOCKED}</span>
                <span class="iss-legend iss-legend--pass">{$LEGEND_PASS}</span>
                <span class="iss-legend iss-legend--none">{$LEGEND_NONE}</span>
                <span class="iss-legend iss-legend--home">{$LEGEND_HOME}</span>
            </span>
        </header>
        <div class="iss-card__body">
            <div id="geomap" class="geo-map-wrap">
                {if $WORLD_SVG ne ''}{$WORLD_SVG}{else}<div class="iss-alert iss-alert--error"><div class="iss-alert__content">{$MAP_ERROR}</div></div>{/if}
            </div>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-md-6">
            <section class="iss-card">
                <header class="iss-card__header"><i class="ti ti-ban"></i> {$BLOCKED_TITLE}</header>
                <div class="iss-card__body">
                    <ul class="list-unstyled mb-0" id="blocked_list"><li class="iss-field__help">{$NO_DATA}</li></ul>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="iss-card">
                <header class="iss-card__header"><i class="ti ti-circle-check"></i> {$PASS_TITLE}</header>
                <div class="iss-card__body">
                    <ul class="list-unstyled mb-0" id="pass_list"><li class="iss-field__help">{$NO_DATA}</li></ul>
                </div>
            </section>
        </div>
    </div>

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-chart-bar"></i> {$CHART_TITLE}</header>
        <div class="iss-card__body">
            <div class="geo-chart-wrap"><canvas id="f2bchart"></canvas></div>
        </div>
    </section>
</div>

<script src="{$WEBPATH}themes/{$THEMENAME}/js/chart.umd.min.js"></script>
<script src="{$WEBPATH}themes/{$THEMENAME}/js/issabel-charts.js"></script>
<script>
var geoMapLang = "{$MAP_LANG|escape:'javascript'}";
var geoAvailable = ("{$GEOIP_AVAILABLE}" === "yes");
var geoMapTitle = "{$MAP_TITLE|escape:'javascript'}";
var geoText = {
    block: "{$LEGEND_BLOCKED|escape:'javascript'}",
    pass: "{$LEGEND_PASS|escape:'javascript'}",
    none: "{$LEGEND_NONE|escape:'javascript'}",
    blocked: "{$BLOCKED_TITLE|escape:'javascript'}",
    whitelisted: "{$PASS_TITLE|escape:'javascript'}",
    noData: "{$NO_DATA|escape:'javascript'}",
    chart: "{$CHART_TITLE|escape:'javascript'}"
};
{literal}
(function () {
    'use strict';

    var SVGNS = 'http://www.w3.org/2000/svg';
    var state = {};          // iso -> 'blocked' | 'pass'
    var chart = null;
    var displayNames = null;

    try {
        displayNames = new Intl.DisplayNames([geoMapLang], { type: 'region' });
    } catch (e) {
        displayNames = null;
    }

    function countryName(iso, fallback) {
        if (displayNames) {
            try {
                var name = displayNames.of(iso);
                if (name && name !== iso) return name;
            } catch (e) {}
        }
        return fallback || iso;
    }

    function showMessage(type, text) {
        var box = document.getElementById('map_message');
        var content = document.getElementById('map_message_text');
        content.textContent = text;
        box.className = 'iss-alert iss-alert--' + type;
        box.style.display = '';
    }

    // Los endpoints devuelven texto plano: lista de ISO separados por coma.
    function fetchList(param, cb) {
        $.get('index.php?menu=sec_geoip_map&rawmode=yes&' + param + '=1', function (data) {
            var list = String(data || '').split(',').filter(function (x) { return /^[A-Z]{2}$/.test(x); });
            cb(list);
        }).fail(function () { cb([]); });
    }

    function fetchAttacks(cb) {
        $.get('index.php?menu=sec_geoip_map&rawmode=yes&attacks=1', function (data) {
            try { cb(typeof data === 'string' ? JSON.parse(data) : data); } catch (e) { cb({}); }
        }).fail(function () { cb({}); });
    }

    function fetchHome(cb) {
        $.get('index.php?menu=sec_geoip_map&rawmode=yes&homecountry=1', function (data) {
            var iso = String(data || '').split(',')[0];
            cb(/^[A-Z]{2}$/.test(iso) ? iso : '');
        }).fail(function () { cb(''); });
    }

    function pathFor(iso) {
        return document.querySelector('.geo-country[data-iso="' + iso + '"]');
    }

    function setPathState(iso, value) {
        var el = pathFor(iso);
        if (!el) return;
        el.classList.remove('is-blocked', 'is-pass');
        if (value === 'blocked') el.classList.add('is-blocked');
        if (value === 'pass') el.classList.add('is-pass');
    }

    function localizeMap() {
        var paths = document.querySelectorAll('.geo-country');
        for (var i = 0; i < paths.length; i++) {
            var iso = paths[i].getAttribute('data-iso');
            var name = countryName(iso, paths[i].getAttribute('data-name'));
            paths[i].setAttribute('aria-label', name);
            paths[i].setAttribute('data-name', name);
            var title = paths[i].querySelector('title');
            if (!title) {
                title = document.createElementNS(SVGNS, 'title');
                paths[i].appendChild(title);
            }
            title.textContent = name;
        }
    }

    function renderList(id, isos, action) {
        var ul = document.getElementById(id);
        ul.innerHTML = '';
        if (!isos.length) {
            var empty = document.createElement('li');
            empty.className = 'iss-field__help';
            empty.textContent = geoText.noData;
            ul.appendChild(empty);
            return;
        }
        isos.sort().forEach(function (iso) {
            var li = document.createElement('li');
            li.className = 'd-flex justify-content-between align-items-center py-1';
            var name = document.createElement('span');
            name.textContent = countryName(iso, iso);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'button';
            btn.textContent = action === 'noneblock' ? geoText.none : geoText.block;
            btn.addEventListener('click', function () {
                mutate(action, iso);
            });
            li.appendChild(name);
            li.appendChild(btn);
            ul.appendChild(li);
        });
    }

    function refresh() {
        fetchList('blocked', function (blocked) {
            fetchList('pass', function (pass) {
                state = {};
                blocked.forEach(function (iso) { state[iso] = 'blocked'; });
                pass.forEach(function (iso) { state[iso] = 'pass'; });

                document.querySelectorAll('.geo-country').forEach(function (el) {
                    el.classList.remove('is-blocked', 'is-pass');
                });
                Object.keys(state).forEach(function (iso) { setPathState(iso, state[iso]); });

                renderList('blocked_list', blocked, 'noneblock');
                renderList('pass_list', pass, 'unblock');
            });
        });

        fetchAttacks(function (data) {
            var entries = Object.keys(data || {}).map(function (iso) {
                return [iso, parseInt(data[iso], 10) || 0];
            }).sort(function (a, b) { return b[1] - a[1]; }).slice(0, 10);

            var ctx = document.getElementById('f2bchart');
            if (!ctx || typeof Chart === 'undefined') return;
            if (chart) chart.destroy();

            var labels = entries.map(function (e) { return countryName(e[0], e[0]); });
            var values = entries.map(function (e) { return e[1]; });
            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels.length ? labels : [geoText.noData],
                    datasets: [{
                        label: geoText.chart,
                        data: values.length ? values : [0],
                        backgroundColor: (typeof IssabelCharts !== 'undefined')
                            ? IssabelCharts.palette(Math.max(1, values.length)) : undefined
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        });

        fetchHome(function (iso) {
            if (iso) {
                var el = pathFor(iso);
                if (el) el.classList.add('is-home');
            }
        });
    }

    function mutate(action, iso) {
        if (!geoAvailable) return;
        var payload = {};
        payload[action] = iso;
        $.ajax({
            type: 'POST',
            url: 'index.php?menu=sec_geoip_map&rawmode=yes',
            data: payload,
            dataType: 'json'
        }).done(function (res) {
            if (res && res.status === 'success') {
                showMessage('success', res.message);
            } else {
                showMessage('error', res && res.message ? res.message : geoText.noData);
            }
        }).fail(function () {
            showMessage('error', geoText.noData);
        }).always(function () {
            refresh();
        });
    }

    document.addEventListener('click', function (e) {
        if (!geoAvailable) return;
        var el = e.target.closest ? e.target.closest('.geo-country') : null;
        if (!el) return;
        var iso = el.getAttribute('data-iso');
        var current = state[iso] || 'none';
        if (current === 'blocked') mutate('noneblock', iso);
        else if (current === 'pass') mutate('unblock', iso);
        else mutate('block', iso);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var el = document.activeElement;
        if (el && el.classList && el.classList.contains('geo-country')) {
            e.preventDefault();
            el.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        }
    });

    var refreshBtn = document.getElementById('map_refresh');
    if (refreshBtn) refreshBtn.addEventListener('click', refresh);

    // Accesibilidad: los paths reciben foco con Tab.
    document.querySelectorAll('.geo-country').forEach(function (el) {
        el.setAttribute('tabindex', '0');
        el.setAttribute('role', 'button');
    });

    var rootSvg = document.querySelector('.geo-map');
    if (rootSvg) rootSvg.setAttribute('aria-label', geoMapTitle);

    localizeMap();
    refresh();
})();
{/literal}
</script>
