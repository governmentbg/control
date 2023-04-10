<?php
$id = 'table_' . md5(microtime() . rand(0, 100));
// foreach ($fields as $k => $v) {
//     if (is_string($v)) {
//         $fields[$k] = [ 'label' => $v ];
//     }
// }
?>

<?php $activeFilters = []; ?>
<?php foreach ($table->getColumns() as $name => $column) : ?>
    <?php if ($column->hasFilter()) : ?>
        <div id="<?= $id . '_' . $this->e(str_replace('.', '__', $name)) ?>"
            class="ui flowing popup bottom left transition hidden filter-popup">
            <?php
            $form = $column->getFilter()->populate($params);
            $fields = \vakata\collection\Collection::from($form->getFields())
                ->map(function ($v) {
                    return explode('[', $v->getName(''))[0];
                })
                ->toArray();
            $active = count(array_intersect(array_keys($params), $fields)) > 0;
            if ($active) {
                $activeFilters[] = $name;
            }
            echo $this->insert('crud::index_table_filter', [
                'form'   => $column->getFilter(),
                'data'   => $params,
                'fields' => $fields,
                'clear'  => $active
            ]);
            ?>
        </div>
    <?php endif ?>
<?php endforeach ?>
<div id="<?= $id . '__column_chooser' ?>"
    class="ui column-chooser flowing popup bottom left transition hidden filter-popup">
    <?php
    $form = new \helpers\html\Form();
    array_map(function ($column) use ($module, $form) {
        $form->addField(
            new \helpers\html\Field(
                'checkbox',
                [
                    'name'  => $this->e('column_chooser_' . $column->getName()),
                    'value' => '1'
                ],
                [ 'label' => $module . '.columns.' . $column->getName(), 'nobr' => true ]
            )
        );
    }, $table->getColumns());
    $col = array_map(function ($column) {
        return 'column_chooser_' . $column->getName();
    }, $table->getColumns());
    $cnt = floor(count($col) / 6) + 1;
    if (count($col) % $cnt !== 0) {
        $col = array_pad($col, count($col) + ($cnt - count($col) % $cnt), '');
    }
    $col = array_chunk($col, $cnt);
    $form->setLayout($col);
    echo $this->insert('common/form', [
        'form' => $form
    ]);
    ?>
</div>

