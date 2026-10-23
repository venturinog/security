<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="show" value="{$SHOW}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid iss-formgrid--3col">
            <div class="iss-field">
                <span class="iss-field__label">{$filter.LABEL}</span>
                <span class="iss-field__control">{$filter.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$busqueda.LABEL}</span>
                <span class="iss-field__control">{$busqueda.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__control">
                    <input class="button" type="submit" name="searchnext" value="{$SEARCHNEXT}">
                </span>
            </div>
        </div>
    </section>
</div>
{$ultima_busqueda.INPUT}
{$ultimo_offset.INPUT}
{$offset.INPUT}
