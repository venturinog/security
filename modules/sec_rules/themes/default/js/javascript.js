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

    /*
     * Fuerza una navegación GET. Después de Aplicar (o cualquier acción que
     * use POST) el iframe queda en un documento POST y location.reload()
     * puede no refrescar o pedir reenvío de formulario. Un parámetro único
     * evita reutilizar esa misma navegación.
     */
    function fwReloadPage() {
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('_fw', Date.now());
            window.location.replace(url.toString());
        } catch (err) {
            window.location.replace(window.location.href);
        }
    }

    function fwApplyStart($button) {
        if ($button.data('applying')) return false;

        $button.data('applying', true)
            .prop('disabled', true)
            .attr('aria-busy', 'true')
            .find('i').removeClass('ti-check').addClass('ti-loader-2 fw-spin');

        var $overlay = $('#fw_apply_overlay');
        $('#fw_apply_status').text($overlay.data('status') || '');
        $overlay.prop('hidden', false);
        $('body').addClass('fw-applying');

        return true;
    }

    function fwApplyReset() {
        $('#fw_apply_overlay').prop('hidden', true);
        $('#fw_apply_status').text('');
        $('body').removeClass('fw-applying');

        var $button = $('#fw_apply');
        if (!$button.length) return;

        $button.data('applying', false)
            .prop('disabled', false)
            .removeAttr('aria-busy')
            .find('i').removeClass('ti-loader-2 fw-spin').addClass('ti-check');
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
        var $button = $(this);
        if ($button.data('applying')) return;
        if (!$('#fw_action_form').length) return;
        if (!fwApplyStart($button)) return;

        // Deja que el navegador pinte el overlay antes de enviar el POST.
        window.setTimeout(function () {
            fwSubmit({ activatefirewall: 1 });
        }, 50);
    });

    $(window).on('pageshow', fwApplyReset);

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
                fwReloadPage();
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
                fwReloadPage();
            } else {
                fwAlert('error', res && res.message ? res.message : '');
                setTimeout(fwReloadPage, 1500);
            }
        }).fail(function () {
            fwAlert('error', '');
            setTimeout(fwReloadPage, 1500);
        }).always(function () {
            $grid.removeClass('fw-saving');
        });
    }

    var dragRow = null;
    var dragGrid = null;
    var dragOrigin = null;
    var dragScrollEl = null;
    var dragScrollPrevBehavior = '';
    var dragPointerY = null;
    var dragScrollTimer = null;
    var DRAG_SCROLL_EDGE = 72;
    var DRAG_SCROLL_MAX = 18;

    function fwInitDragRows() {
        $('.fw-grid tbody tr').each(function () {
            var $row = $(this);
            if ($row.find('.fw-drag').length) {
                $row.attr('draggable', 'true');
            }
        });
    }

    function fwFindScrollParent($el) {
        var el = $el[0];
        while (el && el !== document.body && el !== document.documentElement) {
            var overflowY = window.getComputedStyle(el).overflowY;
            if ((overflowY === 'auto' || overflowY === 'scroll') && el.scrollHeight > el.clientHeight) {
                return el;
            }
            el = el.parentElement;
        }
        return document.scrollingElement || document.documentElement;
    }

    function fwDragScrollStop() {
        if (dragScrollTimer !== null) {
            window.clearInterval(dragScrollTimer);
            dragScrollTimer = null;
        }
        dragPointerY = null;
    }

    function fwDragScrollStep() {
        if (!dragRow || dragPointerY === null) return;

        var el = dragScrollEl || document.scrollingElement || document.documentElement;
        var isWindow = (el === document.scrollingElement || el === document.documentElement || el === document.body);
        var top = 0;
        var bottom = window.innerHeight;

        if (!isWindow) {
            var rect = el.getBoundingClientRect();
            top = rect.top;
            bottom = rect.bottom;
        }

        var delta = 0;
        if (dragPointerY < top + DRAG_SCROLL_EDGE) {
            delta = -Math.ceil(Math.min(1, (top + DRAG_SCROLL_EDGE - dragPointerY) / DRAG_SCROLL_EDGE) * DRAG_SCROLL_MAX);
        } else if (dragPointerY > bottom - DRAG_SCROLL_EDGE) {
            delta = Math.ceil(Math.min(1, (dragPointerY - (bottom - DRAG_SCROLL_EDGE)) / DRAG_SCROLL_EDGE) * DRAG_SCROLL_MAX);
        }

        if (delta !== 0) {
            if (isWindow) {
                window.scrollBy(0, delta);
            } else {
                el.scrollTop += delta;
            }
        }
    }

    function fwDragScrollStart() {
        if (dragScrollTimer !== null) return;
        dragScrollTimer = window.setInterval(fwDragScrollStep, 16);
    }

    function fwDragReset() {
        if (dragRow) dragRow.removeClass('fw-dragging');
        $('.fw-drop-above, .fw-drop-below').removeClass('fw-drop-above fw-drop-below');
        fwDragScrollStop();
        if (dragScrollEl) {
            dragScrollEl.style.scrollBehavior = dragScrollPrevBehavior;
        }
        dragRow = null;
        dragGrid = null;
        dragScrollEl = null;
        dragScrollPrevBehavior = '';
        dragOrigin = null;
    }

    fwInitDragRows();

    $(document).on('mousedown', '.fw-grid tbody tr', function (e) {
        dragOrigin = e.target;
    });

    $(document).on('mouseup', function () {
        dragOrigin = null;
    });

    $(document).on('dragstart', '.fw-grid tbody tr', function (e) {
        if ($(dragOrigin).closest('input, a, button, select, textarea, label, .fw-switch, .fw-edit').length) {
            e.preventDefault();
            return;
        }

        var $handle = $(this).find('.fw-drag');
        if (!$handle.length) {
            e.preventDefault();
            return;
        }

        dragRow = $(this);
        dragGrid = $(this).closest('.fw-grid');
        dragScrollEl = fwFindScrollParent(dragRow);
        dragScrollPrevBehavior = dragScrollEl.style.scrollBehavior || '';
        dragScrollEl.style.scrollBehavior = 'auto';
        dragRow.addClass('fw-dragging');

        if (e.originalEvent.dataTransfer) {
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', String($handle.data('id')));
            if (e.originalEvent.dataTransfer.setDragImage) {
                try {
                    e.originalEvent.dataTransfer.setDragImage(this, 16, 16);
                } catch (err) {}
            }
        }
    });

    $(document).on('dragend', '.fw-grid tbody tr', function () {
        fwDragReset();
    });

    $(document).on('dragend', function () {
        if (dragRow) fwDragReset();
    });

    $(document).on('dragover', function (e) {
        if (!dragRow) return;
        dragPointerY = e.originalEvent.clientY;
        fwDragScrollStart();
    });

    $(document).on('dragleave', function (e) {
        if (dragRow && e.originalEvent.relatedTarget === null) {
            fwDragScrollStop();
        }
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
        fwDragReset();
        fwSaveOrder($grid);
    });

    $(document).on('drop', function () {
        if (dragRow) fwDragReset();
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