<table 
    class="ui <?= $this->e($table->getAttr('class')) ?> main-table <?= !$count ? 'empty-table' : '' ?> single line table"
    id="<?= $id ?>">
    <thead>
        <tr>
            <?php foreach ($table->getColumns() as $name => $column) : ?>
                <th data-column="<?= $this->e($name) ?>"
                    class="<?= $column->hasFilter() ? 'has-filter' : '' ?>">
                    <?php if ($column->hasFilter()) : ?>
                        <button
                            data-popup="<?= $id . '_' . $this->e(str_replace('.', '__', $name)) ?>"
                            class="filter ui right floated <?= in_array($name, $activeFilters) ? 'orange' : '' ?>
                                basic mini compact icon button"
                        >
                            <i class="filter icon"></i>
                        </button>
                    <?php endif ?>
                    <?php if ($column->isSortable()) : ?>
                        <a href="?<?= $this->e(http_build_query(array_merge(
                            $params,
                            [
                                'o' => $name,
                                'd' => (isset($params['o']) &&
                                    $params['o'] === $name &&
                                    isset($params['d']) &&
                                    (int)$params['d'] === 0 ?
                                        1 : 0
                                )
                            ]
                        ))) ?>">
                            <?= $this->e($intl($module . '.columns.' . $name)) ?>
                            <?php if (isset($params['o']) && $params['o'] === $name) : ?>
                                <i class="caret <?= isset($params['d']) && (int)$params['d'] === 1 ? 'down' : 'up' ?> 
                                    icon"></i>
                            <?php endif; ?>
                        </a>
                    <?php else : ?>
                        <?= $this->e($intl($module . '.columns.' . $name)) ?>
                    <?php endif ?>
                </th>
            <?php endforeach; ?>
            <th class="single line operations">
                <button data-popup="<?= $id . '__column_chooser' ?>" class="filter ui basic mini compact icon button">
                    <i class="settings icon"></i>
                </button>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$count) : ?>
            <tr>
                <td colspan="<?php echo (count($table->getColumns()) + 1); ?>" class="center aligned">
                    <div class="ui message">
                        <?= $this->e($intl('common.table.norecords')) ?>
                        <?php if ($filtered) : ?>
                            <?= $this->e($intl('common.table.matching')) ?><br /><br />
                            <a href="?" class="ui tiny labeled icon teal button">
                                <i class="remove icon"></i>
                                <?= $this->e($intl('common.table.clearfilters')) ?>
                            </a>
                        <?php endif ?>
                    </div>
                </td>
            </tr>
        <?php else : ?>
            <?php foreach ($table->getRows() as $row) : ?>
                <?= $this->insert($views['row'], [ 'row' => $row, 'columns' => $table->getColumns() ]); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <?php if ($paging && $count) : ?>
        <tfoot>
            <tr>
                <th colspan="<?php echo (count($table->getColumns()) + 1); ?>" class="center aligned">
                    <?php if ($count > $params['l']) : ?>
                        <div class="ui center small pagination menu">
                            <a 
                                href="?<?php
                                    echo $this->e(http_build_query(array_merge($params, ['p' => $params['p'] - 1])))
                                ?>"
                                class="icon <?= $this->e(($params['p'] <= 1 ? 'disabled' : '')) ?> item"
                            ><i class="left chevron icon"></i></a>
                            <?php
                                $t_rcrd = (int)$count;
                                $p_page = (int)$params['l'];
                                $s_page = 1;
                                $e_page = (int)ceil($count / $params['l']);
                                $c_page = (int)$params['p'];
                                $s_rcrd = (max($c_page - 1, 0) * $p_page) + 1;
                                $e_rcrd = $s_rcrd + count($table->getRows()) - 1;
                            for ($i = 1; $i <= $e_page; $i++) {
                                if ($e_page > 16 && $i > 3 && $i < $e_page - 3) {
                                    if ($c_page > 7 && $c_page < $e_page - 6) {
                                        if ($i < $c_page - 3) {
                                            echo '<span class="disabled item">&hellip;</span>';
                                            $i = $c_page - 3;
                                            continue;
                                        }
                                        if ($i > $c_page + 2) {
                                            echo '<span class="disabled item">&hellip;</span>';
                                            $i = $e_page - 3;
                                            continue;
                                        }
                                    } else {
                                        if ($i == 9) {
                                            echo '<span class="disabled item">&hellip;</span>';
                                            $i = max($e_page - 8, 10);
                                            continue;
                                        }
                                    }
                                }
                                echo '<a href="?' . $this->e(
                                    http_build_query(array_merge($params, ['p' => $i ]))
                                ) . '" ';
                                echo 'class="paging-number ' . $this->e($params['p'] == $i ? 'active' : '') . ' item">';
                                echo $i . '</a>';
                            }
                            ?>

                            <a 
                                href="?<?= $this->e(http_build_query(array_merge($params, ['p' => $c_page + 1 ]))) ?>"
                                class="icon <?= $this->e($c_page >= $e_page ? 'disabled' : '') ?> item"
                            ><i class="right chevron icon"></i></a>
                        </div>
                    <?php endif ?>
                    <small class="paging-stats">
                        <?=
                            $this->e(
                                $intl(
                                    'common.table.records',
                                    [
                                        'beg' => ($params['p'] - 1) * $params['l'] + 1,
                                        'end' => min($count, ($params['p'] - 1) * $params['l'] + $params['l']),
                                        'total' => $count
                                    ]
                                )
                            )
                        ?>
                    </small>
                </th>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>

<div id="save-filter-modal" class="ui small modal">
    <div class="ui form">
        <div class="one field">
            <label><?= $this->e($intl('crud.save_filter_name')) ?></label>
            <div class="ui required input">
                <input type="text" required />
            </div>
        </div>
        <div class="ui section divider"></div>
        <div class="ui center aligned teal secondary segment">
            <button class="ui teal icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
            <div class="ui basic cancel button">
                <?= $this->e($intl('common.cancel')) ?>
            </div>
        </div>
    </div>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
