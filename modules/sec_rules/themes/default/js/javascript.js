/*
 * Mostrar/ocultar los campos del formulario de reglas según Traffic y
 * Protocol. Sin librerías: sólo clases del tema.
 */
function setDisplay(id, visible) {
    var el = document.getElementById(id);
    if (el) el.style.display = visible ? '' : 'none';
}

function showElementByTraffic() {
    var traffic = document.querySelector('[name="id_traffic"]');
    var value = traffic ? traffic.value : 'INPUT';
    setDisplay('id_interface_in', value === 'INPUT' || value === 'FORWARD');
    setDisplay('id_interface_out', value === 'OUTPUT' || value === 'FORWARD');
}

function showElementByProtocol() {
    var protocol = document.querySelector('[name="id_protocol"]');
    var value = protocol ? protocol.value : 'ALL';

    setDisplay('id_port_in', value === 'TCP' || value === 'UDP');
    setDisplay('id_port_out', value === 'TCP' || value === 'UDP');
    setDisplay('id_type_icmp', value === 'ICMP');
    setDisplay('id_id_ip', value === 'IP');
    setDisplay('id_established', value === 'STATE');
    setDisplay('id_related', value === 'STATE');
    setDisplay('id_geoipcontinents', value === 'GEOIP');
    setDisplay('id_geoipcountries', value === 'GEOIP');
}

/*
 * Acciones de la grilla de reglas: barra única, interruptores de activado y
 * reordenación por drag-and-drop / teclado. Sin librerías adicionales.
 */
jQuery(function ($) {
    'use strict';

    function fwAlert(type, message) {
        var $box = $('#fw_alert');
        if (!$box.length) return;
        if (!message) message = $box.data('error') || '';
        $box.attr('class', 'iss-alert iss-alert--' + type)
            .text(message)
            .show();
    }

    function fwSubmit(fields) {
        var $form = $('#fw_action_form');
        var $fields = $('#fw_action_fields').empty();
        if (!$form.length) return;

        Object.keys(fields).forEach(function (name) {
            var value = fields[name];
            if (name === 'del_x[]') {
                (value || []).forEach(function (id) {
                    $('<input>', { type: 'hidden', name: 'del_x[]', value: id }).appendTo($fields);
                });
            } else {
                $('<input>', { type: 'hidden', name: name, value: value }).appendTo($fields);
            }
        });
        $form.trigger('submit');
    }

    $('#fw_delete').on('click', function () {
        var ids = [];
        $('.fw-grid input[name="del_x[]"]:checked').each(function () {
            ids.push($(this).val());
        });
        if (!ids.length) {
            fwAlert('warning', $(this).data('empty'));
            return;
        }
        if (!confirmSubmit($(this).data('confirm'))) return;
        fwSubmit({ delete: 1, 'del_x[]': ids });
    });

    $('#fw_apply').on('click', function () {
        fwSubmit({ activatefirewall: 1 });
    });

    $('#fw_deactivate').on('click', function () {
        if (!confirmSubmit($(this).data('confirm'))) return;
        fwSubmit({ desactivatefirewall: 1 });
    });

    $('#fw_discard').on('click', function () {
        if (!confirmSubmit($(this).data('confirm'))) return;
        fwSubmit({ discardchanges: 1 });
    });

    $(document).on('change', '.fw-toggle', function () {
        var $toggle = $(this);
        var id = $toggle.data('id');
        var enabled = $toggle.is(':checked');
        var data = { menu: 'sec_rules', ajax: 1 };
        data[enabled ? 'activate' : 'deactivate'] = id;

        $.ajax({
            type: 'POST',
            url: 'index.php?menu=sec_rules&rawmode=yes',
            data: data,
            dataType: 'json'
        }).done(function (res) {
            if (!res || res.status !== 'success') {
                $toggle.prop('checked', !enabled);
                fwAlert('error', res && res.message ? res.message : '');
            } else {
                // Refresca el aviso de cambios pendientes y el botón Descartar.
                window.location.reload();
            }
        }).fail(function () {
            $toggle.prop('checked', !enabled);
            fwAlert('error', '');
        });
    });

    function fwSaveOrder($grid) {
        var ids = $grid.find('.fw-drag').map(function () {
            return $(this).data('id');
        }).get();
        var geoip = ($grid.attr('id') === 'geoip_grid') ? 1 : 0;

        $grid.addClass('fw-saving');
        $.ajax({
            type: 'POST',
            url: 'index.php?menu=sec_rules&rawmode=yes',
            data: {
                menu: 'sec_rules',
                rawmode: 'yes',
                reorder: 1,
                ajax: 1,
                geoip: geoip,
                ids: JSON.stringify(ids)
            },
            dataType: 'json'
        }).done(function (res) {
            if (res && res.status === 'success') {
                window.location.reload();
            } else {
                fwAlert('error', res && res.message ? res.message : '');
                setTimeout(function () { window.location.reload(); }, 1500);
            }
        }).fail(function () {
            fwAlert('error', '');
            setTimeout(function () { window.location.reload(); }, 1500);
        }).always(function () {
            $grid.removeClass('fw-saving');
        });
    }

    var dragRow = null;
    var dragGrid = null;

    $(document).on('dragstart', '.fw-drag', function (e) {
        dragRow = $(this).closest('tr');
        dragGrid = $(this).closest('.fw-grid');
        dragRow.addClass('fw-dragging');
        if (e.originalEvent.dataTransfer) {
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', String($(this).data('id')));
        }
    });

    $(document).on('dragend', '.fw-drag', function () {
        if (dragRow) dragRow.removeClass('fw-dragging');
        $('.fw-drop-above, .fw-drop-below').removeClass('fw-drop-above fw-drop-below');
        dragRow = null;
        dragGrid = null;
    });

    $(document).on('dragover', '.fw-grid tbody tr', function (e) {
        if (!dragRow || !dragGrid) return;
        if ($(this).closest('.fw-grid')[0] !== dragGrid[0]) return;
        e.preventDefault();
        if (e.originalEvent.dataTransfer) e.originalEvent.dataTransfer.dropEffect = 'move';

        var rect = this.getBoundingClientRect();
        var after = (e.originalEvent.clientY > rect.top + rect.height / 2);
        $(this).removeClass('fw-drop-above fw-drop-below')
               .addClass(after ? 'fw-drop-below' : 'fw-drop-above');
    });

    $(document).on('dragleave', '.fw-grid tbody tr', function () {
        $(this).removeClass('fw-drop-above fw-drop-below');
    });

    $(document).on('drop', '.fw-grid tbody tr', function (e) {
        if (!dragRow || !dragGrid) return;
        if ($(this).closest('.fw-grid')[0] !== dragGrid[0]) return;
        e.preventDefault();

        var $target = $(this);
        if ($target[0] === dragRow[0]) return;
        var after = $target.hasClass('fw-drop-below');
        $target.removeClass('fw-drop-above fw-drop-below');
        if (after) dragRow.insertAfter($target);
        else dragRow.insertBefore($target);

        var $grid = dragGrid;
        dragRow.removeClass('fw-dragging');
        dragRow = null;
        dragGrid = null;
        fwSaveOrder($grid);
    });

    $(document).on('keydown', '.fw-drag', function (e) {
        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
        e.preventDefault();
        var $row = $(this).closest('tr');
        if (e.key === 'ArrowUp') {
            if (!$row.prev('tr').length) return;
            $row.insertBefore($row.prev('tr'));
        } else {
            if (!$row.next('tr').length) return;
            $row.insertAfter($row.next('tr'));
        }
        fwSaveOrder($(this).closest('.fw-grid'));
    });
});
