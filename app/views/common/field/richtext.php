<?php if ($field->getOption('textOnly')) : ?>
    <?php if (strlen($field->getOption('label', ''))) : ?>
        <label>
            <?php if ($field->getOption('tooltip')) : ?>
                <span 
                    data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                    data-inverted="">
                    <i class="question circle icon"></i>
                </span>
            <?php endif ?>
            <?= $this->e($intl($field->getOption('label'))) ?>
        </label>
    <?php endif ?>
    <div><?= $field->getValue('') ?></div>
<?php else : ?>
    <?php
    if (!$field->hasAttr('id')) {
        $field->setAttr('id', 'richtext_' . md5($field->getName('') . microtime() . rand(0, 100)));
    }
    $id = $field->getAttr('id');
    $field->addClass('richtext richtext-waiting');
    $disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
    $field->setAttr('data-tinymce', $field->getOptions());
    include __DIR__ . '/textarea.php';
    ?>
    <div id="upload_<?= $this->e($id) ?>"
        data-plupload='<?= json_encode([ 'url' => $url('upload'), 'chunksize' => '250kb' ]); ?>'></div>
    <div id="modal_<?= $this->e($id) ?>" class="ui fullscreen modal"></div>
    <script nonce="<?= $this->e($cspNonce) ?>">
    setTimeout(function () {
        var upload_cb = null;
        $(function () {
            var upload = $.plupload.create($('#upload_<?= $this->e($id) ?>')[0]);
            upload.bind('PostInit', function(up, params) {
                setTimeout(function () { up.refresh(); }, 100);
            });
            upload.bind('FilesAdded', function(up, files) {
                $.each(files, function (i, v) {
                    $('.tox-browse-url').prev().children('input')
                        .css({
                            'backgroundImage' : 'linear-gradient(#ebebeb, #ebebeb)',
                            'backgroundSize' : '1% 100%',
                            'backgroundRepeat' : 'no-repeat',
                            'backgroundPosition' : 'left top'
                        })
                        .val(files[i].name + ' ...').end()
                        .addClass('mce-disabled');
                });
                setTimeout(function () { up.refresh(); up.start(); }, 100);
            });
            upload.bind('UploadProgress', function(up, file) {
                $('.tox-browse-url').prev().children('input').css('backgroundSize', file.percent + '% 100%');
            });
            upload.bind('FileUploaded', function(a,b,c) {
                c = JSON.parse(c.response);
                if (parseInt(c.id,10)) {
                    $('.tox-browse-url').prev().children('input')
                        .css({ 'backgroundImage' : 'none', 'backgroundSize' : '100% 100%' })
                        .val(c.url).end()
                        .removeClass('mce-disabled');
                    if (upload_cb) {
                        upload_cb(c.url, {});
                        upload_cb = null;
                    }
                } else {
                    alert("<?= $this->e($intl('common.error.tryagain')) ?>");
                }
                a.refresh();
            });
            upload.bind('Error', function(up, e) {
                $('.mce-window .mce-i-browse').parent().parent().prev().prev()
                    .css({ 'backgroundImage' : 'none', 'backgroundSize' : '100% 100%' }).end()
                    .removeClass('mce-disabled');
                alert("<?= $this->e($intl('common.fields.richtext.uploaderror')) ?>: " + "\n\n" + e.message);
                up.refresh();
            });
        });

        /*
        tinymce.PluginManager.add('add_module', function (editor) {
            editor.addButton('add_module_doc', {
                text: '<?= $this->e($intl('common.fields.richtext.document')) ?>',
                icon: 'browse',
                stateSelector : '.mceNonEditable.doc',
                onclick: function() {
                    var node = editor.selection.getNode(),
                        val = '',
                        is_replace = false;
                    if(node && node.tagName === "DIV" && node.className.indexOf("mceNonEditable") !== -1 &&
                        node.getAttribute("data-module") === "documents") {
                        val = node.getAttribute("data-value") || '';
                        is_replace = true;
                    }

                    $('#modal_<?= $this->e($id) ?>')
                        .html(
                            '<iframe src="" width="100%" height="80vh" class="module-field-iframe">'+
                            '</iframe>'
                        )
                        .find('iframe')
                            .off('load')
                            .on('load', function () {
                                var iframe = this.contentWindow;
                                if (iframe.selectedPromise) {
                                    iframe.selectedPromise.then(function (value) {
                                        if(is_replace) {
                                            editor.execCommand('delete', false);
                                            editor.nodeChanged();
                                            editor.focus();
                                        }
                                        editor.insertContent(
                                            '<div class="mceNonEditable doc" data-module="documents" '+
                                            'data-value=\'' + JSON.stringify(value.id) + '\' '+
                                            'sty'+'le="background:#e5e5e5; padding:20px;">' +
                                                value.html.children().eq(0).html() +
                                            '</div><p></p>');
                                        $('#modal_<?= $this->e($id) ?>').modal('hide');
                                    });
                                } else {
                                    $('#modal_<?= $this->e($id) ?>').modal('hide');
                                }
                                if (val) {
                                    iframe.$('.table-read > tbody > tr').each(function () {
                                        if (iframe.JSON.stringify($(this).data('id')) == val) {
                                            $(this).addClass('positive').find('td').eq(-1).empty();
                                        }
                                    });
                                }
                            })
                            .attr('src', "<?= $url('documents') ?>")
                            .end()
                        .modal('show');
                }
            });
        });
        */

        var create = function () {
            var rw = $('.richtext-waiting');
            rw.each(function () {
                var obj = $(this);
                if (obj.is(':visible')) {
                    obj.removeClass('richtext-waiting');
                    (function (id, config) {
                        tinymce.baseURL = "<?= $url('assets/static/tinymce') ?>";
                        tinymce.init($.extend({
                            language : "<?= $this->e($intl('_locale.code.long')) ?>",
                            language_url : "<?= $url('assets/tinymce_langs/' .
                                ($req->getAttribute('locale') ?? 'en') . '.js') ?>",
                            selector : '#' + id,
                            setup: function (editor) {
                                editor.on('change', function () {
                                    editor.save();
                                });
                            },
                            browser_spellcheck: true,
                            contextmenu: false,
                            paste_data_images : true,
                            plugins: [
                                "advlist autolink link image imagetools lists charmap hr anchor pagebreak",
                                "searchreplace visualchars code insertdatetime media nonbreaking",
                                "save table paste noneditable "// add_module" // modules
                            ],
                            images_upload_handler: function (blobInfo, success, failure) {
                                var xhr = new XMLHttpRequest(),
                                    formData = new FormData();
                                xhr.open('POST', "<?= $url('upload') ?>");
                                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                                xhr.onload = function() {
                                    var json;
                                    if (xhr.status != 200) {
                                        return failure('HTTP Error: ' + xhr.status);
                                    }
                                    json = JSON.parse(xhr.responseText);

                                    if (!json || typeof json.url != 'string') {
                                    failure('Invalid JSON: ' + xhr.responseText);
                                    } else {
                                    success(json.url);
                                    }
                                };
                                formData.append('file', blobInfo.blob(), blobInfo.filename());
                                xhr.send(formData);
                            },
                            menubar: <?= (isset($disabled) && $disabled) || (isset($readonly) && $readonly) ?
                                'false' : 'true' ?>,
                            statusbar: <?= (isset($disabled) && $disabled) || (isset($readonly) && $readonly) ?
                                'false' : 'true' ?>,
                            menu : { // this is the complete default configuration
                                file   : {title : 'File'  , items : 'newdocument'},
                                edit   : {title : 'Edit'  , items : 'undo redo | cut copy paste pastetext | selectall'},
                                insert : {title : 'Insert', items : 'link media | template hr'},
                                view   : {title : 'View'  , items : 'visualaid'},
                                format : {
                                    title : 'Format',
                                    items : 'bold italic underline strikethrough superscript subscript | formats | ' +
                                        'removeformat'
                                },
                                table  : {title : 'Table' ,
                                    items : 'inserttable tableprops deletetable | cell row column'},
                                tools  : {title : 'Tools' ,
                                    items : 'spellchecker code'},
                            },
                            toolbar: <?= (isset($disabled) && $disabled) || (isset($readonly) && $readonly) ?
                                'true' : 'false' ?> ?
                                [] :
                                [
                                    "undo redo | insert bold italic underline | "+
                                    "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | "+
                                    "link image | forecolor backcolor | searchreplace | code" // | add_module_doc"
                                ],
                            save_enablewhendirty: false,
                            image_advtab : true,
                            document_base_url: "<?= $url('') ?>",
                            relative_urls: false,
                            file_picker_callback: function(callback, value, meta) {
                                $('#upload_' + id).click();
                                upload_cb = callback;
                            },
                            height: '400px',
                            readonly : <?= $disabled ? 'true' : 'false' ?>
                        }, config));
                    }(obj[0].id, obj.data('tinymce') || {}));
                }
            });
            if (window._vakata_tinymce_timeout) {
                clearTimeout(window._vakata_tinymce_timeout);
            }
            if ($('.richtext-waiting').length) {
                setTimeout(create, 2000);
            }
        };
        if (window._vakata_tinymce_timeout) {
            clearTimeout(window._vakata_tinymce_timeout);
        }
        window._vakata_tinymce_timeout = setTimeout(create, 100);
    }, 100);
    </script>
<?php endif ?>