.paging-stats { display:block; color:gray; padding-top:1rem; }
#save-filter-modal .form { padding:20px; }
#<?= $id . '__column_chooser' ?>.filter-popup { text-align:left !important; }
#<?= $id . '__column_chooser' ?> i.ellipsis { cursor:move; float:right; }
.column-chooser { padding-bottom:1.8rem !important; }
.column-chooser .row { padding-bottom:0 !important; }
.filter-popup > form { min-width:240px !important; }
#<?= $id ?> td:not(:last-child) { cursor:pointer; }
.jquery-dtpckr-popup { z-index:999999 !important; }
.quick-filter { float:right; color:silver; }
.quick-filter:hover { color:black; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
(function () {
    $('#save-filter-modal .cancel').on('click', function (e) {
        $(this).closest('.modal').modal('hide');
    });
    var checks = $('#<?= $id ?>__column_chooser')
        .find(':checkbox').change(function () {
            var hidden = [];
            $(this).closest('.column-chooser').find(':checkbox').each(function (i) {
                var column = $(this).prev().attr('name').replace('column_chooser_', '');
                if (!$(this).prop('checked')) {
                    hidden.push(column);
                }
                $('#<?= $id ?>').find('td[data-column="'+column+'"], th[data-column="'+column+'"]')
                        .css('display', $(this).prop('checked') ? 'table-cell' : 'none');
            });
            window.localStorage.setItem(window.location.pathname + '::column_chooser', JSON.stringify(hidden));
            $('#<?= $id ?>').find('th:last-child')
                .find('button.filter')[hidden.length ? 'addClass' : 'removeClass']('orange')
        });
    var tmp = window.localStorage.getItem(window.location.pathname + '::column_chooser');
    if (!tmp) {
        tmp = '<?= json_encode($hidden) ?>';
    }
    if (tmp = JSON.parse(tmp)) {
        tmp.forEach(function (v) {
            $('#<?= $id ?>__column_chooser').find('[name="column_chooser_'+v+'"]').val('0')
                .next().prop('checked', false);
        });
        checks.eq(0).change();
    }
    var table = $('#<?= $id ?>');
    table
        .on('click', 'td', function (e) {
            if (e.target.tagName === 'TD') {
                var href = $(this).closest('tr').children('td').last().find('.button').not('.skip').eq(0).attr('href');
                if (href) {
                    window.location = href;
                }

            }
        })
        .on('click', '.state-button', function (e) {
            e.preventDefault();
            var buttons = $(this).parent().children('.state-button');
            $.post(
                window.location.pathname.trim('/') + '/patch/' + $(this).closest('tr').data('id'),
                { column : $(this).data('field'), value : $(this).data('value') }
            )
                .done(function (data) {
                    buttons.hide().each(function () {
                        if ($(this).data('value') != data.value) {
                            $(this).show();
                        }
                    });
                })
                .fail(function () {
                    window.location.reload();
                });
        })
        .find('th > .filter').each(function () {
            $(this).popup({
                on: 'click',
                position: 'bottom right',
                popup : $('#' + $(this).data('popup'))
            });
        }).end()
        .find('.button.blank').attr('target', '_blank');

    var filters = $('#<?= $id ?>').closest('.grid').find('.filters-column');
    if (filters.length) {
        var moduleName = JSON.parse('<?= json_encode($module) ?>');
        var clientFilters = localStorage.getItem(moduleName + '.filters');
        var serverFilters = JSON.parse('<?= json_encode($filters) ?>');
        if (clientFilters) {
            clientFilters = JSON.parse(clientFilters);
        }
        if (!clientFilters) {
            clientFilters = [];
        }
        var isFiltered = $('#<?= $id ?>').prevAll('.attached.message').length > 0;
        var defaultFilter = JSON.parse('<?= json_encode(urldecode(http_build_query($params))) ?>')
            .split('&')
            .map(function (value) {
                return decodeURI(value);
            })
            .sort()
            .filter(function (value) {
                return ['p', 'l', 'd', 'o'].indexOf(value.split('=')[0]) === -1;
            });
        var currentFilter = window.location.search.substring(1).split('&')
            .map(function (value) {
                return decodeURI(value);
            })
            .sort()
            .filter(function (value) {
                return ['p', 'l', 'd', 'o'].indexOf(value.split('=')[0]) === -1;
            });
        currentFilter = $.unique(currentFilter.concat(defaultFilter).sort())
            .filter(function (value) { return !!value; });
        if (isFiltered) {
            var isClientFilter = false;
            var isServerFilter = false;
            var clientSearch = null;
            clientFilters.map(function (v) {
                var filter = v.search.substring(1).split('&')
                    .map(function (value) {
                        return decodeURI(value);
                    })
                    .sort()
                    .filter(function (value) {
                        return ['p', 'l', 'd', 'o'].indexOf(value.split('=')[0]) === -1;
                    });
                if (filter.join('&') === currentFilter.join('&')) {
                    isClientFilter = true;
                    clientSearch = v.search;
                    v.selected = true;
                }
                return v;
            });
            serverFilters.map(function (v) {
                var filter = v.search.substring(1).split('&')
                    .map(function (value) {
                        return decodeURI(value);
                    })
                    .sort()
                    .filter(function (value) {
                        return ['p', 'l', 'd', 'o'].indexOf(value.split('=')[0]) === -1;
                    });
                if (filter.join('&') === currentFilter.join('&')) {
                    isServerFilter = true;
                    clientSearch = v.search;
                    v.selected = true;
                }
                return v;
            });
            if (!isClientFilter && !isServerFilter) {
                $('#<?= $id ?>').prevAll('.attached.message').find('.button')
                    .after('<button class="ui save-filter-button teal right floated labeled icon button">'+
                    '<i class="save icon"></i> <?= $this->e($intl("crud.save_filter")) ?></button>');
                $('#<?= $id ?>').prevAll('.attached.message').find('.save-filter-button').on('click', function (e) {
                    e.preventDefault();
                    $('#save-filter-modal').modal('show');
                });
                $('#save-filter-modal').find('.submit').click(function (e) {
                    e.preventDefault();
                    var input = $(this).closest('.form').find('input[type=text]');
                    if (!input.val()) {
                        input.closest('.field').addClass('error');
                        return;
                    }
                    clientFilters.push({
                        name : input.val(),
                        search : window.location.search
                    });
                    localStorage.setItem(moduleName + '.filters', JSON.stringify(clientFilters));
                    window.location.reload();
                })
            }
            if (isClientFilter) {
                $('#<?= $id ?>').prevAll('.attached.message').find('.button')
                    .after('<button class="ui remove-filter-button red right floated labeled icon button">'+
                    '<i class="trash icon"></i> <?= $this->e($intl("crud.remove_filter")) ?></button>');
                $('#<?= $id ?>').prevAll('.attached.message').find('.remove-filter-button').on('click', function (e) {
                    e.preventDefault();
                    clientFilters = clientFilters.filter(function (v) {
                        return v.search !== clientSearch;
                    })
                    localStorage.setItem(moduleName + '.filters', JSON.stringify(clientFilters));
                    window.location.reload();
                });
            }
        }
        if (clientFilters.length || serverFilters.length) {
            var select = $('<select class="search">');
            select.append('<option value=""><?= $this->e($intl("crud.choose_filter")) ?></option>');
            select.append(serverFilters.map(function (v) {
                return $('<option>').text(v.name).attr('value', v.search);
            }));
            select.append(clientFilters.map(function (v) {
                return $('<option>').text(v.name).attr('value', v.search);
            }));
            if (isClientFilter) {
                select.val(clientSearch);
            }
            if (isServerFilter) {
                select.val(clientSearch);
            }
            filters.prepend(select);
            select.change(function () {
                window.location.href = $(this).val() || '?';
            });
            select.dropdown();
        }
    }
    var uri = URI(window.location.href.toString()),
        que = uri.search(true);
    $('#<?= $id ?>')
        .on('click', '.quick-filter', function (e) {
            var tmp = URI(window.location.href.toString())
                .removeQuery($(this).data('column'))
                .removeQuery(new RegExp('^' + $(this).data('column').replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&") + '\\['));
            if ($(this).hasClass('filter')) {
                tmp.addQuery($(this).data('column'), $(this).data('value'));
            }
            window.location = tmp;
        })
        .find('.quick-filter').each(function () {
            if (que[$(this).data('column')] === $(this).data('value').toString()) {
                $(this).toggleClass('filter remove')
            }
        });

    function orderColumns(columns) {
        var order = [];
        var table = $('#<?= $id ?>');
        var thead = table.find('thead');
        var trows = table.find('tr');
        var index;
        columns.reverse().forEach(function (v) {
            var th = thead.find('th[data-column="'+v+'"]');
            if (th.length) {
                index = th.index();
                trows.each(function () {
                    $(this).children('td, th').eq(index).prependTo(this);
                });
            }
        });
        table.find('th > .filter').each(function () {
            $(this).popup({
                on: 'click',
                position: 'bottom right',
                popup : $('#' + $(this).data('popup'))
            });
        });
    }
    // columns drag'n'drop
    var isdrg = 0,
        initx = false,
        inity = false,
        ofstx = false,
        ofsty = false,
        holdr = false,
        elmnt = false;
        container = $('#<?= $id ?>__column_chooser');
    container
        .on('mousedown', '.row', function (e) {
            elmnt = $(this);
            try {
                e.currentTarget.unselectable = "on";
                e.currentTarget.onselectstart = function () { return false; };
                if(e.currentTarget.style) { e.currentTarget.style.MozUserSelect = "none"; }
            } catch (err) { }
            holdr = false;
            initx = e.pageX;
            inity = e.pageY;
            elmnt = $(this);
            var o = elmnt.offset();
            ofstx = e.pageX - o.left;
            ofsty = e.pageY - o.top;
            isdrg = 1;
        });
    $('body')
        .on('mousemove', function (e) {
            switch (isdrg) {
                case 0:
                    return;
                case 1:
                    if(Math.abs(e.pageX - initx) > 5 || Math.abs(e.pageY - inity)) {
                        isdrg = 2;
                    }
                    break;
                case 2:
                    var targt = $(e.target).closest('.row'), i, j;
                    if(targt.length && targt[0] !== elmnt[0] && targt.closest('#<?= $id ?>__column_chooser').length) {
                        i = targt.index();
                        j = elmnt.index();
                        if(i != j) {
                            targt[i>j?'after':'before'](elmnt);
                        }
                    }
                    break;
            }
        })
        .on('mouseup', function () {
            if (isdrg) {
                if (isdrg == 2) {
                    // update table
                    var columns = [];
                    container.find(':checkbox').each(function () {
                        var column = $(this).prev().attr('name').replace('column_chooser_', '');
                        columns.push(column);
                    });
                    window.localStorage.setItem(
                        window.location.pathname + '::column_chooser_order',
                        JSON.stringify(columns)
                    );
                    orderColumns(columns);
                }
                isdrg = 0;
                initx = false;
                inity = false;
                elmnt = false;
                holdr = false;
            }
        });
    var columns = window.localStorage.getItem(window.location.pathname + '::column_chooser_order');
    if (columns && (columns = JSON.parse(columns))) {
        columns.reverse().forEach(function (v) {
            container.find('input[name="column_chooser_'+v+'"]').closest('.row').prependTo(container.find('.grid'));
        });
        orderColumns(columns.reverse());
    }
    container.find('.field')
        .prepend('<i class="ui right floated vertical ellipsis icon"></i>');

    $(window).on('resize', function () {
        if ($(window).width() > 767 && $('.table-read').outerWidth() > $('.table-read').closest('.segment').width()) {
            $('.table-read').closest('.segment').addClass('fixed-content')
                .prepend($('.table-read').closest('.segment').closest('.content').children('.ui.message'));
        } else {
            $('.table-read').closest('.segment').removeClass('fixed-content');
        }
    }).trigger('resize');
}());
</script>
