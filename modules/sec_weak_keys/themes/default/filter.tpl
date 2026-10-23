<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="show" value="{$SHOW}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid iss-formgrid--3col">
            <div class="iss-field">
                <span class="iss-field__label">{$filter_field.LABEL}</span>
                <span class="iss-field__control">{$filter_field.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$filter_value.LABEL}</span>
                <span class="iss-field__control">{$filter_value.INPUT}</span>
            </div>
        </div>
    </section>
</div>
