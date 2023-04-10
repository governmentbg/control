<?php $this->layout('main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<h3 class="ui left floated header">
    <i class="<?= $this->e($modules[$url->getSegment(0)]['icon']) ?> icon"></i>
    <span class="content"><?= $this->e($intl($url->getSegment(0, 'dashboard') . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <div class="ui fluid grid">
        <div class="ui stackable two column row crud-header">
            <div class="ui column">
                <?php if (count($table->getOperations())) : ?>
                <div class="row-operations
                    <?= count($table->getOperations()) ? ' ui clearing basic fitted segment' : '' ?>">
                    <?= $this->insert($views['actions'], [ 'actions' => $table->getOperations() ]) ?>
                </div>
                <?php endif ?>
            </div>
            <div class="ui right aligned column">
                <?= $this->insert($views['filters'], [ 'params' => $params ]) ?>
            </div>
        </div>
        <div class="ui one column row">
            <div class="ui column">
                <?php if ($filtered) : ?>
                    <div class="ui attached small icon message">
                        <i class="filter icon"></i>
                        <div class="content">
                            <a href="?" class="ui right floated icon orange labeled button">
                                <i class="ui remove icon"></i>
                                <?= $this->e($intl('crud.clearfilters')) ?>
                            </a>
                            <div class="header"><?= $this->e($intl('crud.dataisfiltered')) ?></div>
                            <p><?= $this->e($intl('crud.dataisfilteredlong')) ?></p>
                        </div>
                    </div>
                <?php endif ?>
                <?= $this->insert($views['table'], [
                    'table'      => $table,
                    'hidden'     => $hidden,
                    'filtered'   => $filtered,
                    'count'      => $count,
                    'paging'     => true,
                    'module'     => $module,
                    'params'     => $params,
                    'filters'    => $filters,
                    'views'      => [ 'row' => $views['table_row'] ]
                ])?>
            </div>
        </div>
    </div>
</div>
<div id="export-modal" class="ui small modal">
    <form method="post" class="ui form">
        <input type="hidden" name="columns" value="" />
        <div class="one field">
            <label><?= $this->e($intl('crud.export_format')) ?></label>
            <div class="ui required input">
                <select name="format" required>
                    <option value="xlsx">XLSX</option>
                    <option value="csv">CSV</option>
                    <option value="xml">XML</option>
                </select>
            </div>
        </div>
        <div class="field">
            <div class="ui checkbox">
                <input id="all_columns" type="checkbox" name="all_columns" value="1" />
                <label for="all_columns"><?= $this->e($intl('export.all_columns')) ?></label>
            </div>
        </div>
        <div class="field">
            <div class="ui checkbox">
                <input id="current_page_only" type="checkbox" name="current_page_only" value="1" />
                <label for="current_page_only"><?= $this->e($intl('export.current_page_only')) ?></label>
            </div>
        </div>
        <div class="ui section divider"></div>
        <div class="ui center aligned olive secondary segment">
            <button class="ui olive icon labeled submit button">
                <i class="download icon"></i> <?= $this->e($intl('common.export')) ?>
            </button>
            <div class="ui basic cancel button">
                <?= $this->e($intl('common.cancel')) ?>
            </div>
        </div>
    </form>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#export-modal .cancel').on('click', function (e) {
    $(this).closest('.modal').modal('hide');
});
if (window.parent && window.parent !== window.self) {
    var selectedPromise = {
        cbks : [],
        then : function (cb) { this.cbks.push(cb); },
        when : function (value) {
            this.cbks.forEach(function (v) {
                v.call(this, value);
            });
        }
    };
    $('body').addClass('no-menu').addClass('inside-modal');
    $('.row-operations').remove();
    var tbl = $('.table-read');
    if (!tbl.hasClass('empty-table')) {
        tbl
            .find('td:last-child')
                .empty()
                .append('<a href="#" class="ui mini green labeled icon button row-pick">'+
                '<i class="ui check icon"></i> <?= $this->e($intl('fields.module.pickrow')) ?></a>')
                .end()
            .on('click', '.row-pick', function (e) {
                e.preventDefault();
                selectedPromise.when({
                    'id'   : $(this).closest('tr').data('id'),
                    'html' : $(this).closest('tr'),
                    'head' : $(this).closest('table').children('thead')
                });
                $(this).closest('tr').addClass('positive').find('.button').remove();
            });
        $(window).on('load', function () {
            if (JSON.parse('<?= json_encode($created) ?>') && $('.table-read tbody tr').length === 1) {
                setTimeout(function () {
                    $('.table-read tbody tr .row-pick').click();
                }, 100);
            }
        });
    }
}
$('.export-button')
    .on('click', function (e) {
        e.preventDefault();
        var columns = [];
        $('.table-read th:visible').each(function () {
            if (this.getAttribute('data-column')) {
                columns.push(this.getAttribute('data-column'));
            }
        });
        $('#export-modal').find('[name="columns"]').val(columns.join(',')).end().modal('show');
    })
$('#export-modal').find('form').on('submit', function () { $(this).closest('.modal').modal('hide'); });
</script>
<style nonce="<?= $this->e($cspNonce) ?>">
#export-modal form { padding:20px; }
.crud-header { border-bottom:1px solid #ebebeb !important;  }
.no-menu { background:white !important; }
.no-menu .menu-top,
.no-menu .menu-side { display:none !important; }
.no-menu .content { padding:0 !important; }
.no-menu .content > .segment { box-shadow:none !important; border:0 !important; }
.filters-form, .filters-column { display:inline-block; }
</style>
